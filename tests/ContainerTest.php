<?php

use PHPUnit\Framework\TestCase;

/**
 * Class ContainerTest
 */
class ContainerTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function canGetSelfInstance()
    {
        $this->assertInstanceOf(\Abava\Container\Contract\Container::class, \Abava\Container\Container::getInstance());
    }

    /**
     * @test
     */
    public function isSingleton()
    {
        $this->assertSame(\Abava\Container\Container::getInstance(), \Abava\Container\Container::getInstance());
    }

    /**
     * @test
     */
    public function canCheckEntryIsResolvable()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, TestClass::class);

        $this->assertTrue($container->has(TestClassContract::class));
        $this->assertTrue($container->has(stdClass::class));
        $this->assertFalse($container->has('UnknownInterface'));
    }

    /**
     * @test
     */
    public function canResolveClassWithConstructorParameters()
    {
        $container = new Abava\Container\Container;

        $this->assertInstanceOf(
            'SimpleConstructorParametersClass',
            $container->get('SimpleConstructorParametersClass')
        );
        $this->assertInstanceOf(stdClass::class, $container->get('SimpleConstructorParametersClass')->getItem());
    }

    /**
     * @test
     */
    public function canResolveFromClassName()
    {
        $container = new Abava\Container\Container;

        $this->assertInstanceOf(stdClass::class, $container->get('\stdClass'));
        $this->assertInstanceOf(stdClass::class, $container->get('stdClass'));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethod()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 'TestClassFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameStaticMethod()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 'TestClassFactory::staticCreate');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromAbstractClassNameStaticMethod()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 'StaticTestFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethodArray()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, [TestClassFactory::class, 'create']);

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromFunctionName()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 'createTestClass');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClosure()
    {
        $container = new Abava\Container\Container;
        $container->set(stdClass::class, function () {
            return new stdClass;
        });

        $this->assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    /**
     * @test
     */
    public function canResolveFromClosureWithArguments()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, function (TestClass $class) {
            return $class;
        });

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromInvokableObject()
    {
        $factory = Mockery::mock(TestClassFactory::class)
                          ->shouldReceive('__invoke')
                          ->withNoArgs()
                          ->andReturn(Mockery::mock(TestClassContract::class))
                          ->once()
                          ->getMock();

        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, $factory);
        $container->get(TestClassContract::class);
    }

    /**
     * @test
     */
    public function canResolveFromObjectMethodArray()
    {
        $factory = Mockery::mock(TestClassFactory::class)
                          ->shouldReceive('create')
                          ->withNoArgs()
                          ->andReturn(Mockery::mock(TestClassContract::class))
                          ->once()
                          ->getMock();

        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, [$factory, 'create']);
        $container->get(TestClassContract::class);
    }


    /**
     * @test
     */
    public function canResolveInstance()
    {
        $container = new Abava\Container\Container;
        $container->set(Abava\Container\Contract\Container::class, $this);

        $this->assertSame($this, $container->get(Abava\Container\Contract\Container::class));
    }

    /**
     * @test
     */
    public function canResolveSingletonFromClosure()
    {
        $container = new Abava\Container\Container;
        $container->singleton(stdClass::class, function () {
            return new stdClass;
        });

        $this->assertSame($container->get(stdClass::class), $container->get(stdClass::class));
    }

    /**
     * @test
     */
    public function canCallCallable()
    {
        $container = new Abava\Container\Container;
        $this->assertInstanceOf(TestClassContract::class, $container->call('createTestClass'));
    }

    /**
     * @test
     */
    public function canCallInvokableClassName()
    {
        $container = new Abava\Container\Container;
        $this->assertInstanceOf(TestClassContract::class, $container->call('TestClassFactory'));
    }

    /**
     * @test
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function throwsNotFoundExceptionIfNotResolvable()
    {
        $container = new Abava\Container\Container;
        $container->get(TestClassContract::class);
    }

}

class SimpleConstructorParametersClass
{
    protected $integer;

    protected $item;

    public function __construct(stdClass $item, int $integer = 0)
    {
        $this->item = $item;
        $this->integer = $integer;
    }

    public function getInteger()
    {
        return $this->integer;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setStdClass(stdClass $item)
    {
        return $item;
    }
}

interface TestClassContract
{
}


class TestClass implements TestClassContract
{
    protected $dependency;

    protected $value;

    public function __construct(stdClass $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}


class TestClassFactory
{
    protected $dependency;

    public function __construct(stdClass $dependency)
    {
        $this->dependency = $dependency;
    }

    public static function staticCreate()
    {
        return new TestClass(new stdClass());
    }

    function __invoke()
    {
        return new TestClass($this->dependency);
    }

    public function create()
    {
        return new TestClass($this->dependency);
    }
}

function createTestClass(stdClass $dependency)
{
    return new TestClass($dependency);
}

abstract class StaticTestFactory
{

    public static function create(stdClass $dependency)
    {
        return new TestClass($dependency);
    }

}