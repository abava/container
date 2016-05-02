<?php declare(strict_types = 1);

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
     * {@inheritdoc}
     */
    public function bind(string $alias, $item, bool $share = false)
    {
        if (!$this->has($alias)) {
            $this->_container[$alias] = new Item($item, $share);
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
        return (new Item($method))->call($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $alias, array $arguments = [])
    {
        if ($this->has($alias)) {
            return $this->_container[$alias]->resolve($arguments);
        }
        
        return (new Item($alias))->resolve($arguments);
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
}