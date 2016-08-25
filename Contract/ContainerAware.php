<?php declare(strict_types = 1);

namespace Abava\Container\Contract;

/**
 * Interface ContainerAware
 *
 * @package Abava\Container\Contract
 */
interface ContainerAware
{
    /**
     * Set container entity.
     *
     * @param Container $container
     * @return ContainerAware
     */
    public function setContainer(Container $container);
}