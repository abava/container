<?php declare(strict_types = 1);

namespace Abava\Container;

use Abava\Container\Contract\Container as ContainerContract;
use Abava\Container\Exception\ContainerException;
use Abava\Container\Exception\NotFoundException;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Class Container
 *
 * @package Abava\Container
 */
class Container implements ContainerContract
{
    /**
     * Globally available container instance.
     *
     * @var ContainerContract
     */
    protected static $instance;

    /**
     * Array of container entry aliases.
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Array of callable definitions.
     *
     * @var callable[]
     */
    protected $callableDefinitions = [];

    /**
     * Array of class definitions.
     *
     * @var string[]
     */
    protected $classDefinitions = [];

    /**
     * Array of container entry callable factories.
     *
     * @var Closure[]
     */
    protected $factories = [];

    /**
     * Array of container entries inflections. A list of methods with arguments
     * which must be called on object after instantiation.
     *
     * @var string[][]
     */
    protected $inflections = [];

    /**
     * Array of resolved instances.
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * Array of container entry identifiers.
     *
     * @var string[]
     */
    protected $keys = [];

    /**
     * Array of instances identifiers marked as shared.
     * Such instances will be instantiated once and returned on consecutive gets.
     *
     * @var bool[]
     */
    protected $shared = [];

    /**
     * Get container instance.
     *
     * @return ContainerContract
     */
    public static function getInstance(): ContainerContract
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * @inheritDoc
     */
    public function alias(string $id, string $alias)
    {
        $this->validateAlias($alias);
        $this->addAlias($id, $alias);
    }

    /**
     * Apply inflections on subject object.
     *
     * @param $object
     * @return mixed
     */
    public function applyInflections($object)
    {
        foreach ($this->inflections as $type => $methods) {
            if (!$object instanceof $type) {
                continue;
            }

            /**
             * @var string $method
             * @var array|Closure $inflection
             */
            foreach ($methods as $method => $inflection) {
                if (is_array($inflection)) {
                    // $inflection contains array of arguments to be passed
                    // wrapping protected methods calls with closure
                    // to bind them to inflection factory with variadic context
                    $reflect = function ($callable) {
                        return $this->createReflector($callable);
                    };
                    $resolve = function ($reflection) {
                        return $this->createResolver($reflection);
                    };
                    $inflection = function () use ($inflection, $type, $method, $reflect, $resolve) {
                        $this->$method(...($resolve($reflect([$type, $method])))($inflection));
                    };
                    // replace array with prepared factory
                    $this->inflections[$type][$method] = $inflection;
                }
                // factory is ready for re-use, we need only to swap context to provided object
                $inflection->call($object);
            }
        }

        return $object;
    }

    /**
     * @inheritDoc
     * @param callable|string $callable Callable to call OR class name to instantiate and invoke
     */
    public function call($callable, array $arguments = [])
    {
        if (is_string($callable) && method_exists($callable, '__invoke')) {
            // we have not instantiated invokable class here
            $callable = [$callable, '__invoke'];
        } elseif (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf("Callable expected, '%s' is given.", gettype($callable)));
        }

