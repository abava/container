<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use ReflectionFunction;
use ReflectionMethod;
use stub\Venta\Container\StubBar;
use stub\Venta\Container\StubFoo;
use stub\Venta\Container\StubService;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container;

class ArgumentResolverSpec extends ObjectBehavior
{
    function let(Container $container, StubFoo $foo, StubBar $bar, StubService $service)
    {
        $container->has(StubFoo::class)->willReturn(true);
        $container->get(StubFoo::class)->willReturn($foo);

        $container->has(StubBar::class)->willReturn(true);
        $container->get(StubBar::class)->willReturn($bar);

        $container->has(StubService::class)->willReturn(true);
        $container->get(StubService::class)->willReturn($service);

        $this->beConstructedWith($container);
    }

    function it_can_override_argument_default_values()
    {
        $serviceConstructor = new ReflectionMethod(StubService::class, '__construct');
        $args = $this->resolve($serviceConstructor, [2 => 'override']);
        $args->shouldHaveKeyWithValue(2, 'override');
        $args->shouldNotContain('default');
    }

    function it_is_initializable()
    {
        $this->shouldImplement(ArgumentResolverContract::class);
    }

    function it_resolves_arguments_with_default_values()
    {
        $serviceConstructor = new ReflectionMethod(StubService::class, '__construct');
        $this->resolve($serviceConstructor)->shouldContain('default');
    }

    function it_resolves_class_method_dependencies(StubFoo $foo, StubBar $bar)
    {
        $serviceConstructor = new ReflectionMethod(StubService::class, '__construct');
        $args = $this->resolve($serviceConstructor);
        $args->shouldContain($foo);
        $args->shouldContain($bar);
    }

    function it_resolves_function_dependencies(StubFoo $foo)
    {
        $function = new ReflectionFunction('stub\Venta\Container\stub_foo_function');
        $this->resolve($function)->shouldContain($foo);
    }
}
