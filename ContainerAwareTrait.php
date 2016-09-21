<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Container\Contract\Container as ContainerContract;

/**
 * Trait ContainerAwareTrait
 *
 * @package Venta\Container
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerContract
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param ContainerContract $container
     * @return $this
     */
    public function setContainer(ContainerContract $container)
    {
        $this->container = $container;

        return $this;
    }
}