<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests;

use Symfony\Component\Yaml\Yaml;

/**
 * Parses generated pipelines so tests assert on structure instead of whitespace.
 */
trait ReadsYaml
{
    /**
     * Parses the document and returns the value at the given path.
     */
    private static function yaml(string $document, int|string ...$path): mixed
    {
        $value = Yaml::parse($document);

        foreach ($path as $key) {
            self::assertIsArray($value);
            self::assertArrayHasKey($key, $value);
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @return list<mixed> the "run" commands of a list of GitHub Actions steps
     */
    private static function commands(mixed $steps): array
    {
        self::assertIsArray($steps);

        return array_column($steps, 'run');
    }
}
