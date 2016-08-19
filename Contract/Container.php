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
     * Add an entry to the container
     *
     * @param string $id Container entry identifier
     * @param mixed $entry
     * @param array $aliases
     * @return void
     */
    public function set(string $id, $entry, array $aliases = []);

    /**
     * Add shared instance to container
     *
     * @param string $id Container entry identifier
     * @param mixed $entry
     * @param array $aliases
     * @return void
     */
    public function singleton(string $id, $entry, array $aliases = []);

    /**
     * Add alias for container entry
     *
     * @param string $id
     * @param string|string[] $alias
     * @return void
     */
    public function alias(string $id, $alias);

    /**
     * Register method to be called after entry instantiation
     *
     * @param string $id
     * @param string $method
     * @param array $args
     * @return void
     */
    public function inflect(string $id, string $method, array $args = []);

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function factory(string $id, callable $factory);

    /**
     * @inheritDoc
     * @param array $args
     */
    public function get($id, array $args = []);
}