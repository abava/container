<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use InvalidArgumentException;
use Venta\Contracts\Container\MutableContainer as MutableContainerContract;
use Venta\Contracts\Container\ServiceDecorator as ServiceDecoratorContract;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;

/**
 * Class MutableContainer
 *
 * @package Venta\Container
 */
class MutableContainer extends AbstractContainer implements MutableContainerContract
{

    /**
     * @var ServiceDecoratorContract
     */
    private $decorator;

    /**
     * @var ServiceInflectorContract
     */
    private $inflector;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $resolver = new ArgumentResolver($this);
        parent::__construct($resolver);
        $this->setInflector(new ServiceInflector($resolver));
        $this->setDecorator(new ServiceDecorator($this, $this->inflector, $this->invoker()));
    }


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

        $this->decorator->addDecorator($id, $decorator);
    }

    /**
     * @inheritDoc
     */
    public function addInflection(string $id, string $method, array $arguments = [])
    {
        $this->inflector->addInflection($id, $method, $arguments);
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
     */
    protected function instantiateService(string $id, array $arguments)
    {
        $object = parent::instantiateService($id, $arguments);
        $this->inflector->inflect($object);

        return $this->decorator->decorate($id, $object, $this->isShared($id));
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