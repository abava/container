<?php declare(strict_types = 1);

namespace Venta\Container\Contract;

/**
 * Interface CallerContract
 * @package Venta\Container\Contract
 */
interface CallerContract
{

    /**
     * Resolve and call \Closure out of container
     *
     * @param  \Closure|string $callable
     * @param  array $args
     * @return mixed
     */
    public function call($callable, array $args = []);

}