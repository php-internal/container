<?php

declare(strict_types=1);

namespace Internal\Container\Tests\Unit\Stub;

use Internal\Container\Attribute\ScopeShared;

/**
 * Mutable service marked {@see ScopeShared} — a scope shares it with its parent instead of cloning it.
 *
 * Deliberately not `readonly` and not an enum, so a test that observes sharing proves the attribute is
 * the cause rather than immutability.
 *
 * @internal
 */
#[ScopeShared]
final class ScopeSharedTag
{
    public int $tag = 7;
}
