<?php

class SimpleConstructorParametersClass
{
    public function __construct(\stdClass $item, int $integer = 0)
    {
        $this->_item = $item;
        $this->_integer = $integer;
    }

    public function getItem()
    {
        return $this->_item;
    }

    public function getInteger()
    {
        return $this->_integer;
    }

    public function methodInjectTest(\stdClass $item)
    {
        return $item;
    }
}

class RewriteTestClass extends \stdClass
{
    protected $_value;

    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function getValue()
    {
        return $this->_value;
    }
}