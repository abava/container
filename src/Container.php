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
    }

    /**
     * Add shared instance to container
     *
     * @param string   $abstract
     * @param mixed    $concrete
     */
    public function singleton(string $abstract, $concrete)
    {
        $abstract = $this->normalizeClassName($abstract);

        if ($this->has($abstract)) {
            throw new \InvalidArgumentException(sprintf('Container item "%s" is already defined', $abstract));
        }

        $this->keys[$abstract] = true;
        $this->shared[$abstract] = true;
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Add instance to container
     *
     * @param string $abstract
     * @param mixed  $concrete
     */
    public function instance(string $abstract, $concrete)
    {
        $abstract = $this->normalizeClassName($abstract);

        if ($this->has($abstract)) {
            throw new \InvalidArgumentException(sprintf('Container item "%s" is already defined', $abstract));
        }

        if (!is_object($concrete) || $concrete instanceof \Closure) {
            throw new \InvalidArgumentException(sprintf('Passed item is not an instance for container item "%s"', $abstract));
        }

        $this->keys[$abstract] = true;
        $this->shared[$abstract] = true;
        $this->instances[$abstract] = $concrete;
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
     * @return mixed
     */
    public function make(string $abstract)
    {
        $abstract = $this->normalizeClassName($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->factories[$abstract])) {
            $concrete = isset($this->bindings[$abstract]) ? $this->bindings[$abstract] : $abstract;
            $this->factories[$abstract] = $this->getFactory($abstract, $concrete);
        }

        return $this->factories[$abstract]();
    }

    /**
     * Normalize class name, if it is string
     *
     * @param  mixed $class
     * @return mixed
     */
    protected function normalizeClassName($class)
    {
        return is_string($class) ? ltrim($class, '\\') : $class;
    }

    /**
     * Returns initialisation factory for objects
     *
     * @param  string $abstract
     * @param  mixed  $concrete
     * @return \Closure
     */
    protected function getFactory(string $abstract, $concrete): \Closure
    {
        if (!is_string($concrete) && !($concrete instanceof \Closure)) {
            throw new \InvalidArgumentException(sprintf('Can not resolve this type of binding: "%s"', $concrete));
        }

        if (is_string($concrete)) {
            $concrete = $this->build($concrete);
        }

        return function () use ($abstract, $concrete) {
            $instance = $concrete($this);

            if (isset($this->shared[$abstract])) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        };
    }

    /**
     * Builds object from scratch with DI
     *
     * @param  string $class
     * @return mixed
     */
    protected function build(string $class)
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $arguments = $this->buildArguments($constructor);

        return function () use ($reflection, $constructor, $arguments) {
            $instance = $reflection->newInstanceWithoutConstructor();

            if ($constructor) {
                $constructor->invokeArgs($instance, $arguments());
            }

            return $instance;
        };
    }

    /**
     * Build up arguments array for provided method
     *
     * @param \ReflectionMethod|null $method
     * @return \Closure
     */
    protected function buildArguments(\ReflectionMethod $method = null): \Closure
    {
        $parameters = $method === null ? [] : array_map(function(\ReflectionParameter $parameter) {
            return [$parameter, $parameter->getClass() ? $parameter->getClass()->name : null];
        }, $method->getParameters());

        return function () use ($parameters) {
            $arguments = [];

            foreach ($parameters as list($parameter, $class)) {
                /** @var \ReflectionParameter $parameter */
                $value = null;

                if ($class !== null) {
                    $value = $this->make($class);
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $value = $parameter->getDefaultValue();
                }

                $arguments[] = $value;
            }

            return $arguments;
        };
    }
}