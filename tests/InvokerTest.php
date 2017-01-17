<?php

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Venta\Container\Exception\NotFoundException;
use Venta\Container\Invoker;
use Venta\Contracts\Container\ArgumentResolver;
use Venta\Contracts\Container\Container;

class InvokerTest extends TestCase
{

    /**
     * @var Container|MockInterface
     */
    protected $container;

    /**
     * @var Invoker
     */
    protected $invoker;

    /**
     * @var ArgumentResolver|MockInterface
     */
    protected $resolver;

    public function setUp()
    {
        $this->container = Mockery::mock(Container::class);
        $this->resolver = Mockery::mock(ArgumentResolver::class);
        $this->invoker = new Invoker($this->container, $this->resolver);
    }
    
    public function tearDown()
    {
        Mockery::close();
    }
    
    /**
     * @test
     */
    public function canCallCallableFunctionName()
    {
        $this->resolver->shouldReceive('resolve')
            ->with(Mockery::type(ReflectionFunction::class), [])
            ->andReturn([new stdClass()]);
        $this->assertInstanceOf(TestClassContract::class, $this->invoker->call('createTestClass'));
    }

    /**
     * @test
     */
    public function canCallClassNameMethod()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([new stdClass()]);
        $this->container->shouldReceive('get')->with(TestClassFactory::class)->andReturn(new TestClassFactory(new stdClass));
        $result = $this->invoker->call('TestClassFactory::createAndSetValue');

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodFromArray()
    {
        $dependency = new stdClass();
        $value = new stdClass();
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([$value]);
        $this->container->shouldReceive('get')->with('TestClassFactory')->andReturn(new TestClassFactory($dependency));

        $result = $this->invoker->call(['TestClassFactory', 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertSame($value, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodStatically()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([new stdClass()]);
        $this->assertInstanceOf(TestClassContract::class, $this->invoker->call('StaticTestFactory::create'));
    }

    /**
     * @test
     */
    public function canCallClosure()
    {
        $object = new stdClass();
        $object->key = 'value';
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionFunction::class), [])
                       ->andReturn([$object]);
        $result = $this->invoker->call(function (stdClass $dependency) {
            return $dependency->key;
        });

        $this->assertSame('value', $result);
    }

    /**
     * @test
     */
    public function canCallInterfaceMethod()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([]);
        $this->container->shouldReceive('get')
            ->with(TestClassFactoryContract::class)
            ->andReturn(new TestClassFactory(new stdClass()));

        $this->assertInstanceOf(TestClassContract::class, $this->invoker->call('TestClassFactoryContract::create'));
    }

    /**
     * @test
     */
    public function canCallInvokableClassName()
    {
        $dependency = new stdClass();
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([$dependency], []);
        $this->container->shouldReceive('get')
                        ->with(TestClassFactory::class)
                        ->andReturn(new TestClassFactory($dependency));

        $this->assertInstanceOf(TestClassContract::class, $this->invoker->call('TestClassFactory'));
    }

    /**
     * @test
     */
    public function canCallInvokableObject()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([]);
        $invokable = new TestClassFactory(new stdClass());
        $result = $this->invoker->call($invokable);

        $this->assertInstanceOf(TestClassContract::class, $result);
    }

    /**
     * @test
     */
    public function canCallObjectMethodFromArrayCallable()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([new stdClass()]);
        $result = $this->invoker->call([new TestClassFactory(new stdClass()), 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function checksIfServiceMethodIsCallable()
    {
        $this->container->shouldReceive('has')->with('TestClassFactory')->andReturn(true);
        $this->container->shouldReceive('has')->with('TestClassFactoryContract')->andReturn(false);
        $this->assertTrue($this->invoker->isCallable('TestClassFactory::create'));
        $this->assertFalse($this->invoker->isCallable('TestClassFactoryContract::create'));
    }


    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfCallingNotCallable()
    {
        $this->invoker->call(42);
    }


    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionOnInvalidCallableCall()
    {
        $this->invoker->call('SomeInvalidCallableToCall');
    }


    /**
     * @test
     * @expectedException Venta\Container\Exception\NotFoundException
     */
    public function throwsExceptionWhenCallsUnresolvableServiceMethod()
    {
        $this->resolver->shouldReceive('resolve')
                       ->with(Mockery::type(ReflectionMethod::class), [])
                       ->andReturn([]);
        $this->container->shouldReceive('get')
            ->with('TestClassFactoryContract')
            ->andThrow(new NotFoundException('TestClassFactoryContract'))
            ->once();

        $this->invoker->call('TestClassFactoryContract::create');
    }

}
