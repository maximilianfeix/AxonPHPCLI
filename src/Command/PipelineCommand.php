<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Diff\LineDiff;
use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\Provider;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
                'min-coverage',
                null,
                InputOption::VALUE_REQUIRED,
                'Fail the coverage job below this line coverage in percent; implies --coverage',
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
            !$project->phpResolved => sprintf('default, "php": "%s" matches no known version', (string) $project->phpConstraint),
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

        $minCoverage = $this->percentage($input, 'min-coverage') ?? $settings->minCoverage;

        $options = new PipelineOptions(
            $this->branches([] !== $branches ? $branches : $settings->branches ?? ['main']),
            // Asking for a minimum only makes sense with a coverage job, so it switches one on.
            $this->flag($input, 'coverage') ?? $settings->coverage ?? null !== $minCoverage,
            $this->flag($input, 'lowest') ?? $settings->lowest ?? false,
            $this->flag($input, 'audit') ?? $settings->audit ?? false,
            $minCoverage,
        );

        return new PipelineContext($directory, $project, $options, $phpSource);
    }

    protected function pipelineFile(string $directory, Provider $provider): string
    {
        return $directory.\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $provider->path());
    }

    /**
     * @return list<Provider> the provider passed as argument, or every provider with a pipeline file in the project
     *
     * @throws InvalidInputException
     */
    protected function requestedProviders(InputInterface $input, string $directory): array
    {
        $name = $input->getArgument('provider');

        if (is_string($name) && '' !== $name) {
            return [$this->providers->get($name)];
        }

        return array_values(array_filter(
            $this->providers->all(),
            fn (Provider $provider): bool => is_file($this->pipelineFile($directory, $provider)),
        ));
    }

    /**
     * Renders the pipeline and compares it with the file in the project.
     */
    protected function state(PipelineContext $context, Provider $provider): PipelineState
    {
        $file = $this->pipelineFile($context->directory, $provider);
        $expected = $provider->render($context->project, $context->options);

        if (!is_file($file)) {
            return new PipelineState($provider, PipelineState::MISSING, $expected);
        }

        $diff = LineDiff::compare((string) file_get_contents($file), $expected);

        return new PipelineState($provider, [] === $diff ? PipelineState::CURRENT : PipelineState::OUTDATED, $expected, $diff);
    }

    /**
     * @param list<array{string, string}> $diff
     */
    protected function printDiff(OutputInterface $output, array $diff): void
    {
        $output->writeln('   <fg=red>- in your file</>  <fg=green>+ expected</>');

        foreach ($diff as [$marker, $line]) {
            $text = OutputFormatter::escape($line);

            $output->writeln(match ($marker) {
                LineDiff::REMOVED => sprintf('   <fg=red>- %s</>', $text),
                LineDiff::ADDED => sprintf('   <fg=green>+ %s</>', $text),
                LineDiff::GAP => '   <fg=gray>…</>',
                default => sprintf('     %s', $text),
            });
        }
    }

    /**
     * @throws ProjectException
     */
    protected function write(string $target, string $contents): void
    {
        $directory = dirname($target);

        if (!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw new ProjectException(sprintf('Could not create the directory "%s".', $directory));
        }

        // Write next to the target and swap it in, so a failed write never leaves a truncated pipeline behind.
        $temporary = $target.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (strlen($contents) !== @file_put_contents($temporary, $contents) || !@rename($temporary, $target)) {
            @unlink($temporary);

            throw new ProjectException(sprintf('Could not write "%s".', $target));
        }
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
            if (1 !== preg_match('~^[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $branch) || !$this->isGitBranchName($branch)) {
                throw new InvalidInputException(sprintf('Invalid branch name "%s".', $branch));
            }
        }

        return array_values(array_unique($branches));
    }

    /**
     * The rules of "git check-ref-format" that the safe alphabet does not already cover.
     */
    private function isGitBranchName(string $branch): bool
    {
        if (str_contains($branch, '..') || str_contains($branch, '//') || str_ends_with($branch, '/') || str_ends_with($branch, '.')) {
            return false;
        }

        foreach (explode('/', $branch) as $component) {
            if (str_starts_with($component, '.') || str_ends_with($component, '.lock')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string> the unique values of an array option
     *
     * @throws InvalidInputException when the option was passed without a value, e.g. from an empty shell variable
     */
    private function values(InputInterface $input, string $option): array
    {
        $values = [];

        foreach ((array) $input->getOption($option) as $value) {
            if (!is_string($value) || '' === trim($value)) {
                throw new InvalidInputException(sprintf('The --%s option needs a value.', $option));
            }

            $values[] = trim($value);
        }

        return array_values(array_unique($values));
    }

    /**
     * @return ?float null when the option was not passed
     *
     * @throws InvalidInputException
     */
    private function percentage(InputInterface $input, string $option): ?float
    {
        $value = $input->getOption($option);

        if (null === $value) {
            return null;
        }

        if (!is_string($value) || !is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
            throw new InvalidInputException(sprintf('The --%s option needs a number from 0 to 100.', $option));
        }

        return (float) $value;
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
