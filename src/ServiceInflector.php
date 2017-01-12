<?php declare(strict_types = 1);

namespace Venta\Container;

use InvalidArgumentException;
use ReflectionMethod;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;

/**
 * Class ServiceInflector
 *
 * @package Venta\Container
 */
final class ServiceInflector implements ServiceInflectorContract
{

    /**
     * @var ArgumentResolverContract
     */
    private $argumentResolver;

    /**
     * A list of methods with arguments which will be invoked on the subject object.
     *
     * @var string[][]
     */
    private $inflections = [];

    /**
     * ServiceInflector constructor.
     *
     * @param ArgumentResolverContract $argumentResolver
     */
    public function __construct(ArgumentResolverContract $argumentResolver)
    {
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * @inheritDoc
     */
    public function addInflection(string $id, string $method, array $arguments = [])
    {
        if (!method_exists($id, $method)) {
            throw new InvalidArgumentException(sprintf('Method "%s" not found in "%s".', $method, $id));
        }

        $this->inflections[$id][$method] = $arguments;
    }

    /**
     * @inheritDoc
     */
    public function inflect($object)
    {
        foreach ($this->inflections as $type => $methods) {
            if (!$object instanceof $type) {
                continue;
            }

            foreach ($methods as $method => $inflection) {
                // $inflection may be array of arguments to pass to the method
                // OR prepared closure to call with the provided object context.
                if (is_array($inflection)) {

                    // Reflect and resolve method arguments.
                    $arguments = $this->argumentResolver->resolve(new ReflectionMethod($type, $method), $inflection);

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