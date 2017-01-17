<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use ReflectionClass;
use Venta\Container\Exception\ArgumentResolverException;
use Venta\Container\Exception\CircularReferenceException;
use Venta\Container\Exception\NotFoundException;
use Venta\Container\Exception\UninstantiableServiceException;
use Venta\Container\Exception\UnresolvableDependencyException;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\Invoker as InvokerContract;

/**
 * Class Container
 *
 * @package Venta\Container
 */
abstract class AbstractContainer implements ContainerContract
{

    /**
     * Array of callable definitions.
     *
     * @var Invokable[]
     */
    protected $callableDefinitions = [];

    /**
     * Array of class definitions.
     *
     * @var string[]
     */
    protected $classDefinitions = [];

    /**
     * Array of resolved instances.
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * Array of container service identifiers.
     *
     * @var string[]
     */
    protected $keys = [];

    /**
     * Array of instances identifiers marked as shared.
     * Such instances will be instantiated once and returned on consecutive gets.
     *
     * @var bool[]
     */
    protected $shared = [];

    /**
     * Array of container service callable factories.
     *
     * @var Closure[]
     */
    private $factories = [];

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
     *
     * @param ArgumentResolverContract|null $resolver
     */
    public function __construct(ArgumentResolverContract $resolver = null)
    {
        $this->setInvoker(new Invoker($this, $resolver ?: new ArgumentResolver($this)));
    }

    /**
     * @inheritDoc
     */
    public function get($id, array $arguments = [])
    {
        try {
            $id = $this->normalize($id);
            $this->resolving($id);
            $object = $this->instantiateService($id, $arguments);

            return $object;
        } catch (ArgumentResolverException $resolveException) {
            throw new UnresolvableDependencyException($id, $this->resolving, $resolveException);
        } finally {
            $this->resolved($id);
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
     * Create callable factory for the subject service.
     *
     * @param string $id
     * @param array $arguments
     * @return mixed
     * @throws NotFoundException
     */
    protected function instantiateService(string $id, array $arguments)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($arguments);
        }

        if (isset($this->callableDefinitions[$id])) {
            $this->factories[$id] = $this->createServiceFactoryFromCallable($this->callableDefinitions[$id]);

            return $this->invokeFactory($id, $arguments);
        }

        $class = $this->classDefinitions[$id] ?? $id;
        if ($class !== $id) {
            // Recursive call allows to bind contract to contract.
            return $this->saveShared($id, $this->instantiateService($class, $arguments));
        }
        if (!class_exists($class)) {
            throw new NotFoundException($id, $this->resolving);
        }
        $this->factories[$id] = $this->createServiceFactoryFromClass($class);

        return $this->invokeFactory($id, $arguments);
    }

    /**
     * @return InvokerContract
     */
    protected function invoker(): InvokerContract
    {
        return $this->invoker;
    }

    /**
     * Check if container can resolve the service with subject identifier.
     *
     * @param string $id
     * @return bool
     */
    protected function isResolvableService(string $id): bool
    {
        return isset($this->keys[$id]) || class_exists($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    protected function isShared(string $id): bool
    {
        return isset($this->shared[$id]);
    }

    /**
     * Normalize key to use across container.
     *
     * @param  string $id
     * @return string
     */
    protected function normalize(string $id): string
    {
        return ltrim($id, '\\');
    }

    /**
     * @param string $id
     * @return void
     */
    protected function resolved(string $id)
    {
        unset($this->resolving[$id]);
    }

    /**
     * Detects circular references.
     *
     * @param string $id
     * @return void
     * @throws CircularReferenceException
     */
    protected function resolving(string $id)
    {
        if (isset($this->resolving[$id])) {
            throw new CircularReferenceException($id, $this->resolving);
        }

        // We mark service as being resolved to detect circular references through out the resolution chain.
        $this->resolving[$id] = $id;
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
     * @param string $id
     * @param array $arguments
     * @return object
     */
    private function invokeFactory(string $id, array $arguments)
    {
        return $this->saveShared($id, ($this->factories[$id])($arguments));
    }

    /**
     * @param string $id
     * @param object $object
     * @return object
     */
    private function saveShared(string $id, $object)
    {
        if ($this->isShared($id)) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

}
