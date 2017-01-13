<?php

namespace stub\Venta\Container;

class StubFoo
{
    /**
     * @var StubBar
     */
    private $bar;

    /**
     * StubFoo constructor.
     *
     * @param StubBar $bar
     */
    public function __construct(StubBar $bar)
    {
        $this->bar = $bar;
    }

    public function bar(): StubBar
    {
        return $this->bar;
    }
}