<?php declare(strict_types = 1);

namespace Venta\Container;

use Ds\PriorityQueue;
use Venta\Container\Resolver\ClosureResolver;
use Venta\Container\Resolver\StringResolver;
use Venta\Contracts\Container\ItemContract;
use Venta\Contracts\Event\EventContract;
use Venta\Contracts\Event\EventsDispatcherAwareContract;
use Venta\Event\Traits\EventDispatcherAwareTrait;

/**
 * Class Item
 *
 * @package Venta\Container
 */
class Item implements ItemContract, EventsDispatcherAwareContract
{
    use EventDispatcherAwareTrait;

    /**
     * Item holder
     *
     * @var mixed
     */
    protected $_item;

    /**
     * Item sharing flag
     *
     * @var bool
     */
    protected $_share;

    /**
     * Item string alias
     *
     * @var string
     */
    protected $_alias;

    /**
     * Resolved instance holder for shared items
     *
     * @var null|mixed
     */
    protected $_resolved;

    /**
     * {@inheritdoc}
     */
    public function __construct($item, bool $share = false, string $alias = null)
    {
        $this->_item = $item;
        $this->_share = $share;
        $this->_alias = $alias;

        $this->_resolvingCallbacks = new PriorityQueue;
        $this->_resolvedCallbacks = new PriorityQueue;

        $this->_setResolvedIfInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $arguments = [])
    {
        if ($this->_resolved !== null) {
            return $this->_resolved;
        } else {
            $resolved = null;

            if (is_string($this->_item)) {
                $resolved = $this->_resolveFromString($arguments);
            }
    
            if ($this->_isClosure()) {
                $resolved = $this->_resolveFromClosure($arguments);
            }
            
            $resolved = $this->_fireResolvingCallbacks($resolved);

            if ($this->_share) {
                $this->_resolved = $resolved;
            }

            return $resolved;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function call(array $arguments = [])
    {
        if ($this->_isClosure()) {
            return $this->_resolveFromClosure($arguments);
        }

        if (is_string($this->_item)) {
            return $this->_resolveFromString($arguments);
        }

        throw new \LogicException(sprintf(
            '%s method can not be called out of container',
            $this->_item
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvingItem()
    {
        return $this->_item;
    }

    /**
     * Sets this container item as resolved, in case already created instance is passed in
     */
    protected function _setResolvedIfInstance()
    {
        if (is_object($this->_item) && !$this->_isClosure()) {
            $this->_resolved = $this->_item;
            $this->_share = true;
        }
    }

    /**
     * Resolves item out of closure
     *
     * @param  array $arguments
     * @return mixed
     */
    protected function _resolveFromClosure(array $arguments = [])
    {
        return (new ClosureResolver)->resolve($this, $arguments);
    }

    /**
     * Resolves item from string definition
     *
     * @param  array $arguments
     * @return mixed
     */
    protected function _resolveFromString(array $arguments = [])
    {
        return (new StringResolver)->resolve($this, $arguments);
    }

    /**
     * Defines, if item is a Closure
     *
     * @return bool
     */
    protected function _isClosure(): bool
    {
        return $this->_item instanceof \Closure;
    }

    /**
     * Fires resolving callback for item
     * 
     * @param  mixed $resolvedItem
     * @return mixed
     */
    protected function _fireResolvingCallback($resolvedItem)
    {
        return $this->getEventsDispatcher()->dispatch('resolving: ' . $this->_alias, ['resolving' => $resolvedItem])
            ->getData('resolving');
    }

    /**
     * Fire resolved callbacks
     *
     * @param  mixed $resolvedItem
     */
    protected function _fireResolvedCallback($resolvedItem)
    {
        $this->getEventsDispatcher()->dispatch('resolved: ' . $this->_alias, ['resolved' => $resolvedItem]);
    }

    /**
     * Fires callbacks after/in item resolving
     *
     * @param  mixed $resolvedItem
     * @return mixed
     */
    protected function _fireResolvingCallbacks($resolvedItem)
    {
        $resolvedItem = $this->_fireResolvingCallback($resolvedItem);
        $this->_fireResolvedCallback($resolvedItem);

        return $resolvedItem;
    }
}