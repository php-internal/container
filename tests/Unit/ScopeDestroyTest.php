<?php

declare(strict_types=1);

namespace Internal\Container\Tests\Unit;

use Internal\Container\ObjectContainer;
use Internal\Container\Tests\Unit\Stub\DestroyableService;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

/**
 * {@see ObjectContainer::scope()} tears down the scoped state when it exits.
 *
 * Leaving a scope destroys the temporary state, so every {@see \Internal\Destroy\Destroyable} service the
 * scope produced is torn down — while the parent's own instances are left untouched.
 */
#[Test]
#[Covers(ObjectContainer::class)]
final class ScopeDestroyTest
{
    public function leavingAScopeDestroysServicesResolvedInside(): void
    {
        $container = new ObjectContainer();

        $inScope = null;
        $container->scope(static function (ObjectContainer $scoped) use (&$inScope): void {
            $inScope = $scoped->get(DestroyableService::class);
            Assert::false($inScope->destroyed);
        });

        Assert::true($inScope->destroyed);
    }

    public function leavingAScopeKeepsTheParentServiceAlive(): void
    {
        $container = new ObjectContainer();
        $parent = $container->get(DestroyableService::class);

        $container->scope(static fn(ObjectContainer $scoped): DestroyableService => $scoped->get(DestroyableService::class));

        Assert::false($parent->destroyed);
    }
}
