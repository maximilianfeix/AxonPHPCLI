<?php

declare(strict_types=1);

namespace AxonPHP\Cli;

use AxonPHP\Cli\Command\CiInitCommand;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Provider\ProviderRegistry;
use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public const NAME = 'AxonPHP CLI';
    public const VERSION = '0.2.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommands([
            new CiInitCommand(ProviderRegistry::default(), new ProjectInspector()),
        ]);
    }
}
