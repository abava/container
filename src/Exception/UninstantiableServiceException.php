<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;

/**
 * Class UninstantiableServiceException
 *
 * @package Venta\Container\Exception
 */
class UninstantiableServiceException extends ContainerException
{
    /**
     * @inheritDoc
     */
    protected function createMessage(Exception $previous = null): string
    {
        return sprintf(
            'Unable to instantiate "%s" service, path: "%s".',
            $this->serviceId(),
            implode(' -> ', $this->referenceChain())
        );
    }
}