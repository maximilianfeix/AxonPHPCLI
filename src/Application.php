<?php

declare(strict_types=1);

namespace AxonPHP\Cli;

use AxonPHP\Cli\Command\CiCheckCommand;
use AxonPHP\Cli\Command\CiInitCommand;
use AxonPHP\Cli\Command\CiUpdateCommand;
use AxonPHP\Cli\Command\InspectCommand;
use AxonPHP\Cli\Command\ToolsCommand;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public const NAME = 'AxonPHP CLI';
    public const VERSION = '0.5.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $providers = ProviderRegistry::default();
        $inspector = new ProjectInspector();

        $this->addCommands([
            new CiInitCommand($providers, $inspector),
            new CiCheckCommand($providers, $inspector),
            new CiUpdateCommand($providers, $inspector),
            new InspectCommand($providers, $inspector),
            new ToolsCommand($providers),
        ]);
    }
}
