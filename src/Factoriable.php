<?php

declare(strict_types=1);

namespace Internal\Container;

/**
 * Interface for classes that can create instances of themselves.
 *
 * Implementing classes should provide the static factory method `create()` for instantiation.
 * This pattern is useful for objects that require complex initialization logic or dependency resolution.
 *
 * ```
 *  class Config implements Factoriable
 *  {
 *      private function __construct(
 *          private Logger $logger,
 *      ) {}
 *
 *      public static function create(Logger $logger): self
 *      {
 *          return new self($logger);
 *      }
 *  }
 *
 *  $container->get(Config::class); // Will be created via the `create()` method with autowiring
 * ```
 *
 * @method static static create() Creates a new instance; parameters are autowired by the container.
 */
interface Factoriable {}
