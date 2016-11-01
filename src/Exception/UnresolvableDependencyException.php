<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Class ResolveException
 *
 * @package Venta\Container\Exception
 */
class UnresolvableDependencyException extends ContainerException
{

    /**
     * @inheritDoc
     * @param ArgumentResolverException $previous
     */
    public function __construct($entryId, array $referenceChain, ArgumentResolverException $previous)
    {
        parent::__construct($entryId, $referenceChain, $previous);
    }

    /**
     * @inheritDoc
     * @param ArgumentResolverException $argumentResolveException
     */
    protected function createMessage(Exception $argumentResolveException = null): string
    {
        return sprintf(
            'Unable to resolve parameter "%s" value for "%s" %s, path: "%s".',
            $this->formatParameter($argumentResolveException->getParameter()),
            $this->formatFunction($argumentResolveException->getFunction()),
            $argumentResolveException->getFunction() instanceof ReflectionMethod ? 'method' : 'function',
            implode(' -> ', $this->referenceChain)
        );
    }

    /**
     * Formats function declaration depending on method/function type.
     *
     * @param ReflectionFunctionAbstract $function
     * @return string
     */
    private function formatFunction(ReflectionFunctionAbstract $function)
    {
        return $function instanceof ReflectionMethod ?
            $function->getDeclaringClass()->getName() . '::' . $function->getName() :
            $function->getName();
    }

    /**
     * Formats parameter depending on type
     *
     * @param ReflectionParameter $parameter
     * @return string
     */
    private function formatParameter(ReflectionParameter $parameter): string
    {
        return $parameter->hasType() ?
            sprintf('%s $%s', $parameter->getType(), $parameter->getName()) :
            sprintf('$%s', $parameter->getName());
    }
}