<?php

namespace Venta\Container;

use Venta\Contracts\Container\ContainerContract;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container implements ContainerContract
{
    /**
     * Real container itself
     *
     * @var Item[]
     */
    protected $_container = [];

    /**
     * {@inheritdoc}
     */
    public function bind($alias, $concrete)
    {
        $this->_bindToContainer($alias, $concrete);
    }

    /**
     * {@inheritdoc}
     */
    public function share($alias, $concrete)
    {
        $this->_bindToContainer($alias, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function make($alias)
    {
        return $this->get($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->_container[$id]->make();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->_container);
    }

    /**
     * {@inheritdoc}
     */
    public function call($method, $arguments = [])
    {
        return (new Item(null, $method))->make($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function rewrite($alias, $item)
    {
        if (!$this->has($alias)) {
            throw new \LogicException("Can't rewrite {$alias} - it doesn't exist in container");
        }

        $this->_container[$alias]->rewrite($item);
    }

    /**
     * Central container binding function
     *
     * @param  string $alias
     * @param  mixed $item
     * @param  bool $shared
     * @throws \Venta\Container\Exceptions\ContainerException
     * @return void
     */
    protected function _bindToContainer($alias, $item, $shared = false)
    {
        if ($this->has($alias)) {
            throw new \Venta\Container\Exceptions\ContainerException(
                sprintf('Alias "%s" is already registered. Use rewrite function in order to rewrite it.', $alias)
            );
        }

        $this->_container[$alias] = (new Item($alias, $item, $shared === true))->setContainer($this);
    }
}