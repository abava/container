<?php

namespace Venta\Container;

/**
 * Class Item
 *
 * @package Venta\Container
 */
class Item
{
    /**
     * Container item alias holder
     *
     * @var string
     */
    protected $_alias;

    /**
     * Container item holder
     *
     * @var mixed
     */
    protected $_item;

    /**
     * Defines, if instance is shared
     *
     * @var bool
     */
    protected $_shared = false;

    /**
     * Resolved instance holder for shared instances
     *
     * @var mixed
     */
    protected $_resolved;

    /**
     * Application instance holder
     *
     * @var \Venta\Contracts\Container\ContainerContract
     */
    protected $_container;

    /**
     * Construct function
     *
     * @param  string $alias
     * @param  mixed $item
     * @param  bool  $shared
     */
    public function __construct($alias, $item, $shared = false)
    {
        $this->_alias = $alias;
        $this->_item = $item;
        $this->_shared = $shared;
    }

    /**
     * Returns resolved item
     *
     * @param  array $arguments
     * @return mixed
     */
    public function make($arguments = [])
    {
        $instance = null;

        // 1. Check if instance is shared and resolved, return if it does
        if ($this->_shared === true && $this->_resolved !== null) {
            return $this->_resolved;
        }

        // 2. If item is instance, we just set it as resolved and return
        if (is_object($this->_item) && !($this->_item instanceof \Closure)) {
            $this->_shared = true;
            $this->_resolved = $this->_item;
            $instance = $this->_item;
        }

        // 3. Check, if item is a closure we need to resolve
        if ($this->_item instanceof \Closure) {
            $instance = $this->_call($this->_item, null, $arguments);
        }

        // 4. Check, if item is a string
        if (is_string($this->_item)) {
            $instance = $this->_resolve($arguments);
        }

        // 5. If this is shared instance, save it for later use
        if ($this->_shared === true) {
            $this->_resolved = $instance;
        }

        return $instance;
    }

    /**
     * Performs rewrite of definition
     *
     * @param  mixed $item
     * @return void
     */
    public function rewrite($item)
    {
        $rewrite = (new Item(null, $item))->make();
        $active = $this->make();

        if (is_object($rewrite) && is_object($active) && !($rewrite instanceof $active)) {
            throw new \LogicException('Rewrite class' . get_class($rewrite) . ' should extend ' . get_class($active));
        }

        $this->_item = $item;
        $this->_resolved = null;
    }

    /**
     * Application setter
     *
     * @param  \Venta\Contracts\Container\ContainerContract $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * Resolves DI definitions, returns resolved instance
     *
     * @param  array $arguments
     * @return mixed
     */
    protected function _resolve($arguments)
    {
        if (interface_exists($this->_item)) {
            return $this->_container->get($this->_item);
        }

        $definition = $this->_parseStringDefinition();
        $reflection = new \ReflectionClass($definition['class']);
        $instance = $reflection->newInstanceArgs($this->_getArguments($this->_getMethodParameters($reflection)));

        return $definition['method'] === null ? $instance : $this->_call($instance, $definition['method'], $arguments);
    }

    /**
     * Resolve and call specific method on resolved instance and return result
     *
     * @param  mixed $instance
     * @param  string $method
     * @param  array $arguments
     * @return mixed
     */
    protected function _call($instance, $method, $arguments)
    {
        $data = $this->_normaliseCallArguments($instance, $method);

        return call_user_func_array(
            $data['callable'],
            $this->_getArguments($data['parameters'], $arguments)
        );
    }

    /**
     * @param  \Closure|mixed $instance
     * @param  string|null $method
     * @return array
     */
    protected function _normaliseCallArguments($instance, $method)
    {
        if ($instance instanceof \Closure) {
            return [
                'callable' => $instance,
                'parameters' => (new \ReflectionFunction($instance))->getParameters()
            ];
        }

        return [
            'callable' => [$instance, $method],
            'parameters' => $this->_getMethodParameters(new \ReflectionObject($instance), $method)
        ];
    }

    /**
     * Returns array of resolved arguments, based on parameters array
     *
     * @param  \ReflectionParameter[]|array $parameters
     * @param  array $defaultArguments
     * @return array
     */
    protected function _getArguments($parameters, $defaultArguments = [])
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            if ($parameter->hasType()) {
                $argument = (new Item(null, $parameter->getType()->__toString()))
                    ->setContainer($this->_container)
                    ->make();
            } else {
                if (array_key_exists($parameter->name, $defaultArguments)) {
                    $argument = $defaultArguments[$parameter->name];
                } else {
                    $argument = $parameter->isOptional()
                        ? $parameter->getDefaultValue()
                        : null;
                }
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * Returns method parameters out of reflection class
     *
     * @param  \ReflectionClass $reflection
     * @param  string|null $method
     * @return \ReflectionParameter[]
     */
    protected function _getMethodParameters($reflection, $method = null)
    {
        $default = [];

        if ($method === null) {
            return $reflection->getConstructor()
                ? $reflection->getConstructor()->getParameters()
                : $default;
        }

        return $reflection->hasMethod($method)
            ? $reflection->getMethod($method)->getParameters()
            : $default;
    }

    /**
     * Parses string definition of an item and returns array with class and method names
     *
     * @return array
     */
    protected function _parseStringDefinition()
    {
        // 1. Check, if it is class@method definition
        $exploded = array_filter(explode('@', $this->_item), function($item){
            return !!$item;
        });

        if (count($exploded) >= 2) {
            return array_combine(['class', 'method'], array_slice($exploded, 0, 2));
        }

        return [
            'class' => str_replace('@', '', $this->_item),
            'method' => null
        ];
    }
}