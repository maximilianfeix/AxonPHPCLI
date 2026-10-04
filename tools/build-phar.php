<?php

declare(strict_types=1);

/*
 * Builds build/axonphp.phar, the single-file distribution attached to every release.
 *
 * Install the dependencies without the development packages first, so they stay out of the archive:
 *
 *   composer install --no-dev --classmap-authoritative
 *   php -d phar.readonly=0 tools/build-phar.php
 */

$root = dirname(__DIR__);
$target = $root.'/build/axonphp.phar';

if ('1' === ini_get('phar.readonly')) {
    fwrite(\STDERR, "Phar creation is disabled. Run this script with \"php -d phar.readonly=0\".\n");

    exit(1);
}

if (!is_file($root.'/vendor/autoload.php')) {
    fwrite(\STDERR, "Dependencies are missing. Run \"composer install --no-dev\" first.\n");

    exit(1);
}

if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0o777, true);
}

if (is_file($target)) {
    unlink($target);
}

$files = [$root.'/LICENSE' => 'LICENSE'];

foreach (['src', 'vendor'] as $directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        $relative = substr($path, strlen($root) + 1);

        // Dependencies read their own resource files at runtime, so only what is clearly unused is left out:
        // tests, documentation, executables and repository metadata.
        if (1 === preg_match('~/(?:[Tt]ests?|docs?|bin|\.github)/|\.md$|/(?:composer\.json|phpunit\.xml\.dist|\.git[a-z]+)$~', $relative)) {
            continue;
        }

        $files[$path] = $relative;
    }
}

ksort($files);

$phar = new Phar($target, 0, 'axonphp.phar');
$phar->startBuffering();
$phar->buildFromIterator(new ArrayIterator(array_flip($files)));
$phar->setStub(
    <<<'STUB'
        #!/usr/bin/env php
        <?php

        Phar::mapPhar('axonphp.phar');

        require 'phar://axonphp.phar/vendor/autoload.php';

        exit((new AxonPHP\Cli\Application())->run());

        __HALT_COMPILER();
        STUB,
);
$phar->stopBuffering();

chmod($target, 0o755);

fwrite(\STDOUT, sprintf("Wrote %s (%d files, %d KB)\n", $target, count($files), (int) ceil((int) filesize($target) / 1024)));
