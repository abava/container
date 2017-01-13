<?php

namespace spec\Venta\Container;

use Exception;
use InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use stub\Venta\Container\StubContract;
use stub\Venta\Container\StubDecorator;
use stub\Venta\Container\StubFoo;
use Venta\Container\Invokable;
use Venta\Contracts\Container\Container;
use Venta\Contracts\Container\Invoker;
use Venta\Contracts\Container\ServiceDecorator;
use Venta\Contracts\Container\ServiceInflector;

class ServiceDecoratorSpec extends ObjectBehavior
{
    function let(Container $container, ServiceInflector $inflector, Invoker $invoker, StubFoo $foo)
    {
        $container->get(StubDecorator::class, [$foo])->willReturn(new StubDecorator($foo->getWrappedObject()));
        $invoker->invoke(Argument::type(Invokable::class), Argument::type('array'))->will(function ($arguments) {
            /** @var Invokable $invokable */
            list($invokable, $args) = $arguments;
            $callback = $invokable->callable();
            return $callback(...$args);
        });
        
        $this->beConstructedWith($container, $inflector, $invoker);
    }

    function it_decorates_once(StubFoo $foo)
    {
        $this->addDecorator(StubContract::class, function(StubContract $stub){
            if ($stub instanceof StubFoo) {
                return new StubDecorator($stub);
            }
            throw new Exception(sprintf('$stub already decorated with "%s"', get_class($stub)));
        });
        $decorated = $this->decorate(StubContract::class, $foo, true);
        $decorated->shouldBeAnInstanceOf(StubDecorator::class);
        $this->decorate(StubContract::class, $decorated->getWrappedObject());
    }

    function it_decorates_with_callback(StubContract $stub)
    {
        $this->addDecorator(StubContract::class, function(StubContract $stub){
            return new StubDecorator($stub);
        });
        $this->decorate(StubContract::class, $stub)->shouldBeAnInstanceOf(StubDecorator::class);
    }

    function it_decorates_with_class(StubFoo $foo)
    {
        $this->addDecorator(StubContract::class, StubDecorator::class);
        $this->decorate(StubContract::class, $foo)->shouldBeAnInstanceOf(StubDecorator::class);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(ServiceDecorator::class);
    }

    function it_refuses_to_decorate_with_interface()
    {
        $this->shouldThrow(InvalidArgumentException::class)
            ->during('addDecorator', [StubContract::class, StubContract::class]);
    }
}
