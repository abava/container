<?php declare(strict_types = 1);

namespace Abava\Container\Contract;

use Interop\Container\ContainerInterface;

/**
 * Interface ContainerContract
 *
 * @package Abava\Container
 */
interface Container extends ContainerInterface
{
    /**
     * Add alias for container entry.
     *
     * @param string $id
     * @param string $alias
     * @return void
     */
    public function alias(string $id, string $alias);

    /**
     * Invoke a callable with resolving dependencies.
     *
     * @param $callable
     * @param array $arguments
     */
    public function call($callable, array $arguments = []);

    /**
     * {@inheritDoc}
     * @param array $arguments
     */
    public function get($id, array $arguments = []);

    /**
     * Register method to be called after entry instantiation.
     *
     * @param string $id
     * @param string $method
     * @param array $arguments
     * @return void
     */
    public function inflect(string $id, string $method, array $arguments = []);

    /**
     * Add an entry to the container. It allows to assign multiple aliases to resolve a single definition.
     *
     * @param string $id Container entry identifier.
     * @param mixed $entry Container entry definition.
     * @param array $aliases List of entry identifier aliases container should be able to resolve by.
     * @return void
     */
    public function set(string $id, $entry, array $aliases = []);

    /**
     * Add shared instance to container.
     *
     * @param string $id Container entry identifier.
     * @param mixed $entry
     * @param array $aliases
     * @return void
     */
    public function singleton(string $id, $entry, array $aliases = []);
}