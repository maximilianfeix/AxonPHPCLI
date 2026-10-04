<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

use AxonPHP\Cli\Exception\ProjectException;

/**
 * Reads a project's composer.json to find out what its pipeline has to run.
 */
final readonly class ProjectInspector
{
    public function __construct(
        private PhpVersionResolver $versions = new PhpVersionResolver(),
        private ToolCatalog $catalog = new ToolCatalog(),
    ) {}

    public function inspect(string $directory): Project
    {
        $file = $directory.\DIRECTORY_SEPARATOR.'composer.json';

        if (!is_file($file)) {
            return new Project($this->versions->resolve(null), usesComposer: false);
        }

        $manifest = $this->read($file);
        $require = $this->packages($manifest, 'require');
        $requireDev = $this->packages($manifest, 'require-dev');
        $packages = array_keys($require + $requireDev);
        $constraint = $require['php'] ?? null;

        return new Project(
            $this->versions->resolve($constraint),
            $this->extensions($packages),
            $this->catalog->detect($packages),
            true,
            $constraint,
        );
    }

    /**
     * @return array<mixed>
     */
    private function read(string $file): array
    {
        $contents = @file_get_contents($file);

        if (false === $contents) {
            throw new ProjectException(sprintf('Could not read "%s".', $file));
        }

        try {
            $manifest = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ProjectException(sprintf('"%s" is not valid JSON: %s.', $file, $exception->getMessage()), 0, $exception);
        }

        if (!is_array($manifest)) {
            throw new ProjectException(sprintf('"%s" must contain a JSON object.', $file));
        }

        return $manifest;
    }

    /**
     * @param array<mixed> $manifest
     *
     * @return array<string, string> package name => version constraint
     */
    private function packages(array $manifest, string $section): array
    {
        $entries = $manifest[$section] ?? [];

        if (!is_array($entries)) {
            return [];
        }

        $packages = [];

        foreach ($entries as $name => $constraint) {
            if (is_string($name) && is_string($constraint)) {
                $packages[strtolower($name)] = $constraint;
            }
        }

        return $packages;
    }

    /**
     * @param list<string> $packages
     *
     * @return list<string>
     */
    private function extensions(array $packages): array
    {
        $extensions = [];

        foreach ($packages as $package) {
            if (1 === preg_match('/^ext-([a-z0-9_-]+)$/', $package, $matches)) {
                $extensions[] = $matches[1];
            }
        }

        sort($extensions);

        return $extensions;
    }
}
