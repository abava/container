<?php

use PHPUnit\Framework\TestCase;
use Venta\Container\ObjectInflector;
use Venta\Contracts\Container\ArgumentResolver;

class ObjectInflectorTest extends TestCase
{

    /**
     * @test
     */
    public function canApplyInflections()
    {
        // Mocking argument resolver dependency.
        $resolver = Mockery::mock(ArgumentResolver::class);

        // Callback to be returned by ArgumentResolver::resolveArguments() method.
        // Expects $arguments array to contain 'value' key to merge.
        // Will return array of arguments for TestClass::setValue() method call.
        $callback = function (array $arguments) {
            return array_values(array_merge(['value' => 'to be replaced'], $arguments));
        };

        // Defining expectations.
        $resolver->shouldReceive('createCallback')
                 ->with(Mockery::type(ReflectionMethod::class))
                 ->andReturn($callback)
                 ->once();

        // Creating inflector, setting resolver, adding inflection.
        $inflector = new ObjectInflector($resolver);
        $inflector->addInflection(TestClass::class, 'setValue', ['value' => 'value']);

        // Creating test object.
        $test = new TestClass(new stdClass());

        // At first value is empty.
        $this->assertNull($test->getValue());

        // Applying inflections.
        $inflector->applyInflections($test);

        // Now value was changed via TestClass::setValue() call.
        $this->assertSame('value', $test->getValue());

        // After first run inflection callable must be saved for better performance.
        $test2 = new TestClass(new stdClass());
        $inflector->applyInflections($test2);
        $this->assertSame($test->getValue(), $test2->getValue());

        // Inflection must be called only on TestClass instances.
        $inflector->applyInflections(
            Mockery::mock(stdClass::class)->shouldNotReceive('setValue')->getMock()
        );

        Mockery::close();
    }

}
