<?php declare(strict_types = 1);

namespace Venta\Container;

use Venta\Contracts\Container\ContainerContract;
use Venta\Contracts\Container\ItemContract;
use Venta\Contracts\Event\EventContract;
use Venta\Contracts\Event\EventsDispatcherAwareContract;
use Venta\Event\Traits\EventDispatcherAwareTrait;

/**
 * Class Container
 *
 * @package Venta\Container
 */
class Container implements ContainerContract, EventsDispatcherAwareContract
{
    use EventDispatcherAwareTrait;

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
        $this->getEventsDispatcher()->observe('resolving: ' . $className, function(EventContract $event) use ($callback) {
            $latest = $event->getData('resolving');
            $changed = $callback($latest, $this);

            if ($changed === null) {
                $changed = $latest;
            }

            if ($this->_checkRewrite($changed, $latest) && !($changed instanceof $latest)) {
                throw new \LogicException(sprintf(
                    'Class %s should extend %s in order to be rewritten',
                    get_class($changed), get_class($latest)
                ));
            }

            $event->setData('resolving', $changed);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function resolved(string $className, \Closure $callback = null)
    {
        $this->getEventsDispatcher()->observe('resolved: ' . $className, function(EventContract $event) use ($callback) {
            $callback($event->getData('resolved'), $this);
        });
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
        $containerItem = new Item($item, $share, $alias);
        $containerItem->setEventsDispatcher($this->getEventsDispatcher());

        return $containerItem;
    }

    /**
     * Defines, if rewrite can happen
     *
     * @param  mixed $rewrite
     * @param  mixed $latest
     * @return bool
     */
    protected function _checkRewrite($rewrite, $latest)
    {
        return is_object($rewrite) && is_object($latest) && $rewrite !== $latest;
    }
}