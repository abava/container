<?php declare(strict_types = 1);

namespace Abava\Container;

use Abava\Container\Contract\Container as ContainerContract;
use Abava\Container\Exception\ContainerException;
use Abava\Container\Exception\NotFoundException;
use Closure;
use ReflectionClass;
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
     * Array of container entry identifiers
     *
     * @var string[]
     */
    protected $keys = [];

    /**
     * Array of container entry raw definitions
     *
     * @var string[]|callable[]
     */
    protected $definitions = [];

    /**
     * Array of closure entry keys
     *
     * @var bool[]
     */
    protected $closures = [];

    /**
     * Array of actual instances
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * Array of shared instances keys
     *
     * @var bool[]
     */
    protected $shared = [];

    /**
     * Array of created container entry factories
     *
     * @var Closure[]
     */
    protected $factories = [];

    /**
     * @var string[]
     */
    protected $aliases = [];

    /**
     * @var string[][]
     */
    protected $inflections = [];

    /**
     * {@inheritdoc}
     */
    public function set(string $id, $entry, array $aliases = [])
    {
        $id = $this->assertId($id);

        array_walk($aliases, [$this, 'assertAlias']);

        if ($this->isClosure($entry)) {
            $this->closures[$id] = true;
        }

        if ($this->isConcrete($entry)) {
            $this->instances[$id] = $entry;
            // All concrete instances are shared by default
            $this->shared[$id] = true;
        } else {
            $this->definitions[$id] = $entry;
        }

        $this->keys[$id] = true;
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $id;
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
     * {@inheritdoc}
     */
    public function alias(string $id, $alias)
    {
        $id = $this->normalize($id);
        foreach ((array)$alias as $a) {
            $this->aliases[$this->assertAlias($a)] = $id;
        }
    }

    /**
     * @inheritDoc
     */
    public function factory(string $id, callable $factory)
    {
        $id = $this->normalize($id);
        $this->factories[$id] = $factory;
        $this->keys[$id] = true;
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
    public function get($id, array $args = [])
    {
        $id = $this->resolveAlias($this->normalize($id));

        if (interface_exists($id) && !isset($this->keys[$id])) {
            throw new NotFoundException(sprintf('Unable to resolve "%s"', $id));
        }

        // Check shared instances first
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Create instance factory closure
        if (!isset($this->factories[$id])) {
            $this->factories[$id] = $this->createFactory($id);
        }

        return $this->factories[$id]($args);
    }

    /**
     * @inheritDoc
     */
    public function inflect(string $id, string $method, array $args = [])
    {
        $id = $this->resolveAlias($this->assertId($id));
        if (!method_exists($id, $method)) {
            throw new ContainerException(sprintf('Method "%s" not found in "%s"', $method, $id));
        }

        $this->inflections[$id][$method] = $args;
    }


    /**
     * @param string $id
     * @return string
     */
    protected function resolveAlias(string $id): string
    {
        return $this->aliases[$id] ?? $id;
    }

    /**
     * Checks if container can resolve subject id
     *
     * @param string $id
     * @return bool
     */
    protected function isResolvable(string $id): bool
    {
        return isset($this->keys[$this->normalize($id)]) || class_exists($id);
    }

    /**
     * Validate alias
     *
     * @param string $alias
     * @return string
     * @throws ContainerException
     */
    protected function assertAlias(string $alias): string
    {
        $alias = $this->normalize($alias);
        if (isset($this->aliases[$alias])) {
            throw new ContainerException(sprintf('Alias "%s" already defined', $alias));
        }

        return $alias;
    }

    /**
     * @param string $id
     * @return Closure
     */
    protected function createFactory(string $id): Closure
    {
        if (isset($this->closures[$id])) {
            return function () use ($id) {
                return $this->applyInflections(($this->createFactoryFromClosureDefinition($id))());
            };
        }

        return function (array $args = []) use ($id) {
            return $this->applyInflections(($this->createFactoryFromDefinition($id))($args));
        };
    }


    /**
     * Normalize id to use across container
     *
     * @param  string $id
     * @return string
     */
    protected function normalize(string $id): string
    {
        return strtolower(ltrim($id, '\\'));
    }

    /**
     * Ensure valid entry id
     *
     * @param string $id
     * @return string
     */
    protected function assertId(string $id): string
    {
        if (!interface_exists($id) && !class_exists($id)) {
            throw new ContainerException(
                sprintf('Invalid id "%s". Container entry id must be an existing interface or class name.', $id)
            );
        }

        return $this->normalize($id);
    }

    /**
     * Returns closure binding resolving function
     *
     * @param  string $id
     * @return Closure
     */
    protected function createFactoryFromClosureDefinition(string $id): Closure
    {
        // Create shared instance factory closure
        if (isset($this->shared[$id])) {
            return function () use ($id) {
                return $this->instances[$id] = $this->definitions[$id]($this);
            };
        }

        // Create instance factory closure
        return function () use ($id) {
            return $this->definitions[$id]($this);
        };
    }

    /**
     * Returns initialisation factory for objects
     *
     * @param  string $id
     * @return Closure
     */
    protected function createFactoryFromDefinition(string $id): Closure
    {
        $class = new ReflectionClass($id);

        // Create argument resolver if instance has constructor dependencies
        $argumentResolver = $this->createConstructorArgumentResolver($class);

        // Create factory closure for shared instance
        if (isset($this->shared[$id])) {
            return function (array $args = []) use ($id, $class, $argumentResolver) {
                return $this->instances[$id] = $argumentResolver
                    ? $class->newInstanceArgs($argumentResolver($args))
                    : new $class->name;

                // todo: inflect
            };
        }

        // Create factory closure with argument resolver
        if ($argumentResolver) {
            return function (array $args = []) use ($class, $argumentResolver) {
                return $class->newInstanceArgs($argumentResolver($args));
            };
        }

        // Create factory closure with no arguments
        return function () use ($class) {
            return new $class->name;
        };
    }

    /**
     * @param ReflectionClass $class
     * @return callable|Closure|null
     */
    protected function createConstructorArgumentResolver(ReflectionClass $class)
    {
        $constructor = $class->getConstructor();

        return ($constructor && $constructor->getNumberOfParameters())
            ? $this->createArgumentResolver($constructor)
            : null;
    }

    /**
     * Defines, if passed in item is a closure
     *
     * @param $entry
     * @return bool
     */
    protected function isClosure($entry): bool
    {
        return $entry instanceof Closure;
    }

    /**
     * Defines, if passed in item is an object instance
     *
     * @param mixed $entry
     * @return bool
     */
    protected function isConcrete($entry): bool
    {
        return is_object($entry) && !$entry instanceof Closure;
    }

    /**
     * Apply inflections on subject object
     *
     * @param $object
     * @return mixed
     */
    protected function applyInflections($object)
    {
        foreach ($this->inflections as $type => $methods) {
            if (!$object instanceof $type) {
                continue;
            }

            foreach ($methods as $method => $args) {
                $argumentResolver = $this->createArgumentResolver(new ReflectionMethod($type, $method));
                call_user_func_array([$object, $method], $argumentResolver($args));
            }
        }

        return $object;
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @return Closure
     */
    public function createArgumentResolver(ReflectionFunctionAbstract $method): Closure
    {
        // Reflect method arguments from method signature once
        // to use in resolver closure for all future resolve operations
        $reflectedParams = array_map(function (ReflectionParameter $parameter) {
            return [$parameter, $parameter->getClass() ? $parameter->getClass()->name : null];
        }, $method->getParameters());

        // Create argument resolver closure with reflected arguments and container to provide resolving functionality
        return function (array $overrideArgs = []) use ($reflectedParams) {

            return array_map(function ($paramDefinition) use ($overrideArgs) {
                /** @var ReflectionParameter $parameter */
                list($parameter, $class) = $paramDefinition;

                // If passed use argument instead of reflected parameter
                if (array_key_exists($parameter->name, $overrideArgs)) {
                    return $overrideArgs[$parameter->name];
                }

                // Recursively resolve method arguments
                if ($class !== null) {
                    return $this->get($class);
                }

                // Use argument default value if defined
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                return null;

            }, $reflectedParams);
        };
    }

    private function __clone()
    {
    }

}
