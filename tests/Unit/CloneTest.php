<?php

declare(strict_types=1);

namespace Internal\Container\Tests\Unit;

use Internal\Container\ObjectContainer;
use Internal\Container\Tests\Unit\Stub\ContainerScopeService;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

/**
 * {@see ObjectContainer::__clone()} — cloning a container copies its state.
 *
 * The clone gets its own {@see State} object, so its service registry is independent: rebinding or
 * replacing a service on the clone never reaches back into the original. The already-cached instances
 * themselves stay shared until one side replaces them.
 */
#[Test]
#[Covers(ObjectContainer::class)]
final class CloneTest
{
    public function cloneIsADistinctContainer(): void
    {
        $container = new ObjectContainer();

        Assert::notSame(clone $container, $container);
    }

    public function replacingAServiceOnTheCloneDoesNotLeakToTheOriginal(): void
    {
        $container = new ObjectContainer();
        $original = $container->get(ContainerScopeService::class);

        $clone = clone $container;
        $clone->set($replacement = new ContainerScopeService(), ContainerScopeService::class);

        Assert::same($clone->get(ContainerScopeService::class), $replacement);
        Assert::same($container->get(ContainerScopeService::class), $original);
    }
}
