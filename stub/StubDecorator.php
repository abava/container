<?php declare(strict_types = 1);

namespace stub\Venta\Container;

/**
 * Class StubDecorator
 *
 * @package stub\Venta\Container
 */
class StubDecorator implements StubContract
{

    /**
     * @var StubContract
     */
    private $foo;

    /**
     * StubDecorator constructor.
     *
     * @param StubContract $foo
     */
    public function __construct(StubContract $foo)
    {
        $this->foo = $foo;
    }

    public function bar(): StubBar
    {
        return $this->foo->bar();
    }


}