<?php

namespace stub\Venta\Container;

class StubInvokable
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