<?php declare(strict_types = 1);

namespace Venta\Container\Traits;

use Venta\Contracts\Container\ContainerContract;

/**
 * Class ContainerAwareTrait
 *
 * @package Venta\Container\Traits
 */
trait ContainerAwareTrait
{
    /**
     * Container holder
     *
     * @var ContainerContract
     */
    protected $_container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerContract $container)
    {
        $this->_container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ContainerContract
    {
        return $this->_container;
    }
}