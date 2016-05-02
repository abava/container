<?php declare(strict_types = 1);

namespace Venta\Container\Resolver;

use Venta\Container\Item;
use Venta\Contracts\Container\ItemContract;

/**
 * Class StringResolver
 *
 * @package Venta\Container
 */
class StringResolver extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(ItemContract $item, array $arguments = [])
    {
        if (strpos($item->getResolvingItem(), '@') !== false) {
            return $this->_resolveAndCallAMethod($item, $arguments);
        }

        return $this->_resolveAndReturnInstance($item, $arguments);
    }

    /**
     * Resolves item and returns new instance of it
     *
     * @param  ItemContract $item
     * @param  array $arguments
     * @return mixed
     */
    public function _resolveAndReturnInstance(ItemContract $item, array $arguments = [])
    {
        $reflection = new \ReflectionClass($item->getResolvingItem());
        $method = $reflection->getConstructor();

        if ($method === null) {
            return $reflection->newInstance();
        }

        return $reflection->newInstanceArgs($this->_createParametersOutOfArguments($method->getParameters(), $arguments));
    }

    /**
     * Resolves item and returns new instance of it
     *
     * @param  ItemContract $item
     * @param  array $arguments
     * @return mixed
     */
    public function _resolveAndCallAMethod(ItemContract $item, array $arguments = [])
    {
        list($className, $methodName) = explode('@', $item->getResolvingItem());
        $instance = $this->_resolveAndReturnInstance(new Item($className));

        if (method_exists($instance, $methodName)) {
            return $instance->$methodName(...$this->_createParametersOutOfArguments(
                (new \ReflectionObject($instance))->getMethod($methodName)->getParameters(),
                $arguments
            ));
        }

        return null;
    }
}