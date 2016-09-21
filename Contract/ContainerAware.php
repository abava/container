<?php declare(strict_types = 1);

namespace Venta\Container\Contract;

/**
 * Interface ContainerAware
 *
 * @package Venta\Container\Contracts
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