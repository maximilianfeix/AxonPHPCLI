<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Diff\LineDiff;
use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Provider\Provider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ci:check',
    description: 'Check that the committed pipeline still matches the project',
)]
final class CiCheckCommand extends PipelineCommand
{
    protected function configure(): void
    {
        $this
            ->addProviderArgument('CI provider to check; every pipeline found when omitted')
            ->addPipelineOptions()
            ->addWorkingDirOption()
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command regenerates the pipeline in memory and compares it
                    with the file in your project. It exits with <comment>1</comment> and prints the difference when
                    the file is missing or out of date, which makes it suitable for CI:

                      <info>%command.full_name%</info>

                    A pipeline goes out of date when you add a tool, change the PHP constraint or
                    edit the file by hand. Pass the same options you generated it with, or store
                    them under <comment>extra.axonphp</comment> in composer.json so no options are needed.
                    HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $context = $this->context($input);
            $providers = $this->providersToCheck($input, $context->directory);
        } catch (InvalidInputException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        } catch (ProjectException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if ([] === $providers) {
            $io->error('No pipeline found. Run "ci:init" to create one.');

            return Command::FAILURE;
        }

        $outdated = 0;

        foreach ($providers as $provider) {
            $file = $this->pipelineFile($context->directory, $provider);
            $expected = $provider->render($context->project, $context->options);

            if (!is_file($file)) {
                ++$outdated;
                $io->writeln(sprintf(' <fg=red>✗</> %s is missing', $provider->path()));

                continue;
            }

            $diff = LineDiff::compare((string) file_get_contents($file), $expected);

            if ([] === $diff) {
                $io->writeln(sprintf(' <fg=green>✓</> %s is up to date', $provider->path()));

                continue;
            }

            ++$outdated;
            $io->writeln(sprintf(' <fg=red>✗</> %s is out of date', $provider->path()));
            $io->newLine();
            $this->printDiff($io, $diff);
            $io->newLine();
        }

        if ($outdated > 0) {
            $io->error('The pipeline does not match the project. Run "ci:init --force" to regenerate it.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<Provider> the requested provider, or every provider with a pipeline file in the project
     */
    private function providersToCheck(InputInterface $input, string $directory): array
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
     * @param list<array{string, string}> $diff
     */
    private function printDiff(SymfonyStyle $io, array $diff): void
    {
        $io->writeln('   <fg=red>- in your file</>  <fg=green>+ expected</>');

        foreach ($diff as [$marker, $line]) {
            $text = OutputFormatter::escape($line);

            $io->writeln(match ($marker) {
                LineDiff::REMOVED => sprintf('   <fg=red>- %s</>', $text),
                LineDiff::ADDED => sprintf('   <fg=green>+ %s</>', $text),
                LineDiff::GAP => '   <fg=gray>…</>',
                default => sprintf('     %s', $text),
            });
        }
    }
}
