<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Venta\Container\Exception\ArgumentResolveException;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container as ContainerContract;

/**
 * Class ArgumentResolver.
 *
 * @package Venta\Container
 */
final class ArgumentResolver implements ArgumentResolverContract
{
    use ContainerAwareTrait;

    /**
     * ArgumentResolver constructor.
     *
     * @param ContainerContract $container
     */
    public function __construct(ContainerContract $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function reflectCallable($callable): ReflectionFunctionAbstract
    {
        return is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);
    }

    /**
     * @inheritDoc
     */
    public function resolveArguments(ReflectionFunctionAbstract $function): Closure
    {
        return function (array $arguments = []) use ($function) {

            return array_map(function (ReflectionParameter $parameter) use ($arguments, $function) {

                // If passed use argument instead of reflected parameter.
                $name = $parameter->getName();
                if (array_key_exists($name, $arguments)) {
                    return $arguments[$name];
                }

                // Recursively resolve function arguments.
                $class = $parameter->getClass();
                if ($class !== null && $this->container->has($class->getName())) {
                    return $this->container->get($class->getName());
                }

                // Use argument default value if defined.
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                // The argument can't be resolved by this resolver
                throw new ArgumentResolveException($parameter, $function);
            }, $function->getParameters());
        };
    }
}