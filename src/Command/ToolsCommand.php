<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Provider\Provider;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tools',
    description: 'List the tools and CI providers AxonPHP supports',
)]
final class ToolsCommand extends Command
{
    private const FORMATS = ['text', 'json'];

    public function __construct(private readonly ProviderRegistry $providers)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (text, json)', 'text', self::FORMATS)
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command lists every tool AxonPHP detects, the Composer
                    package that triggers it and the command the pipeline runs for it:

                      <info>%command.full_name%</info>

                    To see which of them your project uses, run <info>inspect</info>.
                    HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');

        if (!in_array($format, self::FORMATS, true)) {
            $io->getErrorStyle()->error(sprintf(
                'Unknown format "%s". Supported formats: %s.',
                is_scalar($format) ? (string) $format : '',
                implode(', ', self::FORMATS),
            ));

            return Command::INVALID;
        }

        $tools = [];

        foreach (ToolCatalog::definitions() as [$packages, $tool]) {
            $tools[] = [
                'name' => $tool->name,
                'type' => $tool->type->value,
                'packages' => $packages,
                'command' => $tool->command,
                'coverage' => null !== $tool->coverageCommand,
            ];
        }

        $providers = array_map(
            static fn (Provider $provider): array => [
                'provider' => $provider->name(),
                'label' => $provider->label(),
                'path' => $provider->path(),
            ],
            $this->providers->all(),
        );

        if ('json' === $format) {
            $output->writeln(
                json_encode(['tools' => $tools, 'providers' => $providers], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
                OutputInterface::OUTPUT_RAW,
            );

            return Command::SUCCESS;
        }

        $io->title('AxonPHP CLI · supported tools');
        $io->table(
            ['Tool', 'Type', 'Detected from', 'Command'],
            array_map(
                static fn (array $tool): array => [$tool['name'], $tool['type'], implode("\n", $tool['packages']), $tool['command']],
                $tools,
            ),
        );

        $io->section('Providers');
        $io->table(
            ['Provider', 'Name', 'File'],
            array_map(static fn (array $provider): array => [$provider['label'], $provider['provider'], $provider['path']], $providers),
        );

        return Command::SUCCESS;
    }
}
