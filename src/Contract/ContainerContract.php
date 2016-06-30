<?php declare(strict_types = 1);

namespace Venta\Container\Contract;

use Interop\Container\ContainerInterface;

/**
 * Interface ContainerContract
 *
 * @package Venta\Container
 */
interface ContainerContract extends ContainerInterface
{
    /**
     * Bind element to container
     *
     * @param string $abstract
     * @param mixed  $concrete
     */
    public function bind(string $abstract, $concrete);

    /**
     * Add shared instance to container
     *
     * @param string   $abstract
     * @param mixed    $concrete
     */
    public function singleton(string $abstract, $concrete);

    /**
     * Main container getter
     *
     * @param  string $abstract
     * @param  array  $args
     * @return mixed
     */
    public function make(string $abstract, array $args = []);

}