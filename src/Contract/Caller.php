<?php declare(strict_types = 1);

namespace Abava\Container\Contract;

/**
 * Interface CallerContract
 *
 * @package Abava\Container\Contract
 */
interface Caller
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