<div align="center">

# Container

</div>

<p align="center">A lightweight PSR-11 container for CLI tools</p>

<div align="center">

[![Support on Boosty](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsorship&logo=Boosty&logoColor=white&color=%23F15F2C)](https://boosty.to/roxblnfk)

[![Vibe Index](https://img.shields.io/static/v1?label=Vibe+Index&message=1.8&color=1eb88b&style=flat-square&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2ZmZiI%2BPHBhdGggZD0iTTkgNCBROSAxMyAxOCAxMyBROSAxMyA5IDIyIFE5IDEzIDAgMTMgUTkgMTMgOSA0IFoiLz48cGF0aCBkPSJNMTkgMSBRMTkgNiAyNCA2IFExOSA2IDE5IDExIFExOSA2IDE0IDYgUTE5IDYgMTkgMSBaIi8%2BPHBhdGggZD0iTTIwIDE0IFEyMCAxOCAyNCAxOCBRMjAgMTggMjAgMjIgUTIwIDE4IDE2IDE4IFEyMCAxOCAyMCAxNCBaIi8%2BPC9zdmc%2B)](https://github.com/roxblnfk/action-vibe-index)
[![Psalm Level](https://shepherd.dev/github/php-internal/container/level.svg)](https://shepherd.dev/github/php-internal/container)
[![Type Coverage](https://shepherd.dev/github/php-internal/container/coverage.svg)](https://shepherd.dev/github/php-internal/container)
[![codecov](https://codecov.io/gh/php-internal/container/branch/1.x/graph/badge.svg)](https://codecov.io/gh/php-internal/container)
[![Mutation testing badge](https://img.shields.io/endpoint?url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphp-internal%2Fcontainer%2F1.x)](https://dashboard.stryker-mutator.io/reports/github.com/php-internal/container/1.x)

</div>

<br />

A small PSR-11 container for command-line tools and other short-lived PHP processes: autowiring, a handful of bindings and deterministic cleanup without a full-blown DI framework. No compilation, no config files, no lazy proxies, nothing to set up beyond `new ObjectContainer()`. It was born in [Trap](https://github.com/buggregator/trap), grew up in [DLoad](https://github.com/php-internal/dload) and [Testo](https://github.com/php-testo/testo), and is extracted here so all of them can share one implementation.

What it does:

- Resolves classes by constructor autowiring through [yiisoft/injector](https://github.com/yiisoft/injector); resolved services are cached.
- Accepts bindings as a factory closure, an alias class name or predefined constructor arguments.
- Creates objects that implement `Factoriable` through their static `create()` method with autowired parameters.
- Passes every resolved object through registered `Inflector`s before it is cached.
- Opens nested scopes: services resolved inside a scope live only until the scope closes.
- Destroys managed services on `destroy()`, see [internal/destroy](https://github.com/php-internal/destroy).

## Installation

```bash
composer require internal/container
```

[![PHP](https://img.shields.io/packagist/php-v/internal/container.svg?style=flat-square&logo=php)](https://packagist.org/packages/internal/container)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/internal/container.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/internal/container)
[![License](https://img.shields.io/packagist/l/internal/container.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/internal/container.svg?style=flat-square)](https://packagist.org/packages/internal/container/stats)

## Usage

```php
use Internal\Container\Container;
use Internal\Container\ObjectContainer;

$container = new ObjectContainer();

// Autowired on first request, cached afterwards
$downloader = $container->get(Downloader::class);

// Factory binding
$container->bind(Logger::class, static fn(Container $c) => new Logger($c->get(OutputInterface::class)));

// Alias binding: an interface resolves to a concrete class
$container->bind(ClockInterface::class, SystemClock::class);

// Constructor arguments binding
$container->bind(HttpClient::class, ['timeout' => 30]);

// Register an existing instance; `destroy: true` hands its lifecycle over to the container
$container->set($output, OutputInterface::class, destroy: true);

// A fresh instance that is not cached
$request = $container->make(Request::class, ['uri' => '/']);
```

### Factoriable

A class that needs custom construction implements `Factoriable` and exposes a static `create()`. The container calls it with autowired parameters instead of the constructor.

```php
use Internal\Container\Factoriable;

final class Config implements Factoriable
{
    private function __construct(private readonly Logger $logger) {}

    public static function create(Logger $logger): self
    {
        return new self($logger);
    }
}
```

### Inflectors

An `Inflector` sees every object right after it is resolved and may replace or configure it.

```php
use Internal\Container\Container;
use Internal\Container\Inflector;

$container->addInflector(new class implements Inflector {
    public function inflect(object $object, Container $container): object
    {
        $object instanceof LoggerAwareInterface and $object->setLogger($container->get(Logger::class));
        return $object;
    }
});
```

### Scopes

`scope()` runs a closure against a child state. Bindings are inherited, cached services are cloned into the scope (readonly objects and enums are shared as is), and everything resolved inside is destroyed when the closure returns. The parent state is left untouched.

```php
$result = $container->scope(static function (Container $scoped): Result {
    $scoped->set(new SuiteConfig(...));

    return $scoped->get(SuiteRunner::class)->run();
});
```

Scopes are fiber-aware: a scope opened outside an event loop is the active one for everything that runs on the loop under it. Opening a scope inside a loop-driven fiber and suspending within it is not supported.

## Testing

```bash
composer test
```
