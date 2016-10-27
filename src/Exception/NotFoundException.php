<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;
use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * Class NotFountException
 *
 * @package Venta\Container\Exception
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{

    /**
     * @inheritdoc
     */
    protected function createMessage(Exception $previous = null): string
    {
        return sprintf(
            'Service not found for "%s" id, path: "%s".',
            $this->serviceId,
            implode(' -> ', $this->referenceChain)
        );
    }

}