        return ($this->createFactoryFromCallable($callable))($arguments);
    }

    /**
     * @inheritDoc
     */
    public function get($id, array $arguments = [])
    {
        $id = $this->resolveAlias($id);
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Unable to resolve "%s"', $id));
        }

        $id = $this->normalize($id);

        // Check shared instances first
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Create instance factory closure
        if (!isset($this->factories[$id])) {
            $this->factories[$id] = $this->createEntryFactory($id);
        }

        // Instantiate resolved object and apply inflections
        $object = $this->factories[$id]($arguments);
        $this->applyInflections($object);

        // Cache shared instances
        if (isset($this->shared[$id])) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return $this->isResolvable($id);
    }

    /**
     * @inheritDoc
     */
    public function inflect(string $id, string $method, array $arguments = [])
    {
        $this->validateId($id);
        if (!method_exists($id, $method)) {
            throw new ContainerException(sprintf('Method "%s" not found in "%s"', $method, $id));
        }

        $this->addInflection($id, $method, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function set(string $id, $entry, array $aliases = [])
    {
        $this->validateId($id);
        foreach ($aliases as $alias) {
            $this->validateAlias($alias);
        }

        $this->setDefinition($id, $entry);
        foreach ($aliases as $alias) {
            $this->addAlias($id, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $id, $entry, array $aliases = [])
    {
        $this->set($id, $entry, $aliases);
        $this->shared[$this->normalize($id)] = true;
    }

    /**
     * Store entry alias to the list.
     *
     * @param string $id
     * @param string $alias
     */
    protected function addAlias(string $id, string $alias)
    {
        $this->aliases[$this->normalize($id)] = $this->normalize($alias);
    }

    /**
     * Add entry inflection to the list.
     *
     * @param string $id
     * @param string $method
     * @param array $arguments
     */
    protected function addInflection(string $id, string $method, array $arguments = [])
    {
        $this->inflections[$this->normalize($id)][$method] = $arguments;
    }

    /**
     * Create callable factory for the subject entry.
     *
     * @param string $id
     * @return Closure
     */
    protected function createEntryFactory(string $id): Closure
    {
        if (isset($this->callableDefinitions[$id])) {
            return $this->createFactoryFromCallable($this->callableDefinitions[$id]);
        }

        return $this->createFactoryFromClass($this->classDefinitions[$id] ?? $id);
    }

    /**
     * Create callable factory with resolved arguments from callable.
     *
     * @param callable $callable
     * @return Closure
     */
    protected function createFactoryFromCallable($callable): Closure
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if ($this->isConcrete($callable)) {
            $callable = [$callable, '__invoke'];
        }

        if (is_array($callable)) {

            return function (array $arguments = []) use ($callable) {
                $reflection = $this->createReflector($callable);
                if ($reflection->isStatic()) {
                    $object = null;
                } elseif (is_string($callable[0])) {
                    $object = $this->get($callable[0]);
                } else {
                    $object = $callable[0];
                }

                return $reflection->invokeArgs($object, ($this->createResolver($reflection))($arguments));
            };
        }

        return function (array $arguments = []) use ($callable) {
            return $callable(...($this->createResolver($this->createReflector($callable)))($arguments));
        };
    }

    /**
     * Create callable factory with resolved arguments from class name.
     *
     * @param string $className
     * @return Closure
     */
    protected function createFactoryFromClass(string $className): Closure
    {
        $constructor = (new ReflectionClass($className))->getConstructor();
        $argumentResolver = ($constructor && $constructor->getNumberOfParameters())
            ? $this->createResolver($constructor)
            : null;

        return function (array $args = []) use ($className, $argumentResolver) {
            $object = $argumentResolver ? new $className(...$argumentResolver($args)) : new $className();

            return $object;
        };
    }

    /**
     * Create argument resolver closure for subject function.
     *
     * @param ReflectionFunctionAbstract $function
     * @return Closure
     */
    protected function createResolver(ReflectionFunctionAbstract $function): Closure
    {
        return function (array $arguments = []) use ($function) {

            return array_map(function (ReflectionParameter $parameter) use ($arguments, $function) {

                // If passed use argument instead of reflected parameter
                $name = $parameter->getName();
                if (array_key_exists($name, $arguments)) {
                    return $arguments[$name];
                }

                // Recursively resolve function arguments
                $class = $parameter->getClass();
                if ($class !== null) {
                    return $this->get($class->getName());
                }

                // Use argument default value if defined
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                throw new ContainerException(sprintf(
                    'Unable to resolve parameter "%s" value for "%s" function (method)', $name, $function->getName()
                ));

            }, $function->getParameters());
        };
    }

    /**
     * Create reflector depending on callable type.
     *
     * @param callable|string|array $callable
     * @return ReflectionFunction|ReflectionMethod|ReflectionFunctionAbstract
     */
    protected function createReflector($callable): ReflectionFunctionAbstract
    {
        return is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);
    }

    /**
     * Check if subject entry is a closure.
     *
     * @param $entry
     * @return bool
     */
    protected function isClosure($entry): bool
    {
        return $entry instanceof Closure;
    }

    /**
     * Check if subject entry is an object instance.
     *
     * @param mixed $entry
     * @return bool
     */
    protected function isConcrete($entry): bool
    {
        return is_object($entry) && !$this->isClosure($entry);
    }

    /**
     * Check if container can resolve the entry with subject identifier.
     *
     * @param string $id
     * @return bool
     */
    protected function isResolvable(string $id): bool
    {
        return isset($this->keys[$this->normalize($id)]) || class_exists($id);
    }

    /**
     * Check if alias value is has not been already used for another entry.
     *
     * @param string $alias
     * @return bool
     */
    protected function isValidAlias(string $alias): bool
    {
        return !isset($this->aliases[$this->normalize($alias)]);
    }

    /**
     * Normalize key to use across container.
     *
     * @param  string $id
     * @return string
     */
    protected function normalize(string $id): string
    {
        return strtolower(ltrim($id, '\\'));
    }

    /**
     * Check whether subject identifier is an alias and return the referenced definition.
     *
     * @param string $id Entry id.
     * @return string
     */
    protected function resolveAlias(string $id): string
    {
        return $this->aliases[$this->normalize($id)] ?? $id;
    }

    /**
     * Set new container entry definition.
     *
     * @param string $id
     * @param $entry
     * @throws ContainerException
     */
    protected function setDefinition(string $id, $entry)
    {
        $id = $this->normalize($id);

        if (is_callable($entry)) {
            $this->callableDefinitions[$id] = $entry;

        } elseif (is_string($entry)) {

            if (!class_exists($entry)) {
                throw new ContainerException(sprintf('Class "%s" does not exist', $entry));
            }
            $this->classDefinitions[$id] = $entry;

        } elseif ($this->isConcrete($entry)) {
            $this->instances[$id] = $entry;
            $this->shared[$id] = true;
        } else {
            throw new ContainerException(sprintf('Invalid entry "%s" type', $id));
        }

        $this->keys[$id] = true;
    }

    /**
     * Validate entry alias. Throw an Exception in case of invalid value.
     *
     * @param string $alias
     * @throws ContainerException
     */
    protected function validateAlias($alias)
    {
        if (!$this->isValidAlias($alias)) {
            throw new ContainerException(sprintf('Invalid alias "%s"', $alias));
        }
    }

    /**
     * Validate entry identifier. Throw an Exception in case of invalid value.
     *
     * @param string $id
     * @return void
     * @throws ContainerException
     */
    protected function validateId(string $id)
    {
        if (!interface_exists($id) && !class_exists($id)) {
            throw new ContainerException(
                sprintf('Invalid id "%s". Container entry id must be an existing interface or class name.', $id)
            );
        }
    }

    /**
     * Forbid container cloning
     */
    private function __clone()
    {
    }

}
