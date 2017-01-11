<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use stdClass;
use stub\Venta\Container\StubCallable;
use Throwable;

class ReflectedCallableSpec extends ObjectBehavior
{
    function it_accepts_class_with_static_method_array()
    {
        $this->beConstructedWith([StubCallable::class, 'staticMethod']);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_class_with_method_array()
    {
        $this->beConstructedWith([StubCallable::class, 'method']);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();

        $this->beConstructedWith([StubCallable::class, 'nonExistingMethod']);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_class_with_method_string()
    {
        $this->beConstructedWith(StubCallable::class . '::method');
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_class_with_static_method_string()
    {
        $this->beConstructedWith(StubCallable::class . '::staticMethod');
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_function_name()
    {
        $this->beConstructedWith('stub\Venta\Container\stub_function');
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();

        $this->beConstructedWith('invalid_function_name');
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_invokable_class()
    {
        $this->beConstructedWith(StubCallable::class);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();

        $this->beConstructedWith(stdClass::class);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_invokable_object(StubCallable $callable, stdClass $nonCallable)
    {
        $this->beConstructedWith($callable);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();

        $this->beConstructedWith($nonCallable);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_lambdas()
    {
        $this->beConstructedWith(function () {});
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_object_with_method(StubCallable $callable)
    {
        $this->beConstructedWith([$callable, 'method']);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();

        $this->beConstructedWith([$callable, 'nonExistingMethod']);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_accepts_object_with_static_method(StubCallable $callable)
    {
        $this->beConstructedWith([$callable, 'staticMethod']);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
    }
}
