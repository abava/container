<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;

/**
 * Class ResolveException
 *
 * @package Venta\Container\Exception
 */
class ResolveException extends ContainerException
{

    /**
     * @inheritDoc
     * @param ArgumentResolveException $previous
     */
    public function __construct($entryId, array $referenceChain, ArgumentResolveException $previous)
    {
        parent::__construct($entryId, $referenceChain, $previous);
    }

    /**
     * @inheritDoc
     * @param ArgumentResolveException $argumentResolveException
     */
    protected function createMessage(Exception $argumentResolveException = null): string
    {
        return sprintf(
            'Unable to resolve parameter "%s" in "%s" function while resolving "%s" path.',
            $argumentResolveException->getParameter()->getName(),
            $argumentResolveException->getFunction()->getName(),
            implode(' -> ', $this->referenceChain)
        );
    }

}