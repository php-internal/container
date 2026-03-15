<?php

declare(strict_types=1);

namespace Internal\Container;

/**
 * Container inflector interface.
 */
interface Inflector
{
    /**
     * Inflects the resolved object.
     *
     * @param object $object Object to inflect.
     * @param Container $container Parent container.
     * @return object Inflected object.
     */
    public function inflect(object $object, Container $container): object;
}
