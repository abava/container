<?php

namespace stub\Venta\Container;

class StubCallable
{
    public static function staticMethod()
    {
        return true;
    }

    public function __invoke()
    {
        return true;
    }

    public function method()
    {
        return true;
    }
}