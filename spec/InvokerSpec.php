<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use ReflectionFunctionAbstract;
use stdClass;
use stub\Venta\Container\StubBar;
use stub\Venta\Container\StubContract;
use stub\Venta\Container\StubFoo;
use stub\Venta\Container\StubService;
use Venta\Contracts\Container\ArgumentResolver;
use Venta\Contracts\Container\Container;
use Venta\Contracts\Container\Invoker;

class InvokerSpec extends ObjectBehavior
{
    function let(Container $container, ArgumentResolver $resolver)
    {
        $this->beConstructedWith($container, $resolver);
    }

    function it_can_check_if_is_callable(Container $container)
    {
        $this->isCallable('strpos')->shouldBe(true);
        $this->isCallable(function(){})->shouldBe(true);
        $container->has(StubFoo::class)->willReturn(true);
        $this->isCallable([StubFoo::class, 'bar'])->shouldBe(true);
        $this->isCallable([$container, 'has'])->shouldBe(true);

        $this->isCallable(42)->shouldBe(false);
        $container->has(StubContract::class)->willReturn(false);
        $this->isCallable([StubContract::class, 'bar'])->shouldBe(false);
    }

    function it_creates_objects(ArgumentResolver $resolver)
    {
        $resolver->resolve(Argument::type(ReflectionFunctionAbstract::class), [])->willReturn([new stdClass()]);
        $this->call([StubBar::class, '__construct'])->shouldBeAnInstanceOf(StubBar::class);
    }
    
    function it_invokes_closures(ArgumentResolver $resolver)
    {
        $resolver->resolve(Argument::type(ReflectionFunctionAbstract::class), [42])->willReturn([42]);
        $this->call(function ($int) { return $int; }, [42])->shouldBe(42);
    }
    
    function it_invokes_functions(ArgumentResolver $resolver)
    {
        $resolver->resolve(Argument::type(ReflectionFunctionAbstract::class), [])->willReturn([]);
        $this->call('stub\Venta\Container\stub_function')->shouldBe(true);
    }

    function it_invokes_non_static_methods(Container $container, ArgumentResolver $resolver, StubService $stubService)
    {
        $resolver->resolve(Argument::type(ReflectionFunctionAbstract::class), [])->willReturn([]);
        $container->get(StubService::class)->willReturn($stubService);
        $stubService->baz()->willReturn('qwerty');
        $this->call([StubService::class, 'baz'])->shouldBe('qwerty');
    }

    function it_invokes_static_methods(Container $container, ArgumentResolver $resolver)
    {
        $resolver->resolve(Argument::type(ReflectionFunctionAbstract::class), [])->willReturn([]);
        $container->get(StubService::class)->shouldNotBeCalled();
        $this->call([StubService::class, 'name'])->shouldBe(StubService::class);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(Invoker::class);
    }
}
