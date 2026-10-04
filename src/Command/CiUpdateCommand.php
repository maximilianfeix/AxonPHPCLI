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
    name: 'ci:update',
    description: 'Regenerate the pipelines that no longer match the project',
)]
final class CiUpdateCommand extends PipelineCommand
{
    protected function configure(): void
    {
        $this
            ->addProviderArgument('CI provider to update; every pipeline found when omitted')
            ->addPipelineOptions()
            ->addWorkingDirOption()
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would change without writing anything')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command rewrites every pipeline file in the project that
                    <info>ci:check</info> would report as out of date and leaves the others alone:

                      <info>%command.full_name%</info>

                    See what would change first:

                      <info>%command.full_name% --dry-run</info>

                    Options work as in <info>ci:init</info>. Store them under <comment>extra.axonphp</comment> in
                    composer.json so the update needs none.
                    HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = true === $input->getOption('dry-run');

        try {
            $context = $this->context($input);
            $providers = $this->requestedProviders($input, $context->directory);
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

        $changed = 0;

        foreach ($providers as $provider) {
            $state = $this->state($context, $provider);
            $path = $provider->path();

            if ($state->isCurrent()) {
                $io->writeln(sprintf(' <fg=green>✓</> %s is up to date', $path));

                continue;
            }

            ++$changed;
            $verb = PipelineState::MISSING === $state->status ? 'create' : 'update';

            if ($dryRun) {
                $io->writeln(sprintf(' <fg=yellow>~</> %s would be %sd', $path, $verb));
            } else {
                try {
                    $this->write($this->pipelineFile($context->directory, $provider), $state->expected);
                } catch (ProjectException $exception) {
                    $io->error($exception->getMessage());

                    return Command::FAILURE;
                }

                $io->writeln(sprintf(' <fg=green>✓</> %s %sd', $path, $verb));
            }

            if ([] !== $state->diff) {
                $io->newLine();
                $this->printDiff($io, $state->diff);
                $io->newLine();
            }
        }

        if ($dryRun && $changed > 0) {
            $io->note('Dry run: nothing was written.');
        } elseif ($changed > 0) {
            $io->success(sprintf('Updated %d pipeline%s. Review the changes and commit them.', $changed, 1 === $changed ? '' : 's'));
        }

        return Command::SUCCESS;
    }
}
