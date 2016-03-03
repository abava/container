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
        // 1. Check alias exists
        if ($this->has($id)) {
            return $this->_container[$id]->make();
        }

        // 2. Check if it is class name
        if (class_exists($id) || interface_exists($id)) {
            return $this->_makeItem(null, $id)->make();
        }

        throw new \Venta\Container\Exceptions\NotFoundException(
            sprintf('Item %s cannot be resolved', $id)
        );
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
        return $this->_makeItem(null, $method)->make($arguments);
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
     * @throws \Venta\Container\Exceptions\RewriteException
     * @throws \Venta\Container\Exceptions\InterfaceBindingException
     * @return void
     */
    protected function _bindToContainer($alias, $item, $shared = false)
    {
        if ($this->has($alias)) {
            throw new \Venta\Container\Exceptions\RewriteException(
                sprintf('Alias "%s" is already registered. Use rewrite function in order to rewrite it.', $alias)
            );
        }

        if (interface_exists($alias)) {
            $item = $this->_makeItem(null, $item)->make();

            if (!($item instanceof $alias)) {
                throw new \Venta\Container\Exceptions\InterfaceBindingException(
                    sprintf('Can not bind %s implementation: it does not implement interface', $alias)
                );
            }
        }

        $this->_container[$alias] = $this->_makeItem($alias, $item, $shared)->setContainer($this);
    }

    /**
     * Returns new container item instance
     *
     * @param  string $alias
     * @param  mixed $item
     * @param  bool $shared
     * @return Item
     */
    protected function _makeItem($alias, $item, $shared = false)
    {
        return new Item($alias, $item, $shared === true);
    }
}