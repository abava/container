<?php

namespace stub\Venta\Container;

class StubService
{
    /**
     * @var StubBar
     */
    private $bar;

    /**
     * @var string
     */
    private $baz;

    /**
     * @var StubFoo
     */
    private $foo;

    /**
     * StubService constructor.
     *
     * @param StubFoo $foo
     * @param StubBar $bar
     * @param string $baz
     */
    public function __construct(StubFoo $foo, StubBar $bar, $baz = 'default')
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }

    public static function name()
    {
        return __CLASS__;
    }

    /**
     * @return StubBar
     */
    public function bar(): StubBar
    {
        return $this->bar;
    }

    /**
     * @return string
     */
    public function baz(): string
    {
        return $this->baz;
    }

    /**
     * @return StubFoo
     */
    public function foo(): StubFoo
    {
        return $this->foo;
    }

}