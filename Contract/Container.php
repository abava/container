<?php declare(strict_types = 1);

namespace Venta\Container\Contract;

use Interop\Container\ContainerInterface;

/**
 * Interface Container
 *
 * @package Venta\Container\Contracts
 */
interface Container extends ContainerInterface
{
    /**
     * Add alias for container service.
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
     * Register method to be called after service instantiation.
     *
     * @param string $id
     * @param string $method
     * @param array $arguments
     * @return void
     */
    public function inflect(string $id, string $method, array $arguments = []);

    /**
     * Add an service to the container. It allows to assign multiple aliases to resolve a single definition.
     *
     * @param string $id Container service identifier.
     * @param mixed $service Container service definition.
     * @param array $aliases List of service identifier aliases container should be able to resolve by.
     * @return void
     */
    public function set(string $id, $service, array $aliases = []);

    /**
     * Add shared instance to container.
     *
     * @param string $id Container service identifier.
     * @param mixed $service
     * @param array $aliases
     * @return void
     */
    public function share(string $id, $service, array $aliases = []);
}