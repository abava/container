<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Contracts\Container\Container as ContainerContract;

/**
 * Class ContainerProxy
 *
 * @package Venta\Container
 */
final class ContainerProxy implements ContainerContract
{
    /**
     * @var ContainerContract
     */
    private $container;

    /**
     * ContainerProxy constructor.
     *
     * @param ContainerContract $container
     */
    public function __construct(ContainerContract $container)
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