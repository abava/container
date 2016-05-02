<?php declare(strict_types = 1);

namespace Venta\Container\Resolver;

use Venta\Contracts\Container\ItemContract;

/**
 * Class ClosureResolver
 *
 * @package Venta\Container
 */
class ClosureResolver extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(ItemContract $item, array $arguments = [])
    {
        $closure = $item->getResolvingItem();
        $reflection = new \ReflectionFunction($closure);

        return $reflection->invokeArgs($this->_createParametersOutOfArguments($reflection->getParameters(), $arguments));
    }
}