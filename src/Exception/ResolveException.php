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
        $referenceChain[] = $this->serviceId = $entryId;
        $this->referenceChain = $referenceChain;
        Exception::__construct($this->createMessage($previous), 0, $previous);
    }

    /**
     * @inheritDoc
     */
    protected function createMessage(ArgumentResolveException $argumentResolveException = null): string
    {
        return sprintf(
            'Unable to resolve parameter "%s" in "%s" function while resolving "%s" path.',
            $argumentResolveException->getParameter()->getName(),
            $argumentResolveException->getFunction()->getName(),
            implode(' -> ', $this->referenceChain)
        );
    }

}