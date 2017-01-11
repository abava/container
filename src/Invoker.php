<?php declare(strict_types = 1);

namespace Venta\Container;

use InvalidArgumentException;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container as ContainerContract;
use Venta\Contracts\Container\Invoker as InvokerContract;

/**
 * Class Invoker
 *
 * @package Venta\Container
 */
class Invoker implements InvokerContract
{

    /**
     * @var ContainerContract
     */
    private $container;

    /**
     * @var ArgumentResolverContract
     */
    private $resolver;

    /**
     * Invoker constructor.
     *
     * @param ContainerContract $container
     * @param ArgumentResolverContract $resolver
     */
    public function __construct(ContainerContract $container, ArgumentResolverContract $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function call($callable, array $arguments = [])
    {
        return $this->invoke(new Invokable($callable), $arguments);
    }

    /**
     * @inheritDoc
     */
    public function invoke(Invokable $invokable, array $arguments = [])
    {
        $reflection = $invokable->reflection();
        $arguments = $this->resolver->resolve($reflection, $arguments);

        if ($invokable->isFunction()) {
            // We have Closure or "functionName" string.
            $callable = $invokable->callable();

            return $callable(...$arguments);
        }
        list($object, $method) = $invokable->callable();
        if ($reflection->isStatic()) {
            return $object::$method(...$arguments);
        }

        if ($method === '__construct') {
            return new $object(...$arguments);
        }

        if (is_string($object)) {
            $object = $this->container->get($object);
        }

        return $object->$method(...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function isCallable($callable): bool
    {
        try {
            $invokable = new Invokable($callable);
            if ($invokable->isFunction()) {
                return true;
            }
            $object = $invokable->callable()[0];

            return is_object($object) || $this->container->has($object);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }


}