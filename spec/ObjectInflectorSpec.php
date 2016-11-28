<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use Venta\Container\ObjectInflector;
use Venta\Contracts\Container\ArgumentResolver;
use Venta\Contracts\Container\ObjectInflector as ObjectInflectorContract;

class ObjectInflectorSpec extends ObjectBehavior
{
    function let(ArgumentResolver $resolver)
    {
        $this->beConstructedWith($resolver);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ObjectInflector::class);
        $this->shouldImplement(ObjectInflectorContract::class);
    }
}
