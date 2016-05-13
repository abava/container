<?php

/**
 * Class ContainerTest
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canResolveSimpleClass()
    {
        $container = new \Venta\Container\Container;

        $this->assertInstanceOf('stdClass', $container->make('stdClass'));
        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    /**
     * @test
     */
    public function canResolveClassWithConstructorParameters()
    {
        $container = new \Venta\Container\Container;

        $this->assertInstanceOf('SimpleConstructorParametersClass', $container->make('SimpleConstructorParametersClass'));
        $this->assertInstanceOf('stdClass', $container->get('SimpleConstructorParametersClass')->getItem());
    }

    /**
     * @test
     */
    public function canBindStringInstance()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', 'stdClass');
        $container->share('complex', 'SimpleConstructorParametersClass');

        $this->assertTrue($container->has('complex'));
        $this->assertFalse($container->has('non-existing'));
        $this->assertInstanceOf('stdClass', $container->get('simple'));
        $this->assertInstanceOf('SimpleConstructorParametersClass', $container->get('complex'));
        $this->assertInstanceOf('stdClass', $container->get('complex')->getItem());
        $this->assertSame($container->make('complex'), $container->get('complex'));
        $this->assertNotSame($container->make('simple'), $container->get('simple'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item "simple" already exists in container');
        $container->bind('simple', 'stdClass');
    }

    /**
     * @test
     */
    public function canBindClosure()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', function(\stdClass $item) {
            return $item;
        });
        $container->share('complex', function(SimpleConstructorParametersClass $item) {
            return $item;
        });

        $this->assertTrue($container->has('complex'));
        $this->assertInstanceOf('stdClass', $container->get('simple'));
        $this->assertInstanceOf('SimpleConstructorParametersClass', $container->get('complex'));
        $this->assertInstanceOf('stdClass', $container->get('complex')->getItem());
        $this->assertSame($container->make('complex'), $container->get('complex'));
        $this->assertNotSame($container->make('simple'), $container->get('simple'));
    }

    /**
     * @test
     */
    public function canBindInstance()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', new \stdClass);
        $container->share('complex', new SimpleConstructorParametersClass(new \stdClass));

        $this->assertTrue($container->has('complex'));
        $this->assertInstanceOf('stdClass', $container->get('simple'));
        $this->assertInstanceOf('SimpleConstructorParametersClass', $container->get('complex'));
        $this->assertInstanceOf('stdClass', $container->get('complex')->getItem());
        $this->assertSame($container->make('complex'), $container->get('complex'));
        $this->assertSame($container->make('simple'), $container->get('simple'));
    }

    /**
     * @test
     */
    public function canCallMethodOutOfContainer()
    {
        $container = new \Venta\Container\Container;

        $this->assertInstanceOf('stdClass', $container->call('SimpleConstructorParametersClass@methodInjectTest'));
        $this->assertNull($container->call('SimpleConstructorParametersClass@nonExistingMethod'));
    }

    /**
     * @test
     */
    public function canCallClosureOutOfContainer()
    {
        $container = new \Venta\Container\Container;

        $this->assertInstanceOf('stdClass', $container->call(function(\stdClass $item) {
            return $item;
        }));
    }

    /**
     * @test
     */
    public function wontCallAnythingElseExceptClosureAndString()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('42 method can not be called out of container');

        (new \Venta\Container\Container)->call(42);
    }

    /**
     * @test
     */
    public function canDoTagging()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', 'stdClass');
        $container->share('complex', 'SimpleConstructorParametersClass');
        $container->tag(['simple', 'complex'], 'test');

        $this->assertTrue(is_array($container->tagged('test')));
        $this->assertCount(2, $container->tagged('test'));
        $this->assertInstanceOf('stdClass', $container->tagged('test')[0]);
        $this->assertInstanceOf('SimpleConstructorParametersClass', $container->tagged('test')[1]);
        $this->assertSame($container->tagged('test')[1], $container->tagged('test')[1]);
        $this->assertNotSame($container->tagged('test')[0], $container->tagged('test')[0]);
        $this->assertCount(0, $container->tagged('another-test'));
    }

    /**
     * @test
     */
    public function canResolveWithManualArguments()
    {
        $container = new \Venta\Container\Container;
        $stub = new class extends \stdClass {};
        $resolved = $container->make('SimpleConstructorParametersClass', ['item' => $stub]);

        $this->assertInstanceOf('stdClass', $resolved->getItem());
        $this->assertEquals(0, $resolved->getInteger());
        $this->assertSame($stub, $resolved->getItem());
        $this->assertSame($stub, $container->call('SimpleConstructorParametersClass@methodInjectTest', ['item' => $stub]));
    }

    /**
     * @test
     */
    public function canUseTrait()
    {
        $container = new \Venta\Container\Container;
        $instance = new class { use \Venta\Container\Traits\ContainerAwareTrait; };

        $this->assertInstanceOf(\Venta\Contracts\Container\ContainerContract::class, $instance->getContainer());
        $instance->setContainer($container);
        $this->assertSame($container, $instance->getContainer());
    }

    /**
     * @test
     */
    public function canRunResolvingCallbacks()
    {
        $container = new \Venta\Container\Container;

        $container->resolving(\stdClass::class, function() {});
        $container->resolving(\stdClass::class, function() {
            return new RewriteTestClass;
        });

        $this->assertInstanceOf(RewriteTestClass::class, $container->make('stdClass'));
    }

    /**
     * @test
     */
    public function willFailOnWrongRewrite()
    {
        $container = new \Venta\Container\Container;

        $container->resolving(\stdClass::class, function() {
            return new \Venta\Container\Container;
        });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Class %s should extend %s in order to be rewritten',
            \Venta\Container\Container::class, stdClass::class
        ));

        $container->make('stdClass');
    }

    /**
     * @test
     */
    public function canFireResolvedCallbacks()
    {
        $container = new \Venta\Container\Container;

        $container->resolved(RewriteTestClass::class, function($object) {
            $object->setValue('value');
        });

        $this->assertInstanceOf('RewriteTestClass', $container->make('RewriteTestClass'));
        $this->assertEquals('value', $container->make('RewriteTestClass')->getValue());
    }

    /**
     * @test
     */
    public function canAliasItems()
    {
        $container = new \Venta\Container\Container;

        $container->share('simple', 'stdClass');
        $container->bind('singleton', 'stdClass');

        $container->alias('alias', 'simple');
        $container->alias('new-name', 'singleton');

        $this->assertSame($container->make('simple'), $container->make('alias'));
        $this->assertNotSame($container->make('new-name'), $container->make('singleton'));
    }

    /**
     * @test
     */
    public function canCheckIfItemExistBeforeAliasing()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', 'stdClass');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"no-item" can not be aliased. Item does not exist in container');
        $container->alias('no-name', 'no-item');
    }

    /**
     * @test
     */
    public function canCheckIfAliasExistBeforeAliasing()
    {
        $container = new \Venta\Container\Container;

        $container->bind('simple', 'stdClass');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias "simple" is already registered');
        $container->alias('simple', 'no-item');
    }
}