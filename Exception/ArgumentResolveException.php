<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;
use ReflectionFunctionAbstract;
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
        parent::__construct(sprintf(
            'Unable to resolve parameter "%s" value for "%s" function (method).',
            $parameter->getName(),
            $function->getName()
        ), 0, $previous);

        $this->parameter = $parameter;
        $this->function = $function;
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

}