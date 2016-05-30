<?php declare(strict_types = 1);

namespace Venta\Container;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container
{
    /**
     * Array of container item keys
     *
     * @var array
     */
    protected $keys = [];

    /**
     * Array of defined instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Array of shared instances keys
     *
     * @var array
     */
    protected $shared = [];

    /**
     * Array of bindings
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Array of created container item factories
     *
     * @var array
     */
    protected $factories = [];

    /**
     * Array of closure keys
     *
     * @var array
     */
    protected $closures = [];

    /**
     * Bind element to container
     *
     * @param string $abstract
     * @param mixed  $concrete
     */
    public function bind(string $abstract, $concrete)
    {
        $abstract = $this->normalizeClassName($abstract);

        if ($this->has($abstract)) {
            throw new \InvalidArgumentException(sprintf('Container item "%s" is already defined', $abstract));
        }

        $this->keys[$abstract] = true;
        $this->bindings[$abstract] = $concrete;

        if ($concrete instanceof \Closure) {
            $this->closures[$abstract] = true;
        }
    }

    /**
     * Add shared instance to container
     *
     * @param string   $abstract
     * @param mixed    $concrete
     */
    public function singleton(string $abstract, $concrete)
    {
        $this->bind($abstract, $concrete);

        $this->shared[$this->resolveAlias($this->normalizeClassName($abstract))] = true;
    }

    /**
     * Defines, if item exists in container
     *
     * @param  string $abstract
     * @return bool
     */
    public function has($abstract): bool
    {
        return isset($this->keys[$abstract]);
    }

    /**
     * Main container getter
     *
     * @param  string $abstract
     * @param  array  $args
     * @return mixed
     */
    public function make(string $abstract, array $args = [])
    {
        $abstract = $this->resolveAlias($this->normalizeClassName($abstract));

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->factories[$abstract])) {
            $this->factories[$abstract] = isset($this->closures[$abstract])
                ? $this->getClosureFactory($this->bindings[$abstract], $abstract)
                : $this->getFactory($abstract);
        }

        return $this->factories[$abstract]($args);
    }

    /**
     * Resolve and call \Closure out of container
     *
     * @param  \Closure|string $callable
     * @param  array $args
     * @return mixed
     */
    public function call($callable, array $args = [])
    {
        $factory = $this->getCallableFactory($callable);

        return $factory($args);
    }

    /**
     * Normalize class name, if it is string
     *
     * @param  string $class
     * @return string
     */
    protected function normalizeClassName(string $class): string
    {
        return ltrim($class, '\\');
    }

    /**
     * Resolves container aliases into real items
     *
     * @param  string $alias
     * @return string
     */
    protected function resolveAlias(string $alias): string
    {
        if (
            isset($this->keys[$alias]) &&
            (isset($this->bindings[$alias]) && is_string($this->bindings[$alias]))
        ) {
            return $this->bindings[$alias];
        }

        return $alias;
    }

    /**
     * Returns closure binding resolving function
     *
     * @param \Closure $closure
     * @param  string   $abstract
     * @return \Closure
     */
    protected function getClosureFactory(\Closure $closure, string $abstract): \Closure
    {
        if (isset($this->shared[$abstract])) {
            return function () use ($closure, $abstract) {
                $this->instances[$abstract] = $closure($this);

                return $this->instances[$abstract];
            };
        }

        return function () use ($closure) {
            return $closure($this);
        };
    }

    /**
     * Returns initialisation factory for objects
     *
     * @param  string $abstract
     * @return \Closure
     */
    protected function getFactory(string $abstract): \Closure
    {
        $class = new \ReflectionClass($abstract);
        $constructor = $class->getConstructor();
        $arguments = $constructor ? $this->createArguments($constructor) : null;

        if (isset($this->shared[$abstract])) {
            return function (array $args = []) use ($abstract, $class, $constructor, $arguments) {
                $this->instances[$abstract] = $class->newInstanceWithoutConstructor();

                if ($constructor) {
                    $constructor->invokeArgs($this->instances[$abstract], $arguments($args));
                }

                return $this->instances[$abstract];
            };
        } else if ($arguments !== null) {
            return function (array $args = []) use ($class, $arguments) {
                return new $class->name(...$arguments($args));
            };
        }

        return function () use ($class) {
            return new $class->name;
        };
    }

    /**
     * Used by "call" method.
     * Returns closure to call in order to resolved passed in callable
     *
     * @param  \Closure|string $callable
     * @return \Closure
     */
    protected function getCallableFactory($callable): \Closure
    {
        if (is_string($callable) && strpos($callable, '@') !== false) {
            list($class, $method) = explode('@', $callable);

            $factory = $this->getFactory($class);
            $methodArguments = $this->createArguments(new \ReflectionMethod($class, $method));

            return function (array $args = []) use ($factory, $methodArguments, $method) {
                return $factory()->$method(...$methodArguments($args));
            };
        } else if ($callable instanceof \Closure) {
            $reflection = new \ReflectionFunction($callable);
            $arguments = $this->createArguments($reflection);

            return function (array $args = []) use ($callable, $arguments) {
                return $callable(...$arguments($args));
            };
        }

        throw new \InvalidArgumentException(sprintf('"%s" can not be called out of container', $callable));
    }

    /**
     * Returns arguments builder function
     *
     * @param  \ReflectionMethod|\ReflectionFunction|null $method
     * @return \Closure
     */
    protected function createArguments($method = null): \Closure
    {
        $parameters = ($method === null) ? [] : array_map(function(\ReflectionParameter $parameter) {
            return [$parameter, $parameter->getClass() ? $parameter->getClass()->name : null];
        }, $method->getParameters());

        return function (array $args = []) use ($parameters) {
            return array_map(function ($info) use ($args) {
                /** @var \ReflectionParameter $parameter */
                list($parameter, $class) = $info;

                if (isset($args[$parameter->name])) {
                    return $args[$parameter->name];
                } else if ($class !== null) {
                    return $this->make($class);
                } else if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                return null;
            }, $parameters);
        };
    }
}