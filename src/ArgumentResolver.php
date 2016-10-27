<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
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
        return function (array $arguments = []) use ($function) {

            $resolved = [];
            foreach ($function->getParameters() as $index => $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $arguments)) {
                    // If passed use argument instead of reflected parameter.
                    $resolved[] = $arguments[$name];
                    unset($arguments[$name]);
                    continue;
                }

                $class = $parameter->getClass();
                if ($class) {
                    // Try to find matching argument by type-hinted class or interface.
                    foreach ($arguments as $key => $argument) {
                        if (is_object($argument) && $argument instanceof $class) {
                            $resolved[] = $argument;
                            unset($arguments[$key]);
                            continue 2;
                        }
                    }
                }

                // Simply pass argument by index, if such exists.
                if (array_key_exists($index, $arguments)) {
                    $resolved[] = $arguments[$index];
                    unset($arguments[$index]);
                    continue;
                }

                // Recursively resolve function arguments.
                if ($class !== null && $this->container->has($class->getName())) {
                    $resolved[] = $this->container->get($class->getName());
                    continue;
                }

                // Use argument default value if defined.
                if ($parameter->isDefaultValueAvailable()) {
                    $resolved[] = $parameter->getDefaultValue();
                    continue;
                }

                // The argument can't be resolved by this resolver
                throw new ArgumentResolveException($parameter, $function);
            }

            return $resolved;
        };
    }
}