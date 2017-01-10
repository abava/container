<?php declare(strict_types = 1);

namespace Venta\Container;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Class ReflectedCallable
 *
 * @package Venta\Container
 */
class ReflectedCallable
{

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var ReflectionFunctionAbstract
     */
    private $reflection;

    /**
     * ReflectedCallable constructor.
     *
     * @param callable $callable
     */
    public function __construct($callable)
    {
        $this->callable = $this->normalizeCallable($callable);
    }

    /**
     * @return callable
     */
    public function callable()
    {
        return $this->callable;
    }

    /**
     * @return bool
     */
    public function isFunction(): bool
    {
        return !is_array($this->callable);
    }

    /**
     * @return ReflectionFunctionAbstract|ReflectionMethod|ReflectionFunction
     */
    public function reflection(): ReflectionFunctionAbstract
    {
        if (empty($this->reflection)) {
            $this->reflection = $this->isFunction()
                ? new ReflectionFunction($this->callable)
                : new ReflectionMethod($this->callable[0], $this->callable[1]);
        }

        return $this->reflection;
    }

    /**
     * @param $callable
     * @return callable
     * @throws InvalidArgumentException
     */
    private function normalizeCallable($callable)
    {
        if (is_object($callable)) {
            if ($callable instanceof Closure) {
                return $callable;
            } else {
                if (!method_exists($callable, '__invoke')) {
                    throw new InvalidArgumentException('Invalid callable provided.');
                }

                // Callable object is an instance with magic __invoke() method.
                return [$callable, '__invoke'];
            }
        }

        if (is_string($callable)) {
            // Existing function is always callable.
            if (function_exists($callable)) {
                return $callable;
            }
            if (method_exists($callable, '__invoke')) {
                // We allow to call class by name if `__invoke()` method is implemented.
                return [$callable, '__invoke'];
            }
            if (strpos($callable, '::') !== false) {
                // Replace "ClassName::methodName" string with ["ClassName", "methodName"] array.
                $callable = explode('::', $callable);
            }
        }

        // Is correct callable array.
        if (is_array($callable) && isset($callable[0], $callable[1]) && method_exists($callable[0], $callable[1])) {
            return $callable;
        }

        throw new InvalidArgumentException('Invalid callable provided.');
    }
}