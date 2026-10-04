<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\Provider;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ci:init',
    description: 'Generate a CI pipeline tailored to your PHP project',
)]
final class CiInitCommand extends Command
{
    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly ProjectInspector $inspector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $providers = $this->providers->names();

        $this
            ->addArgument(
                'provider',
                InputArgument::OPTIONAL,
                sprintf('CI provider to generate the pipeline for (%s)', implode(', ', $providers)),
                null,
                $providers,
            )
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
                'Branch whose pushes trigger the pipeline, repeatable',
                ['main'],
            )
            ->addOption(
                'working-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'Project directory (default: current directory)',
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing pipeline file')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the pipeline instead of writing it')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command reads <comment>composer.json</comment> and writes a pipeline that
                    matches the project: the PHP versions it supports, the extensions it
                    needs, and the test, static analysis and code style tools it uses.

                      <info>%command.full_name% github</info>

                    Preview the result without touching the file system:

                      <info>%command.full_name% gitlab --dry-run</info>

                    Pin the PHP versions and trigger branches yourself:

                      <info>%command.full_name% github --php 8.3 --php 8.4 --branch main --branch develop</info>
                    HELP
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('provider')) {
            return;
        }

        $labels = $this->providers->labels();
        $io = new SymfonyStyle($input, $output);

        $input->setArgument('provider', $io->choice('Which CI provider do you use?', $labels, array_key_first($labels)));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = true === $input->getOption('dry-run');
        // Keep stdout clean in dry-run mode so the pipeline can be piped or redirected.
        $ui = $dryRun ? $io->getErrorStyle() : $io;

        try {
            $provider = $this->provider($input);
            $directory = $this->directory($input);
            $branches = $this->branches($input);
            $project = $this->inspector->inspect($directory);
            $phpVersions = $this->phpVersions($input);

            if ([] !== $phpVersions) {
                $project = $project->withPhpVersions($phpVersions);
            }
        } catch (InvalidInputException $exception) {
            $ui->error($exception->getMessage());

            return Command::INVALID;
        } catch (ProjectException $exception) {
            $ui->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->summarize($ui, $provider, $project, $directory, [] !== $phpVersions);

        $pipeline = $provider->render($project, $branches);

        if ($dryRun) {
            $output->write($pipeline, false, OutputInterface::OUTPUT_RAW);
            $ui->note(sprintf('Dry run: %s was not written.', $provider->path()));

            return Command::SUCCESS;
        }

        $target = $directory.\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $provider->path());

        if (is_file($target) && true !== $input->getOption('force')) {
            $question = sprintf('%s already exists. Overwrite it?', $provider->path());

            if (!$input->isInteractive() || !$io->confirm($question, false)) {
                $io->error(sprintf('%s already exists. Use --force to overwrite it.', $provider->path()));

                return Command::FAILURE;
            }
        }

        try {
            $this->write($target, $pipeline);
        } catch (ProjectException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Created %s', $provider->path()));
        $io->writeln(' Commit the file and push to see your first pipeline run.');
        $io->newLine();

        return Command::SUCCESS;
    }

    private function provider(InputInterface $input): Provider
    {
        $name = $input->getArgument('provider');

        if (!is_string($name) || '' === $name) {
            throw new InvalidInputException(sprintf('Pass a provider: %s.', implode(', ', $this->providers->names())));
        }

        return $this->providers->get($name);
    }

    private function directory(InputInterface $input): string
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
     * @return list<string>
     */
    private function phpVersions(InputInterface $input): array
    {
        $versions = $this->values($input, 'php');

        foreach ($versions as $version) {
            if (1 !== preg_match('/^\d+\.\d+$/', $version)) {
                throw new InvalidInputException(sprintf('Invalid PHP version "%s". Use the "major.minor" form, e.g. 8.4.', $version));
            }
        }

        usort($versions, static fn (string $a, string $b): int => version_compare($a, $b));

        return $versions;
    }

    /**
     * @return list<string>
     */
    private function branches(InputInterface $input): array
    {
        $branches = $this->values($input, 'branch');

        foreach ($branches as $branch) {
            if (1 !== preg_match('~^[A-Za-z0-9][A-Za-z0-9._/-]*$~', $branch)) {
                throw new InvalidInputException(sprintf('Invalid branch name "%s".', $branch));
            }
        }

        return $branches;
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

    private function summarize(
        SymfonyStyle $ui,
        Provider $provider,
        Project $project,
        string $directory,
        bool $phpVersionsOverridden,
    ): void {
        $phpSource = match (true) {
            $phpVersionsOverridden => 'from --php',
            null !== $project->phpConstraint => sprintf('from "php": "%s"', $project->phpConstraint),
            default => 'default',
        };

        $rows = [
            ['Project' => $directory],
            ['PHP versions' => sprintf('%s <comment>(%s)</comment>', implode(', ', $project->phpVersions), $phpSource)],
            ['Extensions' => [] === $project->extensions ? '<comment>none</comment>' : implode(', ', $project->extensions)],
        ];

        foreach (ToolType::cases() as $type) {
            $names = array_map(static fn (Tool $tool): string => $tool->name, $project->tools($type));
            $rows[] = [$type->label() => [] === $names ? '<comment>not detected</comment>' : implode(', ', $names)];
        }

        $ui->title(sprintf('AxonPHP CLI · %s', $provider->label()));

        if (!$project->usesComposer) {
            $ui->warning('No composer.json found. Generating a minimal pipeline that only lints PHP files.');
        }

        $ui->definitionList(...$rows);
    }

    private function write(string $target, string $contents): void
    {
        $directory = dirname($target);

        if (!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw new ProjectException(sprintf('Could not create the directory "%s".', $directory));
        }

        if (false === @file_put_contents($target, $contents)) {
            throw new ProjectException(sprintf('Could not write "%s".', $target));
        }
    }
}
