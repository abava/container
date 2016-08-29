<?php declare(strict_types = 1);

namespace Abava\Container;

use Abava\Container\Contract\Container as ContainerContract;

/**
 * Trait ContainerAwareTrait
 *
 * @package Abava\Container
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