<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Contracts\Container\ObjectInflector as ObjectInflectorContract;

/**
 * Class ObjectInflector.
 *
 * @package Venta\Container
 */
final class ObjectInflector implements ObjectInflectorContract
{
    use ArgumentResolverAwareTrait;

    /**
     * A list of methods with arguments which will be invoked on the subject object.
     *
     * @var string[][]
     */
    private $inflections = [];

    /**
     * @inheritDoc
     */
    public function addInflection(string $id, string $method, array $arguments = [])
    {
        $this->inflections[$id][$method] = $arguments;
    }

    /**
     * @inheritDoc
     */
    public function applyInflections($object)
    {
        foreach ($this->inflections as $type => $methods) {
            if (!$object instanceof $type) {
                continue;
            }

            foreach ($methods as $method => &$inflection) {
                if (!is_callable($inflection)) {

                    // Get method reflection.
                    $reflect = function ($callable) {
                        return $this->argumentResolver->reflectCallable($callable);
                    };

                    // Get argument resolver.
                    $resolve = function ($reflection) {
                        return $this->argumentResolver->resolveArguments($reflection);
                    };

                    // We replace inflection with callable which has all the arguments already resolved
                    // to avoid resolving them again if the same method called on another object.
                    $inflection = function () use ($inflection, $type, $method, $reflect, $resolve) {
                        $this->$method(...($resolve($reflect([$type, $method])))($inflection));
                    };
                }

                // We have callable inflection ready, so we simply swap the context to provided object and call it.
                $inflection->call($object);
            }
        }

        return $object;
    }

}