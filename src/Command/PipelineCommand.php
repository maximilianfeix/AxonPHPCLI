<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\Provider;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Shared input handling for the commands that inspect a project or build its pipeline.
 *
 * Command line options win over "extra.axonphp" in composer.json, which wins over the built-in defaults.
 */
abstract class PipelineCommand extends Command
{
    public function __construct(
        protected readonly ProviderRegistry $providers,
        protected readonly ProjectInspector $inspector,
    ) {
        parent::__construct();
    }

    protected function addProviderArgument(string $description): static
    {
        $names = $this->providers->names();

        return $this->addArgument(
            'provider',
            InputArgument::OPTIONAL,
            sprintf('%s (%s)', $description, implode(', ', $names)),
            null,
            $names,
        );
    }

    protected function addWorkingDirOption(): static
    {
        return $this->addOption(
            'working-dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Project directory (default: current directory)',
        );
    }

    protected function addPipelineOptions(): static
    {
        return $this
            ->addOption(
                'php',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'PHP version to test against, repeatable (default: derived from composer.json)',
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Branch whose pushes trigger the pipeline, repeatable (default: main)',
            )
            ->addOption(
                'coverage',
                null,
                InputOption::VALUE_NEGATABLE,
                'Measure code coverage on the newest PHP version',
            )
            ->addOption(
                'lowest',
                null,
                InputOption::VALUE_NEGATABLE,
                'Also test the lowest allowed dependencies on the oldest PHP version',
            )
            ->addOption(
                'audit',
                null,
                InputOption::VALUE_NEGATABLE,
                'Fail on dependencies with known security advisories',
            )
        ;
    }

    /**
     * @throws InvalidInputException
     */
    protected function directory(InputInterface $input): string
    {
        $option = $input->getOption('working-dir');
        $directory = is_string($option) && '' !== $option ? $option : getcwd();

        if (false === $directory || !is_dir($directory)) {
            throw new InvalidInputException(sprintf('The directory "%s" does not exist.', (string) $directory));
        }

        $resolved = realpath($directory);

        return false === $resolved ? $directory : $resolved;
    }

    /**
     * @throws InvalidInputException
     * @throws ProjectException
     */
    protected function context(InputInterface $input): PipelineContext
    {
        $directory = $this->directory($input);
        $project = $this->inspector->inspect($directory);
        $settings = $project->settings;

        $phpSource = match (true) {
            null !== $project->phpConstraint => sprintf('from "php": "%s"', $project->phpConstraint),
            default => 'default',
        };

        $phpVersions = $this->values($input, 'php');

        if ([] !== $phpVersions) {
            $phpSource = 'from --php';
        } elseif (null !== $settings->php) {
            $phpVersions = $settings->php;
            $phpSource = 'from extra.axonphp';
        }

        if ([] !== $phpVersions) {
            $project = $project->withPhpVersions($this->phpVersions($phpVersions));
        }

        $branches = $this->values($input, 'branch');

        $options = new PipelineOptions(
            $this->branches([] !== $branches ? $branches : $settings->branches ?? ['main']),
            $this->flag($input, 'coverage') ?? $settings->coverage ?? false,
            $this->flag($input, 'lowest') ?? $settings->lowest ?? false,
            $this->flag($input, 'audit') ?? $settings->audit ?? false,
        );

        return new PipelineContext($directory, $project, $options, $phpSource);
    }

    protected function pipelineFile(string $directory, Provider $provider): string
    {
        return $directory.\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $provider->path());
    }

    /**
     * @param list<string> $versions
     *
     * @return list<string> validated, unique and sorted oldest first
     */
    private function phpVersions(array $versions): array
    {
        foreach ($versions as $version) {
            if (1 !== preg_match('/^\d+\.\d+$/D', $version)) {
                throw new InvalidInputException(sprintf('Invalid PHP version "%s". Use the "major.minor" form, e.g. 8.4.', $version));
            }
        }

        $versions = array_values(array_unique($versions));
        usort($versions, static fn (string $a, string $b): int => version_compare($a, $b));

        return $versions;
    }

    /**
     * @param list<string> $branches
     *
     * @return list<string>
     */
    private function branches(array $branches): array
    {
        foreach ($branches as $branch) {
            // Branch names end up inside YAML and shell-like expressions, so keep them to a safe alphabet.
            if (1 !== preg_match('~^[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $branch)) {
                throw new InvalidInputException(sprintf('Invalid branch name "%s".', $branch));
            }
        }

        return array_values(array_unique($branches));
    }

    /**
     * @return list<string> the unique, non-empty values of an array option
     */
    private function values(InputInterface $input, string $option): array
    {
        $values = [];

        foreach ((array) $input->getOption($option) as $value) {
            if (is_string($value) && '' !== trim($value)) {
                $values[] = trim($value);
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @return ?bool null when neither --name nor --no-name was passed
     */
    private function flag(InputInterface $input, string $option): ?bool
    {
        $value = $input->getOption($option);

        return is_bool($value) ? $value : null;
    }
}
