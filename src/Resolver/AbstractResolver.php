<?php declare(strict_types = 1);

namespace Venta\Container\Resolver;

use ReflectionMethod;
use Venta\Container\Item;
use Venta\Contracts\Container\ItemContract;
use Venta\Contracts\Container\ResolverContract;

/**
 * Class AbstractResolver
 *
 * @package Venta\Container
 */
abstract class AbstractResolver implements ResolverContract
{
    /**
     * {@inheritdoc}
     */
    abstract public function resolve(ItemContract $item, array $arguments = []);

    /**
     * Returns an array of parameters to pass into method
     *
     * @param  \ReflectionMethod[] $parameters
     * @param  array $arguments
     * @return array
     */
    protected function _createParametersOutOfArguments(array $parameters, array $arguments = []): array
    {
        $resolvedArguments = [];

        foreach ($parameters as $parameter) {
            /** @var \ReflectionType|null $type */
            $type = $parameter->getType();
            $name = $parameter->getName();
            $argument = null;

            if (array_key_exists($name, $arguments)) {
                $argument = $arguments[$name];
            } else {
                if ($type !== null && !$type->isBuiltin()) {
                    $argument = (new Item($type->__toString()))->resolve();
                } else {
                    $argument = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                }
            }

            $resolvedArguments[] = $argument;
        }

        return $resolvedArguments;
    }
}