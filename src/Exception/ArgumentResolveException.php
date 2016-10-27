<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;

/**
 * Class ResolveException
 *
 * @package Venta\Container\Exception
 */
class ArgumentResolveException extends RuntimeException implements ContainerExceptionInterface
{

    /**
     * @var ReflectionFunctionAbstract
     */
    private $function;

    /**
     * @var ReflectionParameter
     */
    private $parameter;

    /**
     * ArgumentResolveException constructor.
     *
     * @param ReflectionParameter $parameter
     * @param ReflectionFunctionAbstract $function
     * @param null $previous
     */
    public function __construct(ReflectionParameter $parameter, ReflectionFunctionAbstract $function, $previous = null)
    {
        $this->parameter = $parameter;
        $this->function = $function;
        parent::__construct($this->createMessage(), 0, $previous);
    }

    /**
     * @return ReflectionFunctionAbstract
     */
    public function getFunction(): ReflectionFunctionAbstract
    {
        return $this->function;
    }

    /**
     * @return ReflectionParameter
     */
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * @return string
     */
    protected function createMessage(): string
    {
        return sprintf(
            'Unable to resolve parameter "%s" value for "%s" %s.',
            $this->formatParameter($this->parameter),
            $this->formatFunction($this->function),
            $this->function instanceof ReflectionMethod ? 'method' : 'function'
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