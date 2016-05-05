<?php declare(strict_types = 1);

namespace Venta\Container\Callback;

use Closure;
use Ds\Map;
use Ds\PriorityQueue;
use Venta\Contracts\Container\CallbackManagerContract;
use Venta\Contracts\Container\ItemContract;

/**
 * Class Manager
 *
 * @package Venta\Container
 */
class Manager implements CallbackManagerContract
{
    /**
     * Holder of all callback queues
     *
     * @var array
     */
    protected $_queues = [];

    /**
     * {@inheritdoc}
     */
    public function resolving(string $alias, Closure $callback)
    {
        $this->_pushToQueue('resolving', $alias, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function resolved(string $alias, Closure $callback)
    {
        $this->_pushToQueue('resolved', $alias, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function fireCallbacks($resolving, string $alias)
    {
        $resolved = $this->_fireResolvingCallback($resolving, $alias);
        $this->_fireResolvedCallback($resolved, $alias);

        return $resolved;
    }

    /**
     * Fires resolving callback for passed in item
     *
     * @param  mixed $resolving
     * @param  string $alias
     * @return mixed
     */
    protected function _fireResolvingCallback($resolving, string $alias)
    {
        if ($this->_hasCallbacks('resolving', $alias)) {
            /** @var PriorityQueue $callbacks */
            $callbacks = $this->_queues['resolving'][$alias];

            foreach ($callbacks->toArray() as $callback) {
                $changed = $callback($resolving);

                if ($changed === null) {
                    $changed = $resolving;
                }

                if ($this->_checkRewrite($changed, $resolving) && !($changed instanceof $resolving)) {
                    throw new \LogicException(sprintf(
                        'Class %s should extend %s in order to be rewritten',
                        get_class($changed), get_class($resolving)
                    ));
                }

                $resolving = $changed;
            }
        }

        return $resolving;
    }

    /**
     * Fires resolved callbacks for item
     *
     * @param  mixed $resolved
     * @param  string $alias
     */
    protected function _fireResolvedCallback($resolved, string $alias)
    {
        if ($this->_hasCallbacks('resolved', $alias)) {
            /** @var PriorityQueue $callbacks */
            $callbacks = $this->_queues['resolved'][$alias];

            foreach ($callbacks->toArray() as $callback) {
                $callback($resolved);
            }
        }
    }

    /**
     * Pushes callback to specific queue
     *
     * @param string $queueName
     * @param string $alias
     * @param \Closure $callback
     */
    protected function _pushToQueue(string $queueName, string $alias, Closure $callback)
    {
        if (!array_key_exists($queueName, $this->_queues)) {
            $this->_queues[$queueName] = new Map;
        }

        if (!$this->_queues[$queueName]->hasKey($alias)) {
            $this->_queues[$queueName]->put($alias, new PriorityQueue);
        }

        $this->_queues[$queueName]->get($alias)->push($callback, 0);
    }

    /**
     * Defines, if any callbacks exists for passed in event
     *
     * @param  string $event
     * @param  string $alias
     * @return bool
     */
    protected function _hasCallbacks(string $event, string $alias): bool
    {
        return array_key_exists($event, $this->_queues) && $this->_queues[$event]->hasKey($alias);
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