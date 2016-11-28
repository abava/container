<?php

namespace spec\Venta\Container;

use Interop\Container\ContainerInterface;
use PhpSpec\ObjectBehavior;
use Venta\Container\Container;
use Venta\Contracts\Container\Container as ContainerContract;

class ContainerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Container::class);
        $this->shouldImplement(ContainerContract::class);
        $this->shouldImplement(ContainerInterface::class);
    }
}
