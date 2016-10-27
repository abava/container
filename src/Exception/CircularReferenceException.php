<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;

/**
 * Class CircularReferenceException
 *
 * @package Venta\Container\Exception
 */
class CircularReferenceException extends ContainerException
{

    /**
     * @inheritDoc
     */
    protected function createMessage(Exception $previous = null):string
    {
        return sprintf(
            'Circular reference detected for "%s", path: "%s".',
            $this->serviceId,
            implode(' -> ', $this->referenceChain)
        );
    }

}