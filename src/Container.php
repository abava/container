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
     * @var ArgumentResolverContract
     */
    private $argumentResolver;

    /**
     * Array of callable definitions.
     *
     * @var ReflectedCallable[]
     */
    private $callableDefinitions = [];

    /**
     * Array of class definitions.
     *
     * @var string[]
     */
    private $classDefinitions = [];

    /**
     * Array of decorator definitions.
     *
     * @var callable[][]
     */
    private $decoratorDefinitions = [];

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
     * @var ObjectInflectorContract
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
        $reflectedCallable = new ReflectedCallable($callable);
        if (!$this->isResolvableCallable($reflectedCallable)) {
            throw new InvalidArgumentException('Invalid callable provided.');
        }

        $this->register(
            $id,
            $shared,
            function ($id) use ($reflectedCallable) {
                $this->callableDefinitions[$id] = $reflectedCallable;
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
        $reflectedCallable = new ReflectedCallable($callable);
        $reflection = $reflectedCallable->reflection();
        $arguments = $this->argumentResolver->resolve($reflection, $arguments);

        if ($reflectedCallable->isFunction()) {
            // We have Closure or "functionName" string.
            $callable = $reflectedCallable->callable();

            return $callable(...$arguments);
        }

        list($object, $method) = $reflectedCallable->callable();
        if ($reflection->isStatic()) {
            return $object::$method(...$arguments);
        }

        if (is_string($object)) {
            $object = $this->get($object);
        }

        return $object->$method(...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function decorate($id, callable $callback)
    {
        $id = $this->normalize($id);

        // Check if correct id is provided.
        if (!$this->isResolvableService($id)) {
            throw new InvalidArgumentException('Invalid id provided.');
        }

        $this->decoratorDefinitions[$id][] = $callback;
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
            $object = $this->decorateObject($id, $this->instances[$id]);
            // Delete all decorator callbacks to avoid applying them once more on another get call.
            unset($this->decoratorDefinitions[$id]);

            return $object;
        }

        // Detect circular references.
        // We mark service as being resolved to detect circular references through out the resolution chain.
        if (isset($this->resolving[$id])) {
            throw new CircularReferenceException($id, $this->resolving);
        } else {
            $this->resolving[$id] = $id;
        }

        try {
            // Instantiate service and apply inflections.
            $object = $this->instantiateService($id, $arguments);
            $this->objectInflector->applyInflections($object);
            $object = $this->decorateObject($id, $object);

            // Cache shared instances.
            if (isset($this->shared[$id])) {
                $this->instances[$id] = $object;
                // Remove all decorator callbacks to prevent further decorations on concrete instance.
                unset($this->decoratorDefinitions[$id]);
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
            return $this->isResolvableCallable(new ReflectedCallable($callable));
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
     * Create callable factory with resolved arguments from callable.
     *
     * @param ReflectedCallable $reflectedCallable
     * @return Closure
     */
    private function createServiceFactoryFromCallable(ReflectedCallable $reflectedCallable): Closure
    {
        $reflection = $reflectedCallable->reflection();
        // Wrap reflected function arguments with closure.
        $resolve = $this->argumentResolver->createCallback($reflection);

        if ($reflectedCallable->isFunction()) {
            // We have Closure or "functionName" string.
            $callable = $reflectedCallable->callable();

            return function (array $arguments = []) use ($callable, $resolve) {
                return $callable(...$resolve($arguments));
            };
        }
        list($object, $method) = $reflectedCallable->callable();

        if (!$reflection->isStatic() && is_string($object)) {
            $object = $this->get($object);
        }

        // Wrap with Closure to save reflection resolve results.
        return function (array $arguments = []) use ($object, $method, $resolve) {
            return ([$object, $method])(...$resolve($arguments));
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
            ? $this->argumentResolver->createCallback($constructor)
            : null;

        return function (array $arguments = []) use ($class, $resolve) {
            $object = $resolve ? new $class(...$resolve($arguments)) : new $class();

            return $object;
        };
    }

    /**
     * Applies decoration callbacks to provided instance.
     *
     * @param string $id
     * @param $object
     * @return object
     */
    private function decorateObject(string $id, $object)
    {
        if (isset($this->decoratorDefinitions[$id])) {
            foreach ($this->decoratorDefinitions[$id] as $callback) {
                $object = $this->call($callback, [$object]);
                $this->objectInflector->applyInflections($object);
            }
        }

        return $object;
    }

    /**
     * Create callable factory for the subject service.
     *
     * @param string $id
     * @param array $arguments
     * @return mixed
     */
    private function instantiateService(string $id, array $arguments)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            if (isset($this->callableDefinitions[$id])) {
                $this->factories[$id] = $this->createServiceFactoryFromCallable($this->callableDefinitions[$id]);
            } elseif (isset($this->classDefinitions[$id]) && $this->classDefinitions[$id] !== $id) {
                // Recursive call allows to bind contract to contract.
                return $this->instantiateService($this->classDefinitions[$id], $arguments);
            } else {
                $this->factories[$id] = $this->createServiceFactoryFromClass($id);
            }
        }

        return ($this->factories[$id])($arguments);
    }

    /**
     * Check if subject service is an object instance.
     *
     * @param mixed $service
     * @return bool
     */
    private function isConcrete($service): bool
    {
        return is_object($service) && !$service instanceof Closure;
    }

    /**
     * Verifies that provided callable can be called by service container.
     *
     * @param ReflectedCallable $reflectedCallable
     * @return bool
     */
    private function isResolvableCallable(ReflectedCallable $reflectedCallable): bool
    {
        // If array represents callable we need to be sure it's an object or a resolvable service id.
        $callable = $reflectedCallable->callable();

        return !is_array($callable)
               || is_object($callable[0])
               || $this->isResolvableService($callable[0]);
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
     * Registers binding.
     * After this method call binding can be resolved by container.
     *
     * @param string $id
     * @param bool $shared
     * @param Closure $registrationCallback
     * @return void
     */
    private function register(string $id, bool $shared, Closure $registrationCallback)
    {
        // Check if correct service is provided.
        $this->validateId($id);
        $id = $this->normalize($id);

        // Clean up previous bindings, if any.
        unset($this->instances[$id], $this->shared[$id], $this->keys[$id]);

        // Register service with provided callback.
        $registrationCallback($id);

        // Mark service as shared when needed.
        $this->shared[$id] = $shared ?: null;

        // Save service key to make it recognizable by container.
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
