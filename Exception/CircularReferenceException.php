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
     * Reference chain parameter is mandatory.
     *
     * @inheritDoc
     */
    public function __construct(string $entryId, array $referenceChain, Exception $previous = null)
    {
        parent::__construct($entryId, $referenceChain, $previous);
    }

    /**
     * @inheritDoc
     */
    protected function createMessage():string
    {
        return sprintf(
            'Circular reference detected for "%s", path: "%s".',
            $this->serviceId,
            implode(' -> ', $this->referenceChain)
        );
    }

}