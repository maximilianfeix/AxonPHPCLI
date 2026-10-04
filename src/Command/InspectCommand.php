<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Provider\Provider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'inspect',
    description: 'Show what AxonPHP detects in your project',
)]
final class InspectCommand extends PipelineCommand
{
    private const FORMATS = ['text', 'json'];

    protected function configure(): void
    {
        $this
            ->addPipelineOptions()
            ->addWorkingDirOption()
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (text, json)', 'text', self::FORMATS)
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command prints what a generated pipeline would be based on,
                    without writing anything: PHP versions, extensions, tools and options.

                      <info>%command.full_name%</info>

                    Use JSON to feed the result into other tools:

                      <info>%command.full_name% --format json</info>
                    HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');

        try {
            if (!in_array($format, self::FORMATS, true)) {
                throw new InvalidInputException(sprintf('Unknown format "%s". Supported formats: %s.', is_scalar($format) ? (string) $format : '', implode(', ', self::FORMATS)));
            }

            $context = $this->context($input);
        } catch (InvalidInputException $exception) {
            $io->getErrorStyle()->error($exception->getMessage());

            return Command::INVALID;
        } catch (ProjectException $exception) {
            $io->getErrorStyle()->error($exception->getMessage());

            return Command::FAILURE;
        }

        $report = $this->report($context);

        if ('json' === $format) {
            $output->writeln(
                json_encode($report, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
                OutputInterface::OUTPUT_RAW,
            );

            return Command::SUCCESS;
        }

        $this->printText($io, $report);

        return Command::SUCCESS;
    }

    /**
     * @return array{
     *     directory: string,
     *     name: ?string,
     *     composer: bool,
     *     php: array{constraint: ?string, versions: list<string>, source: string},
     *     extensions: list<string>,
     *     tools: list<array{name: string, type: string, command: string}>,
     *     options: array{branches: list<string>, coverage: bool, min-coverage: ?float, lowest: bool, audit: bool},
     *     pipelines: list<array{provider: string, label: string, path: string, exists: bool}>
     * }
     */
    private function report(PipelineContext $context): array
    {
        $project = $context->project;
        $options = $context->options;

        return [
            'directory' => $context->directory,
            'name' => $project->name,
            'composer' => $project->usesComposer,
            'php' => [
                'constraint' => $project->phpConstraint,
                'versions' => $project->phpVersions,
                'source' => $context->phpSource,
            ],
            'extensions' => $project->extensions,
            'tools' => array_map(
                static fn (Tool $tool): array => [
                    'name' => $tool->name,
                    'type' => $tool->type->value,
                    'command' => $tool->command,
                ],
                $project->tools,
            ),
            'options' => [
                'branches' => $options->branches,
                'coverage' => $options->coverage,
                'min-coverage' => $options->minCoverage,
                'lowest' => $options->lowest,
                'audit' => $options->audit,
            ],
            'pipelines' => array_map(
                fn (Provider $provider): array => [
                    'provider' => $provider->name(),
                    'label' => $provider->label(),
                    'path' => $provider->path(),
                    'exists' => is_file($this->pipelineFile($context->directory, $provider)),
                ],
                $this->providers->all(),
            ),
        ];
    }

    /**
     * @param array{
     *     directory: string,
     *     name: ?string,
     *     composer: bool,
     *     php: array{constraint: ?string, versions: list<string>, source: string},
     *     extensions: list<string>,
     *     tools: list<array{name: string, type: string, command: string}>,
     *     options: array{branches: list<string>, coverage: bool, min-coverage: ?float, lowest: bool, audit: bool},
     *     pipelines: list<array{provider: string, label: string, path: string, exists: bool}>
     * } $report
     */
    private function printText(SymfonyStyle $io, array $report): void
    {
        $yesNo = static fn (bool $value): string => $value ? 'yes' : '<comment>no</comment>';

        $io->title(sprintf('AxonPHP CLI · %s', $report['name'] ?? basename($report['directory'])));

        if (!$report['composer']) {
            $io->warning('No composer.json found. Only the defaults apply.');
        }

        $io->definitionList(
            ['Project' => $report['directory']],
            ['PHP versions' => sprintf('%s <comment>(%s)</comment>', implode(', ', $report['php']['versions']), $report['php']['source'])],
            ['Extensions' => [] === $report['extensions'] ? '<comment>none</comment>' : implode(', ', $report['extensions'])],
            ['Branches' => implode(', ', $report['options']['branches'])],
            ['Coverage' => $yesNo($report['options']['coverage'])],
            ['Minimum coverage' => null === $report['options']['min-coverage'] ? '<comment>none</comment>' : Plan::percentage($report['options']['min-coverage']).'%'],
            ['Lowest dependencies' => $yesNo($report['options']['lowest'])],
            ['Security audit' => $yesNo($report['options']['audit'])],
        );

        $io->section('Tools');

        if ([] === $report['tools']) {
            $io->writeln(' <comment>None detected.</comment> The pipeline would lint PHP files with "php -l".');
            $io->newLine();
        } else {
            $io->table(
                ['Tool', 'Type', 'Command'],
                array_map(static fn (array $tool): array => [$tool['name'], $tool['type'], $tool['command']], $report['tools']),
            );
        }

        $io->section('Pipelines');
        $io->table(
            ['Provider', 'File', 'Present'],
            array_map(
                static fn (array $pipeline): array => [$pipeline['label'], $pipeline['path'], $yesNo($pipeline['exists'])],
                $report['pipelines'],
            ),
        );
    }
}
