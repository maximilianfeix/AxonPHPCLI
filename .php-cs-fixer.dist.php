<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true,
        '@PhpCsFixer' => true,
        // PHPUnit metadata is declared with attributes, not docblock annotations.
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder(
        (new Finder())
            ->in([__DIR__.'/src', __DIR__.'/tests'])
            ->append([__DIR__.'/bin/axonphp', __DIR__.'/site/build.php', __FILE__])
    )
;
