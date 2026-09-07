<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;

return new ApplicationConfig(
    src: ['src'],
    suites: require __DIR__ . '/tests/suites.php',
);
