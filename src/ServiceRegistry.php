<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use InvalidArgumentException;
use Venta\Contracts\Container\ServiceDecorator as ServiceDecoratorContract;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;
use Venta\Contracts\Container\ServiceRegistry as ServiceRegistryContract;

/**
 * Class ServiceRegistry
 *
 * @package Venta\Container
 */
abstract class ServiceRegistry implements ServiceRegistryContract
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
     * Array of instances identifiers marked as shared.
     * Such instances will be instantiated once and returned on consecutive gets.
     *
     * @var bool[]
     */
    private $shared = [];

    /**
     * @inheritDoc
     */
    public function addDecorator(string $id, $decorator)
    {
        $id = $this->normalize($id);

        // Check if correct id is provided.
        if (!$this->isResolvableService($id)) {
            throw new InvalidArgumentException('Invalid id provided.');
        }

        $this->decorator()->addDecorator($id, $decorator);
    }

    /**
     * @inheritDoc
     */
    public function addInflection(string $id, string $method, array $arguments = [])
    {
        $this->inflector()->addInflection($id, $method, $arguments);
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
     * @param string $id
     * @return null|Invokable
     */
    protected function callableDefinition(string $id)
    {
        return $this->callableDefinitions[$id] ?? null;
    }

    /**
     * @param string $id
     * @return null|string
     */
    protected function classDefinition(string $id)
    {
        return $this->classDefinitions[$id] ?? null;
    }

    /**
     * @return ServiceDecoratorContract
     */
    abstract protected function decorator(): ServiceDecoratorContract;

    /**
     * @return ServiceInflectorContract
     */
    abstract protected function inflector(): ServiceInflectorContract;

    /**
     * @param $id
     * @return null|object
     */
    protected function instance(string $id)
    {
        return $this->instances[$id] ?? null;
    }

    /**
     * Verifies that provided callable can be called by service container.
     *
     * @param Invokable $reflectedCallable
     * @return bool
     */
    protected function isResolvableCallable(Invokable $reflectedCallable): bool
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
            throw new InvalidArgumentException(
                sprintf('Invalid service id "%s". Service id must be an existing interface or class name.', $id)
            );
        }
    }

}