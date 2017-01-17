<?php declare(strict_types = 1);

namespace Venta\Container;

use InvalidArgumentException;
use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\Invoker as InvokerContract;
use Venta\Contracts\Container\ServiceDecorator as ServiceDecoratorContract;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;

/**
 * Class ServiceDecorator
 *
 * @package Venta\Contracts\Container
 */
final class ServiceDecorator implements ServiceDecoratorContract
{
    /**
     * @var ContainerContract
     */
    private $container;

    /**
     * Array of decorator definitions.
     *
     * @var Invokable[][]|string[][]
     */
    private $decorators = [];

    /**
     * @var ServiceInflectorContract
     */
    private $inflector;

    /**
     * @var InvokerContract
     */
    private $invoker;

    /**
     * ServiceDecorator constructor.
     *
     * @param ContainerContract $container
     * @param ServiceInflectorContract $inflector
     * @param InvokerContract $invoker
     */
    public function __construct(
        ContainerContract $container,
        ServiceInflectorContract $inflector,
        InvokerContract $invoker
    ) {
        $this->container = $container;
        $this->inflector = $inflector;
        $this->invoker = $invoker;
    }

    /**
     * @inheritdoc
     */
    public function add(string $id, $decorator)
    {
        if (is_string($decorator)) {
            if (!class_exists($decorator)) {
                throw new InvalidArgumentException(sprintf('Invalid decorator class "%s" provided.', $decorator));
            }
        } else {
            $decorator = new Invokable($decorator);
        }

        $this->decorators[$id][] = $decorator;
    }

    /**
     *
     * @param string $id
     * @param $object
     * @param bool $once
     * @return mixed
     */
    public function apply(string $id, $object, bool $once = false)
    {
        if (empty($this->decorators[$id])) {
            return $object;
        }

        foreach ($this->decorators[$id] as $decorator) {
            $object = $decorator instanceof Invokable
                ? $this->invoker->invoke($decorator, [$object])
                : $this->container->get($decorator, [$object]);
            $this->inflector->apply($object);
        }

        if ($once) {
            unset($this->decorators[$id]);
        }

        return $object;
    }
}