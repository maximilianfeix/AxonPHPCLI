<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests;

use PHPUnit\Framework\Attributes\After;

/**
 * Creates throwaway project directories and removes them after each test.
 */
trait TemporaryProject
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    #[After]
    protected function removeTemporaryProjects(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            self::removeDirectory($directory);
        }

        $this->temporaryDirectories = [];
    }

    /**
     * @param null|array<string, mixed>|string $manifest composer.json as data, as raw text, or null for none
     */
    private function createProject(array|string|null $manifest = null): string
    {
        $directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'axonphp-'.bin2hex(random_bytes(6));
        mkdir($directory, 0o777, true);

        $resolved = realpath($directory);
        self::assertIsString($resolved);
        $this->temporaryDirectories[] = $resolved;

        if (null !== $manifest) {
            $contents = is_string($manifest) ? $manifest : json_encode($manifest, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
            file_put_contents($resolved.\DIRECTORY_SEPARATOR.'composer.json', $contents);
        }

        return $resolved;
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);

        foreach (false === $entries ? [] : $entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.\DIRECTORY_SEPARATOR.$entry;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
