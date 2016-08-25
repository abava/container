<?php declare(strict_types = 1);

namespace Abava\Container;

use Abava\Container\Contract\Container;

/**
 * Trait ContainerAwareTrait
 *
 * @package Abava\Container
 */
trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}