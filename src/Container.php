<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Container\Callback\Manager;
use Venta\Contracts\Container\CallbackManagerContract;
use Venta\Contracts\Container\ContainerContract;
use Venta\Contracts\Container\ItemContract;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container implements ContainerContract
{
    /**
     * Internal container holder
     *
     * @var Item[]
     */
    protected $_container = [];

    /**
     * Tags holder
     *
     * @var array
     */
    protected $_tags = [];

    /**
     * Callbacks manager holder
     *
     * @var CallbackManagerContract
     */
    protected $_callbacks;

    /**
     * {@inheritdoc}
     */
    public function bind(string $alias, $item, bool $share = false)
    {
        if (!$this->has($alias)) {
            $this->_container[$alias] = $this->_createContainerItem($item, $share, $alias);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Item "%s" already exists in container', $alias
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function share(string $alias, $item)
    {
        $this->bind($alias, $item, true);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $alias): bool
    {
        return array_key_exists($alias, $this->_container);
    }

    /**
     * {@inheritdoc}
     */
    public function call($method, array $arguments = [])
    {
        return $this->_createContainerItem($method)->call($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $alias, array $arguments = [])
    {
        if ($this->has($alias)) {
            return $this->_container[$alias]->resolve($arguments);
        }
        
        return $this->_createContainerItem($alias, false, $alias)->resolve($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, array $arguments = [])
    {
        return $this->make($id, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $alias, string $containerItem)
    {
        if ($this->has($alias)) {
            throw new \InvalidArgumentException(sprintf(
                'Alias "%s" is already registered',
                $alias
            ));
        }

        if (!$this->has($containerItem)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" can not be aliased. Item does not exist in container',
                $containerItem
            ));
        }

        $this->_container[$alias] = $this->_container[$containerItem];
    }

    /**
     * {@inheritdoc}
     */
    public function tag(array $items, string $tag)
    {
        if (!array_key_exists($tag, $this->_tags)) {
            $this->_tags[$tag] = [];
        }

        $this->_tags[$tag] += $items;
    }

    /**
     * {@inheritdoc}
     */
    public function tagged(string $tag): array
    {
        if (array_key_exists($tag, $this->_tags)) {
            $results = [];

            foreach ($this->_tags[$tag] as $item) {
                array_push($results, $this->make($item));
            }

            return $results;
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function resolving(string $className, \Closure $callback)
    {
        $this->_getCallbacksManager()->resolving($className, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function resolved(string $className, \Closure $callback)
    {
        $this->_getCallbacksManager()->resolved($className, $callback);
    }

    /**
     * Helper function for creating container item
     *
     * @param  mixed $item
     * @param  bool $share
     * @param  string $alias
     * @return ItemContract
     */
    protected function _createContainerItem($item, bool $share = false, string $alias = null): ItemContract
    {
        $item = new Item($item, $share, $alias);
        $item->setCallbacksManager($this->_getCallbacksManager());

        return $item;
    }

    /**
     * Returns callbacks manager instance
     *
     * @return CallbackManagerContract
     */
    protected function _getCallbacksManager(): CallbackManagerContract
    {
        if ($this->_callbacks === null) {
            $this->_callbacks = new Manager;
        }

        return $this->_callbacks;
    }
}