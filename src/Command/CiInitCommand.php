<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Project\PhpVersionResolver;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\Provider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ci:init',
    description: 'Generate a CI pipeline tailored to your PHP project',
)]
final class CiInitCommand extends PipelineCommand
{
    protected function configure(): void
    {
        $this
            ->addProviderArgument('CI provider to generate the pipeline for')
            ->addPipelineOptions()
            ->addWorkingDirOption()
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

                    Add a coverage job, a lowest-dependencies job and a security audit:

                      <info>%command.full_name% github --coverage --lowest --audit</info>

                    Fail the pipeline when line coverage drops below a percentage:

                      <info>%command.full_name% github --min-coverage 90</info>

                    To make such choices permanent, store them in <comment>composer.json</comment>:

                      <comment>"extra": { "axonphp": { "branches": ["main", "develop"], "coverage": true } }</comment>
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
            $context = $this->context($input);
        } catch (InvalidInputException $exception) {
            $ui->error($exception->getMessage());

            return Command::INVALID;
        } catch (ProjectException $exception) {
            $ui->error($exception->getMessage());

            return Command::FAILURE;
        }

        $this->summarize($ui, $provider, $context);

        $pipeline = $provider->render($context->project, $context->options);

        if ($dryRun) {
            $output->write($pipeline, false, OutputInterface::OUTPUT_RAW);
            $ui->note(sprintf('Dry run: %s was not written.', $provider->path()));

            return Command::SUCCESS;
        }

        $target = $this->pipelineFile($context->directory, $provider);

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

    private function summarize(SymfonyStyle $ui, Provider $provider, PipelineContext $context): void
    {
        $project = $context->project;
        $options = $context->options;

        $rows = [
            ['Project' => $context->directory],
            ['PHP versions' => sprintf('%s <comment>(%s)</comment>', implode(', ', $project->phpVersions), $context->phpSource)],
            ['Extensions' => [] === $project->extensions ? '<comment>none</comment>' : implode(', ', $project->extensions)],
        ];

        foreach (ToolType::cases() as $type) {
            $names = array_map(static fn (Tool $tool): string => $tool->name, $project->tools($type));
            $rows[] = [$type->label() => [] === $names ? '<comment>not detected</comment>' : implode(', ', $names)];
        }

        $extras = array_keys(array_filter([
            'coverage' => $options->coverage && null !== $project->coverageTool(),
            'lowest dependencies' => $options->lowest && $project->usesComposer,
            'security audit' => $options->audit && $project->usesComposer,
        ]));

        if (null !== $options->minCoverage && 'coverage' === ($extras[0] ?? null)) {
            $extras[0] = sprintf('coverage (at least %s%%)', Plan::percentage($options->minCoverage));
        }

        $rows[] = ['Branches' => implode(', ', $options->branches)];
        $rows[] = ['Extras' => [] === $extras ? '<comment>none</comment>' : implode(', ', $extras)];

        $ui->title(sprintf('AxonPHP CLI · %s', $provider->label()));

        if (!$project->usesComposer) {
            $ui->warning('No composer.json found. Generating a minimal pipeline that only lints PHP files.');
        } elseif (!$project->phpResolved) {
            $ui->warning(sprintf(
                'The "php" constraint "%s" allows none of the PHP versions AxonPHP knows (%s to %s). Testing the default versions instead; pass --php to choose them yourself.',
                (string) $project->phpConstraint,
                PhpVersionResolver::KNOWN[0],
                PhpVersionResolver::KNOWN[count(PhpVersionResolver::KNOWN) - 1],
            ));
        }

        if ($project->usesComposer && $options->coverage && null === $project->coverageTool()) {
            $ui->warning('Coverage was requested, but the project has no test runner that can measure it (PHPUnit or Pest).');
        }

        $ui->definitionList(...$rows);
    }
}
