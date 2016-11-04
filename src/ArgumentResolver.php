<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Venta\Container\Exception\ArgumentResolverException;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container as ContainerContract;

/**
 * Class ArgumentResolver.
 *
 * @package Venta\Container
 */
final class ArgumentResolver implements ArgumentResolverContract
{
    /**
     * @var ContainerContract
     */
    protected $container;

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
        $parameters = $function->getParameters();

        return function (array $arguments = []) use ($function, $parameters) {
            // Use passed arguments in place of reflected parameters.
            $provided = array_intersect_key($arguments, $parameters);

            // Remaining parameters will be resolved by container.
            $remaining = array_diff_key($parameters, $arguments);
            $resolved = array_map(function (ReflectionParameter $parameter) use ($function) {

                // Recursively resolve function arguments.
                $class = $parameter->getClass();
                if ($class !== null && $this->container->has($class->getName())) {
                    return $this->container->get($class->getName());
                }

                // Use argument default value if possible.
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                // The argument can't be resolved by this resolver.
                throw new ArgumentResolverException($parameter, $function);

            }, $remaining);

            $arguments = $provided + $resolved;

            // Sort combined result array by parameter indexes.
            ksort($arguments);

            return $arguments;
        };
    }
}