<?php

use PHPUnit\Framework\TestCase;
use Venta\Container\ArgumentResolver;
use Venta\Contracts\Container\Container;

class ArgumentResolverTest extends TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }


    /**
     * @test
     */
    public function canReflectFunction()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $reflection = $resolver->reflectCallable(function () {
        });

        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
    }

    /**
     * @test
     */
    public function canReflectMethod()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $reflection = $resolver->reflectCallable([ArgumentResolver::class, 'reflectCallable']);

        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
    }

    /**
     * @test
     */
    public function canResolveOptionalArguments()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $function = function (string $scalar = 'reso') {
            return $scalar . 'lved';
        };
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));
        $arguments = $closure();

        $this->assertSame(['reso'], $arguments);
        $this->assertSame('resolved', $function(...$arguments));
    }

    /**
     * @test
     */
    public function canResolveWithClassArguments()
    {
        $mock = Mockery::mock(TestClassContract::class);
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('has')->with(TestClassContract::class)->andReturn(true)->once();
        $container->shouldReceive('get')->with(TestClassContract::class)->andReturn($mock)->once();

        $function = function (TestClassContract $test) {
            return $test;
        };

        $resolver = new ArgumentResolver($container);
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));

        $arguments = $closure();

        $this->assertSame([$mock], $arguments);
        $this->assertSame($mock, $function(...$arguments));
    }

    /**
     * @test
     */
    public function canResolveWithPassedArguments()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $function = function (string $scalar) {
            return $scalar . 'd';
        };
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));
        $arguments = $closure(['scalar' => 'resolve']);

        $this->assertSame(['resolve'], $arguments);
        $this->assertSame('resolved', $function(...$arguments));
    }

    /**
     * @test
     */
    public function canResolveWithoutArguments()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $function = function () {
            return 'resolved';
        };
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));
        $arguments = $closure();

        $this->assertSame([], $arguments);
        $this->assertSame('resolved', $function(...$arguments));
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\ArgumentResolveException
     * @expectedExceptionMessage test
     */
    public function failsToResolveMandatoryClassArgumentsContainerMisses()
    {
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('has')->with(TestClassContract::class)->andReturn(false)->once();
        $container->shouldNotReceive('get');

        $function = function (TestClassContract $test) {
            return $test;
        };

        $resolver = new ArgumentResolver($container);
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));

        $closure();
    }

    /**
     * @test
     *
     * @expectedException \Venta\Container\Exception\ArgumentResolveException
     * @expectedExceptionMessage scalar
     */
    public function failsToResolveMandatoryScalarArguments()
    {
        $resolver = new ArgumentResolver(Mockery::mock(Container::class));
        $function = function (string $scalar) {
            return $scalar . 'd';
        };
        $closure = $resolver->resolveArguments(new ReflectionFunction($function));
        $closure();
    }

}
