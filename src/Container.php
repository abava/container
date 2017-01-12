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
use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\Invoker as InvokerContract;
use Venta\Contracts\Container\ServiceDecorator as ServiceDecoratorContract;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container implements ContainerContract
{

    /**
     * Array of callable definitions.
     *
     * @var Invokable[]
     */
    private $callableDefinitions = [];

    /**
     * Array of class definitions.
     *
     * @var string[]
     */
    private $classDefinitions = [];

    /**
     * @var ServiceDecoratorContract
     */
    private $decorator;

    /**
     * Array of container service callable factories.
     *
     * @var Closure[]
     */
    private $factories = [];

    /**
     * @var ServiceInflectorContract
     */
    private $inflector;

    /**
     * Array of resolved instances.
     *
     * @var object[]
     */
    private $instances = [];

    /**
     * @var InvokerContract
     */
    private $invoker;

    /**
     * Array of container service identifiers.
     *
     * @var string[]
     */
    private $keys = [];

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
        $argumentResolver = new ArgumentResolver($this);
        $this->setInvoker(new Invoker($this, $argumentResolver));
        $this->setServiceInflector(new ServiceInflector($argumentResolver));
        $this->setServiceDecorator(new ServiceDecorator($this, $this->inflector, $this->invoker));
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
        $reflectedCallable = new Invokable($callable);
        if (!$this->isResolvableCallable($reflectedCallable)) {
            throw new InvalidArgumentException('Invalid callable provided.');
        }

        $this->register($id, $shared, function ($id) use ($reflectedCallable) {
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
        return $this->invoker->call($callable, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function decorate($id, $decorator)
    {
        $id = $this->normalize($id);

        // Check if correct id is provided.
        if (!$this->isResolvableService($id)) {
            throw new InvalidArgumentException('Invalid id provided.');
        }

        $this->decorator->addDecorator($id, $decorator);
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
            $object = $this->decorator->decorate($id, $this->instances[$id], true);

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
            $this->inflector->inflect($object);
            $object = $this->decorator->decorate($id, $object, isset($this->shared[$id]));

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
    public function inflect(string $id, string $method, array $arguments = [])
    {
        $this->inflector->addInflection($id, $method, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function isCallable($callable): bool
    {
        return $this->invoker->isCallable($callable);
    }

    /**
     * @param InvokerContract $invoker
     * @return void
     */
    protected function setInvoker(InvokerContract $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * @param ServiceDecoratorContract $decorator
     * @return void
     */
    protected function setServiceDecorator(ServiceDecoratorContract $decorator)
    {
        $this->decorator = $decorator;
    }

    /**
     * @param ServiceInflectorContract $inflector
     * @return void
     */
    protected function setServiceInflector(ServiceInflectorContract $inflector)
    {
        $this->inflector = $inflector;
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
     * @param Invokable $invokable
     * @return Closure
     */
    private function createServiceFactoryFromCallable(Invokable $invokable): Closure
    {
        return function (array $arguments = []) use ($invokable) {
            return $this->invoker->invoke($invokable, $arguments);
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

        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            $invokable = new Invokable($constructor);

            return function (array $arguments = []) use ($invokable) {
                return $this->invoker->invoke($invokable, $arguments);
            };
        }

        return function () use ($class) {
            return new $class();
        };
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
     * @param Invokable $reflectedCallable
     * @return bool
     */
    private function isResolvableCallable(Invokable $reflectedCallable): bool
    {
        // If array represents callable we need to be sure it's an object or a resolvable service id.
        $callable = $reflectedCallable->callable();

        return $reflectedCallable->isFunction()
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
