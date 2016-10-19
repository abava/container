<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;

/**
 * Class ArgumentResolverAwareTrait
 *
 * @package Venta\Container
 */
trait ArgumentResolverAwareTrait
{
    /**
     * @var ArgumentResolverContract
     */
    protected $argumentResolver;

    /**
     * @param ArgumentResolverContract $argumentResolver
     */
    public function setArgumentResolver(ArgumentResolverContract $argumentResolver)
    {
        $this->argumentResolver = $argumentResolver;
    }

}