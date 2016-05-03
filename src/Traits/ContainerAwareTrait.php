<?php declare(strict_types = 1);

namespace Venta\Container\Traits;

use Venta\Container\Container;
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
        if ($this->_container === null) {
            $this->_container = new Container;
        }

        return $this->_container;
    }
}