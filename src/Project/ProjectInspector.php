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
            return new Project(PhpVersionResolver::DEFAULT, usesComposer: false);
        }

        $manifest = $this->read($file);
        $require = $this->packages($manifest, 'require');
        $requireDev = $this->packages($manifest, 'require-dev');
        $packages = array_keys($require + $requireDev);
        $constraint = $require['php'] ?? null;
        $name = $manifest['name'] ?? null;

        // Pipelines install the dev dependencies too, so a "php" entry in require-dev narrows the matrix as well.
        $versions = $this->versions->resolve($constraint, $requireDev['php'] ?? null);

        return new Project(
            $versions ?? PhpVersionResolver::DEFAULT,
            $this->extensions($packages),
            $this->catalog->detect($packages, $this->binDir($manifest)),
            true,
            $constraint,
            is_string($name) ? $name : null,
            $this->settings($manifest),
            null !== $versions,
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
            // Decoding to arrays alone cannot tell "{}" from "[]", so the root type is checked on the object form.
            $isObject = json_decode($contents, false, 512, \JSON_THROW_ON_ERROR) instanceof \stdClass;
            $manifest = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ProjectException(sprintf('"%s" is not valid JSON: %s.', $file, $exception->getMessage()), 0, $exception);
        }

        if (!$isObject || !is_array($manifest)) {
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
     * The directory Composer links the tools' executables into, honouring "config.bin-dir" and "config.vendor-dir".
     *
     * @param array<mixed> $manifest
     */
    private function binDir(array $manifest): string
    {
        $config = $manifest['config'] ?? null;
        $config = is_array($config) ? $config : [];

        $vendorDir = $config['vendor-dir'] ?? 'vendor';
        $vendorDir = is_string($vendorDir) ? $vendorDir : 'vendor';

        $binDir = $config['bin-dir'] ?? null;
        $binDir = is_string($binDir) ? str_replace('{$vendor-dir}', $vendorDir, $binDir) : $vendorDir.'/bin';
        $binDir = rtrim(str_replace('\\', '/', $binDir), '/');

        // The directory ends up in shell commands inside YAML, so only plain relative paths are accepted.
        if (1 !== preg_match('~^[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*$~D', $binDir) || str_contains($binDir, '..')) {
            throw new ProjectException(sprintf('Cannot use the Composer bin-dir "%s". Use a relative path without special characters.', $binDir));
        }

        return $binDir;
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
            $this->percentage($settings, 'min-coverage'),
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

        if (!is_array($value) || [] === $value || !array_is_list($value)) {
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

    /**
     * @param array<mixed> $settings
     */
    private function percentage(array $settings, string $key): ?float
    {
        $value = $settings[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if ((!is_int($value) && !is_float($value)) || $value < 0 || $value > 100) {
            throw new ProjectException(sprintf('"extra.axonphp.%s" in composer.json must be a number from 0 to 100.', $key));
        }

        return (float) $value;
    }
}
