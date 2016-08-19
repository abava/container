<?php declare(strict_types = 1);

namespace Abava\Container\Exception;

use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;
use RuntimeException;

/**
 * Class ContainerException
 *
 * @package Abava\Container\Exception
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}