<?php

declare(strict_types=1);

namespace Internal\Container\Attribute;

/**
 * Marks a service to be shared across scopes instead of cloned per-scope.
 *
 * By default a scope clones each cached service when a child scope is derived, so every scope owns
 * its own mutable instance. A class marked with this attribute keeps a single instance shared across
 * the whole scope tree — use it for services that are safe to share (e.g. stateless or immutable ones)
 * on any supported PHP version, including where `readonly class` is unavailable.
 *
 * The attribute is not inherited: a subclass must be marked explicitly to be treated as shared.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ScopeShared {}
