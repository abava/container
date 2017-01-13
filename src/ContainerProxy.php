<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\MutableContainer as MutableContainerContract;

/**
 * Class ContainerProxy
 *
 * @package Venta\Container
 */
final class ContainerProxy implements ContainerContract
{
    /**
     * @var MutableContainerContract
     */
    private $container;

    /**
     * ContainerProxy constructor.
     *
     * @param MutableContainerContract $container
     */
    public function __construct(MutableContainerContract $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function call($callable, array $arguments = [])
    {
        return $this->container->call($callable, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function get($id, array $arguments = [])
    {
        return $this->container->get($id, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * @inheritDoc
     */
    public function isCallable($callable): bool
    {
        return $this->container->isCallable($callable);
    }

}