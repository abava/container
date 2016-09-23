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

            foreach ($methods as $method => $inflection) {
                // $inflection may be array of arguments to pass to the method
                // OR prepared closure to call with the provided object context.
                if (!is_callable($inflection)) {

                    // Reflect and resolve method arguments.
                    $callback = $this->argumentResolver->resolveArguments(
                        $this->argumentResolver->reflectCallable([$type, $method])
                    );

                    // Replace method arguments with provided ones (if any).
                    $arguments = $callback($inflection);

                    // Wrap calling method with closure to avoid reflecting / resolving each time inflection applied.
                    $this->inflections[$type][$method] = $inflection = function () use ($method, $arguments) {
                        $this->$method(...$arguments);
                    };
                }

                // We have callable inflection ready, so we simply swap the context to provided object and call it.
                $inflection->call($object);
            }
        }

        return $object;
    }

}