<?php

class ContainerTests extends \PHPUnit_Framework_TestCase
{
    public function testCanTypeHintInterface()
    {
        $container = $this->_getContainer();
        $stub = new \TestInterfaceImplementation;

        $container->share('TestInterface', $stub);
        $container->share('test.interface', 'DIInterfaceClass');

        $this->assertSame($stub, $container->make('test.interface')->getApplication());
    }

    public function testCanBindStringInstanceToContainer()
    {
        $container = $this->_getContainer();

        $container->bind('test.class', '\stdClass');

        $this->assertInternalType('object', $container->make('test.class'));
        $this->assertInstanceOf('stdClass', $container->make('test.class'));
        $this->assertNotSame($container->make('test.class'), $container->make('test.class'));
    }

    public function testCanBindStringSharedInstanceToContainer()
    {
        $container = $this->_getContainer();

        $container->share('test.class', '\stdClass');

        $this->assertSame($container->make('test.class'), $container->make('test.class'));
    }


    public function testCanResolveClosureBinding()
    {
        $container = $this->_getContainer();

        $container->bind('test.class', function(){
            return new \stdClass;
        });

        $this->assertInternalType('object', $container->make('test.class'));
        $this->assertInstanceOf('\\stdClass', $container->make('test.class'));
    }

    public function testCanBindInstanceToContainer()
    {
        $container = $this->_getContainer();

        $container->bind('test.class', new \stdClass);

        $this->assertNotNull($container->make('test.class'));
        $this->assertSame($container->make('test.class'), $container->make('test.class'));
    }

    public function testCanAutoInjectTypeHintedArguments()
    {
        $container = $this->_getContainer();

        $container->bind('test.di', 'DIStubClass');

        $this->assertInstanceOf('DIStubClass', $container->make('test.di'));
        $this->assertInstanceOf('stdClass', $container->make('test.di')->getItem());
        $this->assertInstanceOf('DIInjectedStubClass', $container->make('test.di')->getClass());
        $this->assertInstanceOf('stdClass', $container->make('test.di')->getClass()->getItem());
    }

    public function testCanResolveAndCallMethodOnTheClass()
    {
        $container = $this->_getContainer();

        $this->assertEquals(24, $container->call('DIStubClass@defineHoursInDay'));
    }

    public function testCanResolveAndCallMethodOnTheClassWithParameters()
    {
        $container = $this->_getContainer();

        $this->assertEquals(24, $container->call('DIStubClass@defineHoursInXDays'));
        $this->assertEquals(48, $container->call('DIStubClass@defineHoursInXDays', ['days' => 2]));
        $this->assertEquals(96, $container->call('DIStubClass@defineHoursInXDays', ['days' => '4']));
    }

    public function testCanCallClosureResolved()
    {
        $container = $this->_getContainer();

        $this->assertInstanceOf('\\stdClass', $container->call(function(\stdClass $item){
            return $item;
        }));
    }

    public function testCanCallClosureResolvedWithArguments()
    {
        $container = $this->_getContainer();

        $this->assertEquals(16, $container->call(function($num, \stdClass $item){
            return $num * $num;
        }, ['num' => 4]));
    }

    /**
     * @expectedException \Venta\Container\Exceptions\RewriteException
     */
    public function testCantRebindExistingDefinitionWithoutRewrite()
    {
        $container = $this->_getContainer();

        $container->bind('test.class', new \stdClass);
        $container->bind('test.class', new \stdClass);
    }

    public function testCanRewriteDefinition()
    {
        $container = $this->_getContainer();

        $container->share('test.class', 'DIStubClass');
        $container->rewrite('test.class', 'DIStubClassChild');

        $this->assertInstanceOf('DIStubClassChild', $container->make('test.class'));
        $this->assertSame($container->make('test.class'), $container->make('test.class'));
    }

    public function testCanResolveRegularClassName()
    {
        $container = $this->_getContainer();

        $this->assertInstanceOf('\stdClass', $container->make('\stdClass'));
    }

    public function testCanCallMissedMethodDefinition()
    {
        $container = $this->_getContainer();

        $this->assertInstanceOf('\stdClass', $container->call('\stdClass@'));
        $this->assertInstanceOf('\stdClass', $container->call('DIStubClass@')->getItem());
        $this->assertEquals(24, $container->call('DIStubClass@defineHoursInDay@wrong'));
    }

    public function testCanResolveInterface()
    {
        $container = $this->_getContainer();

        $container->bind('TestInterface', 'TestInterfaceImplementation');

        $this->assertInstanceOf('TestInterface', $container->make('TestInterface'));
    }

    /**
     * @expectedException \Venta\Container\Exceptions\InterfaceBindingException
     */
    public function testWontBindInterfaceImplementationIfNoImplement()
    {
        $container = $this->_getContainer();

        $container->bind('TestInterface', '\stdClass');
    }

    /**
     * @expectedException \Venta\Container\Exceptions\NotFoundException
     */
    public function testExceptionIfItemCanNotBeResolved()
    {
        $this->_getContainer()->make('non.existing.item');
    }

    /**
     * @expectedException \LogicException
     */
    public function testCantRewriteNonExistingAlias()
    {
        $container = $this->_getContainer();

        $container->rewrite('test.class', '\\stdClass');
    }

    /**
     * @expectedException \LogicException
     */
    public function testCantRewriteNonChildren()
    {
        $container = $this->_getContainer();

        $container->bind('test.class', 'DIStubClass');
        $container->rewrite('test.class', '\\stdClass');
    }

    /**
     * Returns container instance
     *
     * @return \Venta\Contracts\Container\ContainerContract
     */
    protected function _getContainer()
    {
        return new \Venta\Container\Container;
    }
}