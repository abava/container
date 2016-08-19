<?php

use PHPUnit\Framework\TestCase;

/**
 * Class ContainerTest
 */
class ContainerTest extends TestCase
{
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
    public function canCheckEntryIsResolvable()
    {
        $container = new Abava\Container\Container;
        $container->set(CallableFactoryContract::class, CallableFactoryClass::class);

        $this->assertTrue($container->has(CallableFactoryContract::class));
        $this->assertTrue($container->has(stdClass::class));
        $this->assertFalse($container->has('UnknownInterface'));
    }

    /**
     * @test
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function throwsNotFoundExceptionIfNotResolvable()
    {
        $container = new Abava\Container\Container;
        $container->get(CallableFactoryContract::class);
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
    public function canResolveClassWithConstructorParameters()
    {
        $container = new Abava\Container\Container;

        $this->assertInstanceOf(
            'SimpleConstructorParametersClass',
            $container->get('SimpleConstructorParametersClass')
        );
        $this->assertInstanceOf(stdClass::class, $container->get('SimpleConstructorParametersClass')->getItem());
    }

}

class SimpleConstructorParametersClass
{
    protected $item;
    protected $integer;

    public function __construct(stdClass $item, int $integer = 0)
    {
        $this->item = $item;
        $this->integer = $integer;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function getInteger()
    {
        return $this->integer;
    }

    public function setStdClass(stdClass $item)
    {
        return $item;
    }
}

class RewriteTestClass extends stdClass
{
    protected $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}

interface CallableFactoryContract
{
}

class CallableFactoryClass implements CallableFactoryContract
{

    function __invoke()
    {
        return new stdClass();
    }

}
