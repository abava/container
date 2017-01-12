<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
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
class Container extends ServiceRegistry implements ContainerContract
{
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
     * @var InvokerContract
     */
    private $invoker;

    /**
     * Array of container service identifiers currently being resolved.
     *
     * @var string[]
     */
    private $resolving = [];

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $argumentResolver = new ArgumentResolver($this);
        $this->setInvoker(new Invoker($this, $argumentResolver));
        $this->setInflector(new ServiceInflector($argumentResolver));
        $this->setDecorator(new ServiceDecorator($this, $this->inflector(), $this->invoker));
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
    public function get($id, array $arguments = [])
    {
        $id = $this->normalize($id);
        // We try to resolve alias first to get a real service id.
        if (!$this->isResolvableService($id)) {
            throw new NotFoundException($id, $this->resolving);
        }

        // Look up service in resolved instances first.
        $object = $this->instance($id);
        if (!empty($object)) {
            $object = $this->decorator()->decorate($id, $object, true);

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
            $this->inflector()->inflect($object);
            $object = $this->decorator()->decorate($id, $object, $this->isShared($id));

            // Cache shared instances.
            if ($this->isShared($id)) {
                $this->bindInstance($id, $object);
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
        return $this->invoker->isCallable($callable);
    }

    /**
     * @inheritDoc
     */
    protected function decorator(): ServiceDecoratorContract
    {
        return $this->decorator;
    }

    /**
     * @inheritDoc
     */
    protected function inflector(): ServiceInflectorContract
    {
        return $this->inflector;
    }

    /**
     * @param ServiceDecoratorContract $decorator
     */
    protected function setDecorator(ServiceDecoratorContract $decorator)
    {
        $this->decorator = $decorator;
    }

    /**
     * @param ServiceInflectorContract $inflector
     */
    protected function setInflector(ServiceInflectorContract $inflector)
    {
        $this->inflector = $inflector;
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
        $instance = $this->instance($id);
        if (!empty($instance)) {
            return $instance;
        }

        if (!isset($this->factories[$id])) {
            if (!empty($this->callableDefinition($id))) {
                $this->factories[$id] = $this->createServiceFactoryFromCallable($this->callableDefinition($id));
            } elseif (!empty($this->classDefinition($id)) && $this->classDefinition($id) !== $id) {
                // Recursive call allows to bind contract to contract.
                return $this->instantiateService($this->classDefinition($id), $arguments);
            } else {
                $this->factories[$id] = $this->createServiceFactoryFromClass($id);
            }
        }

        return ($this->factories[$id])($arguments);
    }

}
