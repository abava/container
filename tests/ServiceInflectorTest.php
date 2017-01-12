<?php

use PHPUnit\Framework\TestCase;
use Venta\Container\ServiceInflector;
use Venta\Contracts\Container\ArgumentResolver;

class ServiceInflectorTest extends TestCase
{

    /**
     * @test
     */
    public function canApplyInflections()
    {
        // Mocking argument resolver dependency.
        $resolver = Mockery::mock(ArgumentResolver::class);

        // Callback to be returned by ArgumentResolver::resolve() method.
        // Expects $arguments array to contain 'value' key to merge.
        // Will return array of arguments for TestClass::setValue() method call.
        $callback = function (ReflectionFunctionAbstract $function, array $arguments) {
            return array_values(array_merge(['value' => 'to be replaced'], $arguments));
        };

        // Defining expectations.
        $resolver->shouldReceive('resolve')
                 ->with(Mockery::type(ReflectionMethod::class), ['value' => 'value'])
                 ->andReturnUsing($callback)
                 ->once();

        // Creating inflector, setting resolver, adding inflection.
        $inflector = new ServiceInflector($resolver);
        $inflector->addInflection(TestClass::class, 'setValue', ['value' => 'value']);

        // Creating test object.
        $test = new TestClass(new stdClass());

        // At first value is empty.
        $this->assertNull($test->getValue());

        // Applying inflections.
        $inflector->inflect($test);

        // Now value was changed via TestClass::setValue() call.
        $this->assertSame('value', $test->getValue());

        // After first run inflection callable must be saved for better performance.
        $test2 = new TestClass(new stdClass());
        $inflector->inflect($test2);
        $this->assertSame($test->getValue(), $test2->getValue());

        // Inflection must be called only on TestClass instances.
        $inflector->inflect(
            Mockery::mock(stdClass::class)->shouldNotReceive('setValue')->getMock()
        );

        Mockery::close();
    }

}
