<?php

namespace spec\Venta\Container;

use PhpSpec\ObjectBehavior;
use Venta\Container\ServiceInflector;
use Venta\Contracts\Container\ArgumentResolver;
use Venta\Contracts\Container\ServiceInflector as ServiceInflectorContract;

class ServiceInflectorSpec extends ObjectBehavior
{
    function let(ArgumentResolver $resolver)
    {
        $this->beConstructedWith($resolver);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ServiceInflector::class);
        $this->shouldImplement(ServiceInflectorContract::class);
    }
}
