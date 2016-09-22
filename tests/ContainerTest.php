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
    public function canAlias()
    {
        $container = new Venta\Container\Container;
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
    public function canApplyInflectionsOnGet()
    {
        $container = new Venta\Container\Container;
        $container->inflect(TestClass::class, 'setValue', ['value' => 42]);
        $result = $container->get(TestClass::class);

        $this->assertSame(42, $result->getValue());
    }

    /**
     * @test
     */
    public function canApplyInflectionsOnManyInstances()
    {
        $container = new Venta\Container\Container;
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
    public function canCallCallableFunctionName()
    {
        $container = new Venta\Container\Container;
        $this->assertInstanceOf(TestClassContract::class, $container->call('createTestClass'));
    }

    /**
     * @test
     */
    public function canCallClassNameMethod()
    {
        $container = new Venta\Container\Container;
        $result = $container->call('TestClassFactory::createAndSetValue');

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodFromArray()
    {
        $container = new Venta\Container\Container;
        $result = $container->call(['TestClassFactory', 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodStatically()
    {
        $container = new Venta\Container\Container;

        $this->assertInstanceOf(TestClassContract::class, $container->call('StaticTestFactory::create'));
    }

    /**
     * @test
     */
    public function canCallClosure()
    {
        $container = new Venta\Container\Container;
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
    public function canCallInvokableClassName()
    {
        $container = new Venta\Container\Container;
        $this->assertInstanceOf(TestClassContract::class, $container->call('TestClassFactory'));
    }

    /**
     * @test
     */
    public function canCallInvokableObject()
    {
        $container = new Venta\Container\Container;
        $invokable = new TestClassFactory(new stdClass());
        $result = $container->call($invokable);

        $this->assertInstanceOf(TestClassContract::class, $result);
    }

    /**
     * @test
     */
    public function canCallObjectMethodFromArrayCallable()
    {
        $container = new Venta\Container\Container;
        $result = $container->call([new TestClassFactory(new stdClass()), 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCheckEntryIsResolvable()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, TestClass::class);

        $this->assertTrue($container->has(TestClassContract::class));
        $this->assertTrue($container->has(stdClass::class));
        $this->assertFalse($container->has('UnknownInterface'));
    }

    /**
     * @test
     */
    public function canGetSelfInstance()
    {
        $this->assertInstanceOf(\Venta\Contracts\Container\Container::class, \Venta\Container\Container::getInstance());
    }

    /**
     * @test
     */
    public function canResolveClassWithConstructorParameters()
    {
        $container = new Venta\Container\Container;

        $this->assertInstanceOf(
            'SimpleConstructorParametersClass',
            $container->get('SimpleConstructorParametersClass')
        );
        $this->assertInstanceOf(stdClass::class, $container->get('SimpleConstructorParametersClass')->getItem());
    }

    /**
     * @test
     */
    public function canResolveFromAbstractClassNameStaticMethod()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 'StaticTestFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassName()
    {
        $container = new Venta\Container\Container;

        $this->assertInstanceOf(stdClass::class, $container->get('\stdClass'));
        $this->assertInstanceOf(stdClass::class, $container->get('stdClass'));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethod()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 'TestClassFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethodArray()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, [TestClassFactory::class, 'create']);

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameStaticMethod()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 'TestClassFactory::staticCreate');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClosure()
    {
        $container = new Venta\Container\Container;
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
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, function (TestClass $class) {
            return $class;
        });

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromFunctionName()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 'createTestClass');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromInvokableClassName()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassFactory::class, TestClassFactory::class);

        $this->assertInstanceOf(TestClassFactory::class, $container->get(TestClassFactory::class));
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

        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, $factory);
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
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

        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, [$factory, 'create']);
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveInstanceAsShared()
    {
        $container = new Venta\Container\Container;
        $container->set(\Venta\Contracts\Container\Container::class, $this);

        $this->assertSame($this, $container->get(\Venta\Contracts\Container\Container::class));
        $this->assertSame(
            $container->get(\Venta\Contracts\Container\Container::class),
            $container->get(\Venta\Contracts\Container\Container::class)
        );
    }

    /**
     * @test
     */
    public function canResolveSharedFromClosure()
    {
        $container = new Venta\Container\Container;
        $container->share(stdClass::class, function () {
            return new stdClass;
        });

        $this->assertSame($container->get(stdClass::class), $container->get(stdClass::class));
    }

    /**
     * @test
     */
    public function canSetWithAlias()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, TestClass::class, ['test', 'alias']);

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
        $this->assertInstanceOf(TestClassContract::class, $container->get('test'));
        $this->assertInstanceOf(TestClassContract::class, $container->get('alias'));
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksDirectCircularDependency()
    {
        $container = new Venta\Container\Container;
        $container->get(D::class);
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksIndirectCircularDependency()
    {
        $container = new Venta\Container\Container;
        $container->get(A::class);
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksInflectionCircularDependency()
    {
        $container = new Venta\Container\Container;
        $container->inflect(E::class, 'setDependency');
        $container->get(E::class);
    }

    /**
     * @test
     */
    public function isSingleton()
    {
        $this->assertSame(\Venta\Container\Container::getInstance(), \Venta\Container\Container::getInstance());
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\ResolveException
     */
    public function throwsContainerExceptionIfCantResolve()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, function ($someUnresolvableDependency) {
        });
        $container->get(TestClassContract::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfAliasIsInvalid()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, TestClass::class, ['test']);
        $container->alias(stdClass::class, 'test');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfCallingNotCallable()
    {
        $container = new Venta\Container\Container;
        $container->call(42);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfEntryClassNameIsInvalid()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 'Some unknown class');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfEntryIsInvalid()
    {
        $container = new Venta\Container\Container;
        $container->set(TestClassContract::class, 42);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfIdIsInvalid()
    {
        $container = new Venta\Container\Container;
        $container->set('Some unknown interface', TestClass::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfInflectionMethodDoesNotExist()
    {
        $container = new Venta\Container\Container;
        $container->inflect(TestClass::class, 'unknownMethod');
    }

    /**
     * @test
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function throwsNotFoundExceptionIfNotResolvable()
    {
        $container = new Venta\Container\Container;
        $container->get(TestClassContract::class);
    }
}
