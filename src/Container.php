<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use Venta\Container\Exception\ArgumentResolverException;
use Venta\Container\Exception\CircularReferenceException;
use Venta\Container\Exception\NotFoundException;
use Venta\Container\Exception\UninstantiableServiceException;
use Venta\Container\Exception\UnresolvableDependencyException;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\ObjectInflector as ObjectInflectorContract;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container implements ContainerContract
{
    /**
     * @var ArgumentResolver
     */
    private $argumentResolver;

    /**
     * Array of callable definitions.
     *
     * @var callable[]
     */
    private $callableDefinitions = [];

    /**
     * Array of class definitions.
     *
     * @var string[]
     */
    private $classDefinitions = [];

    /**
     * Array of container service callable factories.
     *
     * @var Closure[]
     */
    private $factories = [];

    /**
     * Array of resolved instances.
     *
     * @var object[]
     */
    private $instances = [];

    /**
     * Array of container service identifiers.
     *
     * @var string[]
     */
    private $keys = [];

    /**
     * @var ObjectInflector
     */
    private $objectInflector;

    /**
     * Array of container service identifiers currently being resolved.
     *
     * @var string[]
     */
    private $resolving = [];

    /**
     * Array of instances identifiers marked as shared.
     * Such instances will be instantiated once and returned on consecutive gets.
     *
     * @var bool[]
     */
    private $shared = [];

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this->setArgumentResolver(new ArgumentResolver($this))
             ->setObjectInflector(new ObjectInflector($this->argumentResolver));
    }

    /**
     * @inheritDoc
     */
    public function addInflection(string $id, string $method, array $arguments = [])
    {
        $this->validateId($id);
        if (!method_exists($id, $method)) {
            throw new InvalidArgumentException(sprintf('Method "%s" not found in "%s".', $method, $id));
        }

        $this->objectInflector->addInflection($this->normalize($id), $method, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function bindClass(string $id, string $class, $shared = false)
    {
        if (!$this->isResolvableService($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }
        $this->register($id, $shared, function ($id) use ($class) {
            $this->classDefinitions[$id] = $class;
        });
    }

    /**
     * @inheritDoc
     */
    public function bindFactory(string $id, $callable, $shared = false)
    {
        if (!$this->isCallable($callable)) {
            throw new InvalidArgumentException('Invalid callable provided.');
        }
        $this->register($id, $shared, function ($id) use ($callable) {
            $this->callableDefinitions[$id] = $callable;
        });
    }

    /**
     * @inheritDoc
     */
    public function bindInstance(string $id, $instance)
    {
        if (!$this->isConcrete($instance)) {
            throw new InvalidArgumentException('Invalid instance provided.');
        }
        $this->register($id, true, function ($id) use ($instance) {
            $this->instances[$id] = $instance;
        });
    }

    /**
     * @inheritDoc
     * @param callable|string $callable Callable to call OR class name to instantiate and invoke.
     */
    public function call($callable, array $arguments = [])
    {
        return ($this->createServiceFactoryFromCallable($this->normalizeCallable($callable)))($arguments);
    }

    /**
     * @inheritDoc
     */
    public function get($id, array $arguments = [])
    {
        $id = $this->normalize($id);
        // We try to resolve alias first to get a real service id.
        if (!$this->isResolvableService($id)) {
            throw new NotFoundException($id, $this->resolving);
        }

        // Look up service in resolved instances first.
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Detect circular references.
        // We mark service as being resolved to detect circular references through out the resolution chain.
        if (isset($this->resolving[$id])) {
            throw new CircularReferenceException($id, $this->resolving);
        } else {
            $this->resolving[$id] = $id;
        }

        try {
            // Create service factory closure.
            if (!isset($this->factories[$id])) {
                $this->factories[$id] = $this->createServiceFactory($id);
            }

            // Instantiate service and apply inflections.
            $object = $this->factories[$id]($arguments);
            $this->objectInflector->applyInflections($object);

            // Cache shared instances.
            if (isset($this->shared[$id])) {
                $this->instances[$id] = $object;
            }

            return $object;
        } catch (ArgumentResolverException $resolveException) {
            throw new UnresolvableDependencyException($id, $this->resolving, $resolveException);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return $this->isResolvableService($this->normalize($id));
    }

    /**
     * @inheritDoc
     */
    public function isCallable($callable): bool
    {
        try {
            $callable = $this->normalizeCallable($callable);

            return (is_array($callable)
                    && (is_object($callable[0]) || (is_string($callable[0]) && $this->has($callable[0]))))
                   || !is_array($callable);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param ArgumentResolverContract $argumentResolver
     * @return Container
     */
    protected function setArgumentResolver(ArgumentResolverContract $argumentResolver): Container
    {
        $this->argumentResolver = $argumentResolver;

        return $this;
    }

    /**
     * @param ObjectInflectorContract $objectInflector
     * @return Container
     */
    protected function setObjectInflector(ObjectInflectorContract $objectInflector): Container
    {
        $this->objectInflector = $objectInflector;

        return $this;
    }

    /**
     * Forbid container cloning.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Create callable factory for the subject service.
     *
     * @param string $id
     * @return Closure
     */
    private function createServiceFactory(string $id): Closure
    {
        if (isset($this->callableDefinitions[$id])) {
            return $this->createServiceFactoryFromCallable($this->callableDefinitions[$id]);
        }

        if (isset($this->classDefinitions[$id]) && $this->classDefinitions[$id] !== $id) {
            return $this->createServiceFactory($this->classDefinitions[$id]);
        }

        return $this->createServiceFactoryFromClass($id);
    }

    /**
     * Create callable factory with resolved arguments from callable.
     *
     * @param callable $callable
     * @return Closure
     */
    private function createServiceFactoryFromCallable($callable): Closure
    {
        $callable = $this->normalizeCallable($callable);

        $reflection = $this->argumentResolver->reflectCallable($callable);
        // Wrap reflected function arguments with closure.
        $resolve = $this->argumentResolver->resolveArguments($reflection);

        if (is_array($callable)) {
            list($object, $method) = $callable;
            if (!$reflection->isStatic() && is_string($object)) {
                $object = $this->get($object);
            }

            // Wrap with Closure to save reflection resolve results.
            return function (array $arguments = []) use ($object, $method, $resolve) {
                return ([$object, $method])(... $resolve($arguments));
            };
        }

        // We have Closure or "functionName" string.
        return function (array $arguments = []) use ($callable, $resolve) {
            return $callable(...$resolve($arguments));
        };
    }

    /**
     * Create callable factory with resolved arguments from class name.
     *
     * @param string $class
     * @return Closure
     * @throws UninstantiableServiceException
     */
    private function createServiceFactoryFromClass(string $class): Closure
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new UninstantiableServiceException($class, $this->resolving);
        }
        $constructor = $reflection->getConstructor();
        $resolve = ($constructor && $constructor->getNumberOfParameters())
            ? $this->argumentResolver->resolveArguments($constructor)
            : null;

        return function (array $arguments = []) use ($class, $resolve) {
            $object = $resolve ? new $class(...$resolve($arguments)) : new $class();

            return $object;
        };
    }

    /**
     * Check if subject service is a closure.
     *
     * @param $service
     * @return bool
     */
    private function isClosure($service): bool
    {
        return $service instanceof Closure;
    }

    /**
     * Check if subject service is an object instance.
     *
     * @param mixed $service
     * @return bool
     */
    private function isConcrete($service): bool
    {
        return is_object($service) && !$this->isClosure($service);
    }

    /**
     * Check if container can resolve the service with subject identifier.
     *
     * @param string $id
     * @return bool
     */
    private function isResolvableService(string $id): bool
    {
        return isset($this->keys[$id]) || class_exists($id);
    }

    /**
     * Normalize key to use across container.
     *
     * @param  string $id
     * @return string
     */
    private function normalize(string $id): string
    {
        return ltrim($id, '\\');
    }

    /**
     * Normalizes callable converting Class::method into [class, method] and throwing exception on invalid callable.
     *
     * @param $callable
     * @return callable
     * @throws InvalidArgumentException
     */
    private function normalizeCallable($callable)
    {
        if ($this->isClosure($callable)) {
            return $callable;
        }
        if ($this->isConcrete($callable)) {
            if (!method_exists($callable, '__invoke')) {
                throw new InvalidArgumentException('Invalid callable provided.');
            }

            // Callable object is an instance with magic __invoke() method.
            return [$callable, '__invoke'];
        }
        if (is_string($callable)) {
            if (function_exists($callable)) {
                return $callable;
            }
            if (method_exists($callable, '__invoke')) {
                // We allow to call class by name if `__invoke()` method is implemented.
                return [$callable, '__invoke'];
            }
            if (strpos($callable, '::') !== false) {
                // Replace "ClassName::methodName" string with ["ClassName", "methodName"] array.
                $callable = explode('::', $callable);
            }
        }

        if (is_array($callable) && isset($callable[0], $callable[1]) && method_exists($callable[0], $callable[1])) {
            return $callable;
        }

        throw new InvalidArgumentException('Invalid callable provided.');
    }

    /**
     * @param string $id
     * @param bool $shared
     * @param Closure $registrationCallback
     * @return void
     */
    private function register(string $id, bool $shared, Closure $registrationCallback)
    {
        $this->validateId($id);
        $id = $this->normalize($id);
        unset($this->instances[$id], $this->shared[$id], $this->keys[$id]);
        $registrationCallback($id);
        $this->shared[$id] = $shared ?: null;
        $this->keys[$id] = true;
    }

    /**
     * Validate service identifier. Throw an Exception in case of invalid value.
     *
     * @param string $id
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateId(string $id)
    {
        if (!interface_exists($id) && !class_exists($id)) {
            throw new InvalidArgumentException(sprintf(
                    'Invalid service id "%s". Service id must be an existing interface or class name.', $id
                )
            );
        }
    }
}
