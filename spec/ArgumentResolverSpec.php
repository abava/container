<?php

namespace spec\Venta\Container;

use Venta\Container\ArgumentResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Venta\Contracts\Container\ArgumentResolver as ArgumentResolverContract;
use Venta\Contracts\Container\Container;

class ArgumentResolverSpec extends ObjectBehavior
{
    function let(Container $container)
    {
        $this->beConstructedWith($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ArgumentResolver::class);
        $this->shouldImplement(ArgumentResolverContract::class);
    }
}
