# Abava Container package
This package is minimal container implementation, aimed to be used with PHP7

## Table of contents
- [Installation](#installation)
- [Basic usage](#basic-usage)
- [Container](#container)
- [Tagging](#tags)
- [Callbacks and rewrites](#affectors)
- [Trait](#trait)
- [Advanced](#advanced)

## Installation

```sh
    composer require Abava/container
```

## Basic usage
Create an instance of container itself, and start binding.

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Create container instance
    $container = new \Abava\Container\Container;
    
    // Apply bindings
    $container->bind('container', $container);
    
    // Resolve bindings
    $container->make('container'); // $container->make('container') === $container
```

## Container

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Create container instance
    $container = new \Abava\Container\Container;
    
    // Binding items
    $container->bind('something', 'stdClass');
    $container->share('container', $container); // singleton
    $container->bind('another.container', $container); // same as $container->share('another.container', $container);
    $container->share('function', function() {
        return new \stdClass;
    });
    
    // Checking if items exists in container
    $container->has('something'); // true
    $container->has('something.else'); // false
    
    // Resolving an item
    $container->make('stdClass'); // === new stdClass;
    $container->get('stdClass'); // same as above
    $container->make('container'); // === $container
    $container->make('something', ['argumentName' => $argumentValue]); // hand picked constructor arguments
    
    // Calling a method or function out of container
    $container->call('SomeClass@someMethod');
    $container->call('SomeClass@someMethod', ['argumentName' => $argumentValue]);
    $container->call(function(\stdClass $item) {
        return $item;
    }); // === new \stdClass;
```

## Tagging

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Create container instance
    $container = new \Abava\Container\Container;
    
    // Tag items
    $container->bind('container', $container);
    $container->bind('something', 'stdClass');
    $container->tag(['container', 'something'], 'tag-name');
    
    // Resolve all items at once
    $container->tagged('tag-name') === [$container->make('container'), $container->make('something')];
```

## Callbacks and rewrite
If you want to rewrite something, that is defined in container, you can do it with `resolving()` callback. If something will be returned from that function, it will be considered as a rewrite. This will also check proper inheritance of class, used for rewrite.

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Create container instance and rewrite class
    $container = new \Abava\Container\Container;
    $rewrite = new class extends \stdClass {};
    
    // Perform rewrite
    $container->bind('item', new \stdClass);
    $container->resolving('item', function(\stdClass $active) use ($rewrite) {
        return $rewrite;
    });
    $container->make('item') === $rewrite;
    
    // This rewrite will throw LogicException, since class used for rewrite isn't extending \stdClass
    $container->resolving('item', function(\stdClass $active) {
        return new class {};
    });
    $container->make('item'); // throws LogicException
```

If you want to perform some action on class, when it is finally resolved, you can use `resolved()` callback.

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Create container instance and rewrite class
    $container = new \Abava\Container\Container;
    
    // Using callback
    $container->resolved('config', function($config) {
        $config->set('database.driver', 'mysql');
    });
```

## Trait
There is a trait and contract to use in order to add container support to your class.

```php
    // Require autoload
    require __DIR__ . '/vendor/autoload.php';
    
    // Creating some class, using trait and contract
    $containerAwareClass = new class implements \Abava\Contract\Container\ContainerAwareContract {
        use Abava\Container\Traits\ContainerAwareTrait;
    };
    
    // Now you can get container instance with
    $container = $containerAwareClass->getContainer();
```

## Advanced
TBD