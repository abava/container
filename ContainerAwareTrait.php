<?php


namespace Abava\Container;


use Abava\Container\Contract\Container;

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