<?php

namespace stub\Venta\Container;

use stdClass;

class StubBar
{
    /**
     * @var stdClass
     */
    private $dependency;

    /**
     * StubBar constructor.
     *
     * @param stdClass $dependency
     */
    public function __construct(stdClass $dependency)
    {
        $this->dependency = $dependency;
    }
}