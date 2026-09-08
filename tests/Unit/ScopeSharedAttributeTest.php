<?php

declare(strict_types=1);

namespace Internal\Container\Tests\Unit;

use Internal\Container\Attribute\ScopeShared;
use Internal\Container\ObjectContainer;
use Internal\Container\Tests\Unit\Stub\ContainerScopeService;
use Internal\Container\Tests\Unit\Stub\ScopeSharedTag;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

/**
 * {@see ScopeShared} — a service carrying the attribute is shared across scopes instead of cloned.
 *
 * A scope clones each cached service so children get their own mutable copy; the attribute opts a class
 * out of that, keeping one instance across the whole scope tree even when it is a plain mutable class
 * (neither `readonly` nor an enum).
 */
#[Test]
#[Covers(ObjectContainer::class)]
final class ScopeSharedAttributeTest
{
    public function scopeSharesMarkedServiceWithTheParent(): void
    {
        $container = new ObjectContainer();
        $parent = $container->get(ScopeSharedTag::class);

        $inScope = $container->scope(
            static fn(ObjectContainer $scoped): ScopeSharedTag => $scoped->get(ScopeSharedTag::class),
        );

        Assert::same($inScope, $parent);
    }

    public function mutatingTheSharedServiceInsideAScopeIsVisibleToTheParent(): void
    {
        $container = new ObjectContainer();
        $parent = $container->get(ScopeSharedTag::class);

        $container->scope(static function (ObjectContainer $scoped): void {
            $scoped->get(ScopeSharedTag::class)->tag = 42;
        });

        Assert::same($parent->tag, 42);
    }

    public function nestedScopesShareTheSameMarkedInstance(): void
    {
        $container = new ObjectContainer();
        $parent = $container->get(ScopeSharedTag::class);

        $deepest = $container->scope(
            static fn(ObjectContainer $l1): ScopeSharedTag => $l1->scope(
                static fn(ObjectContainer $l2): ScopeSharedTag => $l2->get(ScopeSharedTag::class),
            ),
        );

        Assert::same($deepest, $parent);
    }

    /**
     * The attribute is the differentiator: an otherwise identical unmarked mutable service is still cloned.
     */
    public function unmarkedServiceIsStillClonedPerScope(): void
    {
        $container = new ObjectContainer();
        $parent = $container->get(ContainerScopeService::class);

        $inScope = $container->scope(
            static fn(ObjectContainer $scoped): ContainerScopeService => $scoped->get(ContainerScopeService::class),
        );

        Assert::notSame($inScope, $parent);
    }
}
