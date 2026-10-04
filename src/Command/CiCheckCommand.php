<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ci:check',
    description: 'Check that the committed pipeline still matches the project',
)]
final class CiCheckCommand extends PipelineCommand
{
    private const FORMATS = ['text', 'json', 'github'];

    protected function configure(): void
    {
        $this
            ->addProviderArgument('CI provider to check; every pipeline found when omitted')
            ->addPipelineOptions()
            ->addWorkingDirOption()
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (text, json, github)', 'text', self::FORMATS)
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command regenerates the pipeline in memory and compares it
                    with the file in your project. It exits with <comment>1</comment> and prints the difference when
                    the file is missing or out of date, which makes it suitable for CI:

                      <info>%command.full_name%</info>

                    A pipeline goes out of date when you add a tool, change the PHP constraint or
                    edit the file by hand. Pass the same options you generated it with, or store
                    them under <comment>extra.axonphp</comment> in composer.json so no options are needed.

                    In a GitHub Actions workflow, annotate the pull request with the result:

                      <info>%command.full_name% --format github</info>

                    Use JSON to feed the result into other tools:

                      <info>%command.full_name% --format json</info>

                    Run <info>ci:update</info> to bring outdated pipelines back in line.
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
            $providers = $this->requestedProviders($input, $context->directory);
        } catch (InvalidInputException $exception) {
            $io->getErrorStyle()->error($exception->getMessage());

            return Command::INVALID;
        } catch (ProjectException $exception) {
            $io->getErrorStyle()->error($exception->getMessage());

            return Command::FAILURE;
        }

        $states = array_map(fn ($provider): PipelineState => $this->state($context, $provider), $providers);
        $outdated = array_filter($states, static fn (PipelineState $state): bool => !$state->isCurrent());
        $passed = [] !== $states && [] === $outdated;

        if ('json' === $format) {
            $output->writeln(
                json_encode($this->report($states), \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
                OutputInterface::OUTPUT_RAW,
            );

            return $passed ? Command::SUCCESS : Command::FAILURE;
        }

        if ([] === $states) {
            $io->error('No pipeline found. Run "ci:init" to create one.');

            return Command::FAILURE;
        }

        foreach ($states as $state) {
            $path = $state->provider->path();

            if ($state->isCurrent()) {
                $io->writeln(sprintf(' <fg=green>✓</> %s is up to date', $path));

                continue;
            }

            if ('github' === $format) {
                // A workflow command: GitHub shows it as an annotation on the file in the pull request.
                $output->writeln(
                    sprintf('::error file=%s,title=Pipeline %s::Run "axonphp ci:update" and commit the result.', $path, $state->status),
                    OutputInterface::OUTPUT_RAW,
                );
            }

            if (PipelineState::MISSING === $state->status) {
                $io->writeln(sprintf(' <fg=red>✗</> %s is missing', $path));

                continue;
            }

            $io->writeln(sprintf(' <fg=red>✗</> %s is out of date', $path));
            $io->newLine();
            $this->printDiff($io, $state->diff);
            $io->newLine();
        }

        if (!$passed) {
            $io->error('The pipeline does not match the project. Run "ci:update" to regenerate it.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<PipelineState> $states
     *
     * @return array{
     *     upToDate: bool,
     *     pipelines: list<array{provider: string, path: string, status: string, diff: list<array{marker: string, line: string}>}>
     * }
     */
    private function report(array $states): array
    {
        $pipelines = [];
        $upToDate = [] !== $states;

        foreach ($states as $state) {
            $upToDate = $upToDate && $state->isCurrent();
            $pipelines[] = [
                'provider' => $state->provider->name(),
                'path' => $state->provider->path(),
                'status' => $state->status,
                'diff' => array_map(
                    static fn (array $entry): array => ['marker' => $entry[0], 'line' => $entry[1]],
                    $state->diff,
                ),
            ];
        }

        return ['upToDate' => $upToDate, 'pipelines' => $pipelines];
    }
}
