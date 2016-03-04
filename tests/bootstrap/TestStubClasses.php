<?php

/**
 * Interface definition to be used in container
 */
interface TestInterface {}

/**
 * Interface implementation class
 */
class TestInterfaceImplementation implements TestInterface
{
    protected $_item;

    public function __construct(\stdClass $item)
    {
        $this->_item = $item;
    }

    public function getItem()
    {
        return $this->_item;
    }
}

/**
 * Stub for testing interface injections
 */
class DIInterfaceClass
{
    protected $_app;

    public function __construct(TestInterface $app)
    {
        $this->_app = $app;
    }

    public function getApplication()
    {
        return $this->_app;
    }
}

/**
 * Stub class, used for DI testing
 */
class DIStubClass
{
    protected $_item;
    protected $_class;

    public function __construct(\stdClass $item, DIInjectedStubClass $class)
    {
        $this->_item = $item;
        $this->_class = $class;
    }

    public function defineHoursInDay(\stdClass $item)
    {
        return 24;
    }

    public function defineHoursInXDays(\stdClass $item, $days = 1)
    {
        return $this->defineHoursInDay($item) * $days;
    }

    public function getItem()
    {
        return $this->_item;
    }

    public function getClass()
    {
        return $this->_class;
    }
}

/**
 * Class with parent class for rewrite functionality testing
 */
class DIStubClassChild extends DIStubClass {}

/**
 * Stub class, used for DI testing
 */
class DIInjectedStubClass
{
    protected $_item;

    public function __construct(\stdClass $item)
    {
        $this->_item = $item;
    }

    public function getItem()
    {
        return $this->_item;
    }
}