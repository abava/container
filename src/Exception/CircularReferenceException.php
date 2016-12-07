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
        // Adding the last dependency to the reflection path.
        $this->referenceChain[] = $this->serviceId;
        return sprintf(
            'Circular reference detected for "%s", path: "%s".',
            $this->serviceId,
            implode(' -> ', $this->referenceChain)
        );
    }

}