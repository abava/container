<?php declare(strict_types = 1);


namespace Abava\Container\Exception;


use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;
use InvalidArgumentException;

/**
 * Class NotFountException
 *
 * @package Abava\Container\Exception
 */
class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}