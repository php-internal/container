<?php

declare(strict_types=1);

namespace Internal\Container\Internal;

use Internal\Container\Attribute\ScopeShared;
use Internal\Container\Container;
use Internal\Container\Factoriable;
use Internal\Container\Inflector;
use Internal\Container\ObjectContainer;
use Internal\Destroy\Destroyable;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Injector\Injector;

/**
 * @internal
 */
final class State
{
    /** @var array<class-string, object> */
    private array $cache = [];

    /**
     * Bindings:
     * Array of:
     * - {@see \Closure}: just a factory
     * - {@see array}: arguments for the Injector
     * with the class name as key.
     *
     * @var array<class-string, array|\Closure(self): object>
     */
    private array $factory = [];

    /** @var list<Inflector> */
    private array $inflectors = [];

    private Injector $injector;

    /** @var array<int, Destroyable> */
    private array $destroy = [];

    private ObjectContainer $container;

    public function __construct(ObjectContainer $container)
    {
        $this->init($container);
    }

    public function addInflector(Inflector $inflector): void
    {
        $this->inflectors[] = $inflector;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @param array<string, mixed> $arguments
     * @return T
     */
    public function get(string $id, array $arguments = []): object
    {
        if (isset($this->cache[$id])) {
            /** @var T */
            return $this->cache[$id];
        }

        $result = $this->make($id, $arguments);
        $this->cache[$id] = $result;
        $result instanceof Destroyable and $this->destroy[\spl_object_id($result)] = $result;

        return $result;
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->cache) || \array_key_exists($id, $this->factory);
    }

    /**
     * @param class-string|null $id
     */
    public function set(object $service, ?string $id = null, bool $destroy = false): void
    {
        $this->cache[$id ?? $service::class] = $service;
        $service instanceof Destroyable and $this->destroy[\spl_object_id($service)] = $service;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $arguments
     * @return T
     */
    public function make(string $class, array $arguments = []): object
    {
        $binding = $this->factory[$class] ?? [];

        if (\is_array($binding)) {
            try {
                $result = $this->injector->make($class, \array_merge($binding, $arguments));
            } catch (\Throwable $e) {
                throw new class("Unable to create object of class $class.", previous: $e) extends \RuntimeException implements NotFoundExceptionInterface {};
            }
        } else {
            $result = $binding($this);
        }

        \assert($result instanceof $class, "Created object must be instance of {$class}.");

        foreach ($this->inflectors as $inflector) {
            $result = $inflector->inflect($result, $this->container);
        }

        /** @var T $result */
        return $result;
    }

    /**
     * @param class-string $id
     * @param null|class-string|array<string, mixed>|\Closure(mixed ...): object $binding
     */
    public function bind(string $id, \Closure|string|array|null $binding = null): void
    {
        $binding ??= $id;

        if (\is_string($binding)) {
            \class_exists($binding) or throw new \InvalidArgumentException(
                "Class `$binding` does not exist.",
            );
            $id === $binding or \is_a($binding, $id, true) or throw new \InvalidArgumentException(
                "Alias for `$id` must be instance of `$id`, `$binding` given.",
            );

            $binding = match (true) {
                $id !== $binding => static fn(self $self): object => $self->get($binding),
                \is_a($binding, Factoriable::class, true) => static fn(self $self): object => $self
                    ->invoke($binding::create(...)),
                default => static fn(self $self): object => $self->injector->make($binding),
            };
        } elseif ($binding instanceof \Closure) {
            $binding = static fn(self $self): object => $self->invoke($binding);
        }

        $this->factory[$id] = $binding;
    }

    public function destroy(): void
    {
        unset($this->cache, $this->factory, $this->injector, $this->container);
        while ($inflector = \array_pop($this->inflectors)) {
            $inflector instanceof Destroyable and $inflector->destroy();
        }

        while ($service = \array_pop($this->destroy)) {
            $service->destroy();
        }
    }

    public function clone(ObjectContainer $container): self
    {
        $self = clone $this;
        [$cache, $self->cache, $self->destroy] = [$self->cache, [], []];
        $self->init($container);

        /** @var array<int, object> $cloned */
        $cloned = [];

        foreach ($cache as $id => $service) {
            if (\array_key_exists($id, $self->cache)) {
                continue;
            }

            $reflection = new \ReflectionClass($service);
            if (
                $reflection->isEnum()
                || (PHP_VERSION_ID >= 80200 && $reflection->isReadOnly())
                || $reflection->getAttributes(ScopeShared::class) !== []
            ) {
                $self->cache[$id] = $service;
                continue;
            }

            $oid = \spl_object_id($service);
            $c = $cloned[$oid] ?? null;
            if ($c === null) {
                $c = clone $service;
                $cloned[$oid] = $c;
                $c instanceof Destroyable and $self->destroy[\spl_object_id($c)] = $c;
            }

            $self->cache[$id] = $c;
        }

        return $self;
    }

    private function init(ObjectContainer $container): void
    {
        $this->container = $container;
        $this->injector = (new Injector($container))->withCacheReflections(false);

        $this->cache = [
            Injector::class => $this->injector,
            Container::class => $container,
            self::class => $container,
            ObjectContainer::class => $container,
            ContainerInterface::class => $container,
        ];
    }

    /**
     * Invokes a factory closure through the injector and guards its result type.
     */
    private function invoke(\Closure $factory): object
    {
        /** @var mixed $result */
        $result = $this->injector->invoke($factory);
        \is_object($result) or throw new \RuntimeException(
            \sprintf('Factory must return an object, `%s` returned.', \get_debug_type($result)),
        );

        return $result;
    }
}
