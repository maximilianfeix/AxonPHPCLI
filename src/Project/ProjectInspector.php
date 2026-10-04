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
        $name = $manifest['name'] ?? null;

        return new Project(
            $this->versions->resolve($constraint),
            $this->extensions($packages),
            $this->catalog->detect($packages),
            true,
            $constraint,
            is_string($name) ? $name : null,
            $this->settings($manifest),
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

    /**
     * Reads "extra.axonphp". Unlike the rest of composer.json this section is ours,
     * so a wrong type is reported instead of being silently ignored.
     *
     * @param array<mixed> $manifest
     */
    private function settings(array $manifest): ProjectSettings
    {
        $extra = $manifest['extra'] ?? null;
        $settings = is_array($extra) ? ($extra['axonphp'] ?? []) : [];

        if (!is_array($settings)) {
            throw new ProjectException('"extra.axonphp" in composer.json must be an object.');
        }

        $unknown = array_diff(array_keys($settings), ProjectSettings::KEYS);

        if ([] !== $unknown) {
            throw new ProjectException(sprintf(
                'Unknown key "extra.axonphp.%s" in composer.json. Supported keys: %s.',
                (string) reset($unknown),
                implode(', ', ProjectSettings::KEYS),
            ));
        }

        return new ProjectSettings(
            $this->stringList($settings, 'php'),
            $this->stringList($settings, 'branches'),
            $this->boolean($settings, 'coverage'),
            $this->boolean($settings, 'lowest'),
            $this->boolean($settings, 'audit'),
        );
    }

    /**
     * @param array<mixed> $settings
     *
     * @return null|list<string>
     */
    private function stringList(array $settings, string $key): ?array
    {
        $value = $settings[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!is_array($value) || [] === $value) {
            throw new ProjectException(sprintf('"extra.axonphp.%s" in composer.json must be a non-empty list of strings.', $key));
        }

        $list = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new ProjectException(sprintf('"extra.axonphp.%s" in composer.json must be a non-empty list of strings.', $key));
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @param array<mixed> $settings
     */
    private function boolean(array $settings, string $key): ?bool
    {
        $value = $settings[$key] ?? null;

        if (null !== $value && !is_bool($value)) {
            throw new ProjectException(sprintf('"extra.axonphp.%s" in composer.json must be true or false.', $key));
        }

        return $value;
    }
}
