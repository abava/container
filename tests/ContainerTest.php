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
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromInvokableClassName()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassFactory::class, TestClassFactory::class);

        $this->assertInstanceOf(TestClassFactory::class, $container->get(TestClassFactory::class));
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
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }


    /**
     * @test
     */
    public function canResolveInstanceAsShared()
    {
        $container = new Abava\Container\Container;
        $container->set(Abava\Container\Contract\Container::class, $this);

        $this->assertSame($this, $container->get(Abava\Container\Contract\Container::class));
        $this->assertSame(
            $container->get(Abava\Container\Contract\Container::class),
            $container->get(Abava\Container\Contract\Container::class)
        );
    }

    /**
     * @test
     */
    public function canResolveSharedFromClosure()
    {
        $container = new Abava\Container\Container;
        $container->share(stdClass::class, function () {
            return new stdClass;
        });

        $this->assertSame($container->get(stdClass::class), $container->get(stdClass::class));
    }

    /**
     * @test
     */
    public function canCallCallableFunctionName()
    {
        $container = new Abava\Container\Container;
        $this->assertInstanceOf(TestClassContract::class, $container->call('createTestClass'));
    }

    /**
     * @test
     */
    public function canCallClassNameMethod()
    {
        $container = new Abava\Container\Container;
        $result = $container->call('TestClassFactory::createAndSetValue');

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodStatically()
    {
        $container = new Abava\Container\Container;

        $this->assertInstanceOf(TestClassContract::class, $container->call('StaticTestFactory::create'));
    }

    /**
     * @test
     */
    public function canCallClassNameMethodFromArray()
    {
        $container = new Abava\Container\Container;
        $result = $container->call(['TestClassFactory', 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallObjectMethodFromArrayCallable()
    {
        $container = new Abava\Container\Container;
        $result = $container->call([new TestClassFactory(new stdClass()), 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClosure()
    {
        $container = new Abava\Container\Container;
        $object = new stdClass();
        $object->key = 'value';
        $container->set(stdClass::class, $object);
        $result = $container->call(function (stdClass $dependency) {
            return $dependency->key;
        });

        $this->assertSame('value', $result);
    }

    /**
     * @test
     */
    public function canCallInvokableObject()
    {
        $container = new Abava\Container\Container;
        $invokable = new TestClassFactory(new stdClass());
        $result = $container->call($invokable);

        $this->assertInstanceOf(TestClassContract::class, $result);
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
     */
    public function canApplyInflectionsOnGet()
    {
        $container = new Abava\Container\Container;
        $container->inflect(TestClass::class, 'setValue', ['value' => 42]);
        $result = $container->get(TestClass::class);

        $this->assertSame(42, $result->getValue());
    }

    /**
     * @test
     */
    public function canApplyInflectionsOnProvidedInstance()
    {
        $container = new Abava\Container\Container;
        $container->inflect(TestClass::class, 'setValue', ['value' => 42]);
        $test = new TestClass(new stdClass());
        $container->applyInflections($test);

        $this->assertSame(42, $test->getValue());
    }

    /**
     * @test
     */
    public function canApplyInflectionsWithResolvedArgs()
    {
        $container = new Abava\Container\Container;
        $container->inflect(SimpleConstructorParametersClass::class, 'setStdClass');
        $container->set(stdClass::class, new stdClass());
        $object = new SimpleConstructorParametersClass(new stdClass());
        $container->applyInflections($object);

        $this->assertSame($container->get(stdClass::class), $object->getItem());
    }

    /**
     * @test
     */
    public function canApplyInflectionsOnManyInstances()
    {
        $container = new Abava\Container\Container;
        $container->inflect(TestClass::class, 'setValue', ['value' => 42]);
        $test1 = $container->get(TestClass::class);
        $test2 = $container->get(TestClass::class);
        $test3 = $container->get(TestClass::class);

        $this->assertSame(42, $test1->getValue());
        $this->assertSame(42, $test2->getValue());
        $this->assertSame(42, $test3->getValue());
    }

    /**
     * @test
     */
    public function canAlias()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, TestClass::class);
        $container->alias(TestClassContract::class, 'test');
        $container->alias(TestClassContract::class, 'alias');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
        $this->assertInstanceOf(TestClassContract::class, $container->get('test'));
        $this->assertInstanceOf(TestClassContract::class, $container->get('alias'));
    }

    /**
     * @test
     */
    public function canSetWithAlias()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, TestClass::class, ['test', 'alias']);

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
        $this->assertInstanceOf(TestClassContract::class, $container->get('test'));
        $this->assertInstanceOf(TestClassContract::class, $container->get('alias'));
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

    /**
     * @test
     * @expectedException \Interop\Container\Exception\ContainerException
     */
    public function throwsContainerExceptionIfCantResolve()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, function ($someUnresolvableDependency) {});
        $container->get(TestClassContract::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfCallingNotCallable()
    {
        $container = new Abava\Container\Container;
        $container->call(42);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfInflectionMethodDoesNotExist()
    {
        $container = new Abava\Container\Container;
        $container->inflect(TestClass::class, 'unknownMethod');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfAliasIsInvalid()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, TestClass::class, ['test']);
        $container->alias(stdClass::class, 'test');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfIdIsInvalid()
    {
        $container = new Abava\Container\Container;
        $container->set('Some unknown interface', TestClass::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfEntryIsInvalid()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 42);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfEntryClassNameIsInvalid()
    {
        $container = new Abava\Container\Container;
        $container->set(TestClassContract::class, 'Some unknown class');
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
        $this->item = $item;
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

    public function createAndSetValue(stdClass $value)
    {
        $test = $this->create();
        $test->setValue($value);

        return $test;
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