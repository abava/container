<?php

use Venta\Container\ContainerAwareTrait;
use Venta\Container\Contract\Container;
use PHPUnit\Framework\TestCase;

class ContainerAwareTraitTest extends TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function canSetContainer()
    {
        $class = new class
        {
            use ContainerAwareTrait;

            public function getContainer()
            {
                return $this->container;
            }
        };

        $container = Mockery::mock(Container::class);
        $class->setContainer($container);

        $this->assertSame($container, $class->getContainer());
    }

}
