<?php

if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    require __DIR__ . '/../vendor/autoload.php';
}

interface TestClassContract
{
}

interface TestClassFactoryContract
{

    public function create(): TestClassContract;

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

function createTestClass(stdClass $dependency)
{
    return new TestClass($dependency);
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

class TestClassFactory implements TestClassFactoryContract
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

    public function create(): TestClassContract
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

abstract class StaticTestFactory
{

    public static function create(stdClass $dependency)
    {
        return new TestClass($dependency);
    }

}

class A
{
    protected $dependency;

    public function __construct(B $dependency)
    {
        $this->dependency = $dependency;
    }
}

class B
{
    protected $dependency;

    public function __construct(C $dependency)
    {
        $this->dependency = $dependency;
    }
}

class C
{
    protected $dependency;

    public function __construct(A $dependency)
    {
        $this->dependency = $dependency;
    }
}

class D
{
    protected $dependency;

    public function __construct(D $dependency)
    {
        $this->dependency = $dependency;
    }

    public function setDependency(D $dependency)
    {
        $this->dependency = $dependency;
    }
}

class E
{
    protected $dependency;

    public function setDependency(D $dependency)
    {
        $this->dependency = $dependency;
    }
}