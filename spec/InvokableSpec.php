<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use ReflectionFunctionAbstract;
use stdClass;
use stub\Venta\Container\StubInvokable;
use Throwable;

class InvokableSpec extends ObjectBehavior
{
    function it_accepts_class_with_method_array()
    {
        $callable = [StubInvokable::class, 'method'];
        $this->beConstructedWith($callable);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($callable);
    }

    function it_accepts_class_with_method_string()
    {
        $this->beConstructedWith(StubInvokable::class . '::method');
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe([StubInvokable::class, 'method']);
    }

    function it_accepts_class_with_static_method_array()
    {
        $callable = [StubInvokable::class, 'staticMethod'];
        $this->beConstructedWith([StubInvokable::class, 'staticMethod']);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($callable);
    }

    function it_accepts_class_with_static_method_string()
    {
        $this->beConstructedWith(StubInvokable::class . '::staticMethod');
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe([StubInvokable::class, 'staticMethod']);
    }

    function it_accepts_closures()
    {
        $closure = function () {
        };
        $this->beConstructedWith($closure);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($closure);
    }

    function it_accepts_function_name()
    {
        $functionName = 'stub\Venta\Container\stub_function';
        $this->beConstructedWith($functionName);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($functionName);
    }

    function it_accepts_invokable_class()
    {
        $this->beConstructedWith(StubInvokable::class);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe([StubInvokable::class, '__invoke']);
    }

    function it_accepts_invokable_object(StubInvokable $callable)
    {
        $this->beConstructedWith($callable);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe([$callable, '__invoke']);
    }

    function it_accepts_object_with_method_array(StubInvokable $callable)
    {
        $callable = [$callable, 'method'];
        $this->beConstructedWith($callable);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($callable);
    }

    function it_accepts_object_with_static_method(StubInvokable $callable)
    {
        $callable = [$callable, 'staticMethod'];
        $this->beConstructedWith($callable);
        $this->shouldNotThrow(Throwable::class)->duringInstantiation();
        $this->callable()->shouldBe($callable);
    }

    function it_detects_callable_functions()
    {
        $this->beConstructedWith('stub\Venta\Container\stub_function');
        $this->isFunction()->shouldBe(true);
    }

    function it_detects_callable_objects(StubInvokable $callable)
    {
        $this->beConstructedWith($callable);
        $this->isFunction()->shouldBe(false);
    }

    function it_rejects_invalid_class_with_method_array()
    {
        $this->beConstructedWith([StubInvokable::class, 'nonExistingMethod']);
        $this->shouldThrow(Throwable::class)->duringInstantiation();

    }

    function it_rejects_invalid_function_name()
    {
        $this->beConstructedWith('invalid_function_name');
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_rejects_invalid_invokable_class()
    {
        $this->beConstructedWith(stdClass::class);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_rejects_invalid_invokable_object(stdClass $nonCallable)
    {
        $this->beConstructedWith($nonCallable);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_rejects_invalid_object_with_method_array(StubInvokable $callable)
    {
        $this->beConstructedWith([$callable, 'nonExistingMethod']);
        $this->shouldThrow(Throwable::class)->duringInstantiation();
    }

    function it_returns_reflection_function()
    {
        $functionName = 'stub\Venta\Container\stub_function';
        $this->beConstructedWith($functionName);
        $reflection = $this->reflection();
        $reflection->shouldBeAnInstanceOf(ReflectionFunctionAbstract::class);
        $reflection->getName()->shouldBe($functionName);
    }
}
