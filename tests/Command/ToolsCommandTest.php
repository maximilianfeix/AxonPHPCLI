<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\ToolsCommand;
use AxonPHP\Cli\Project\ToolCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ToolsCommand::class)]
#[CoversClass(Application::class)]
final class ToolsCommandTest extends TestCase
{
    public function testListsToolsAndProviders(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('PHPStan', $display);
        self::assertStringContainsString('larastan/larastan', $display);
        self::assertStringContainsString('vendor/bin/phpstan analyse --no-progress', $display);
        self::assertStringContainsString('CircleCI', $display);
        self::assertStringContainsString('.circleci/config.yml', $display);
    }

    public function testListsEverythingAsJson(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['--format' => 'json']));

        $report = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($report);
        self::assertIsArray($report['tools']);
        self::assertCount(count(ToolCatalog::definitions()), $report['tools']);
        self::assertSame(
            [
                'name' => 'Pest',
                'type' => 'tests',
                'packages' => ['pestphp/pest'],
                'command' => 'vendor/bin/pest',
                'coverage' => true,
            ],
            $report['tools'][0],
        );
        self::assertIsArray($report['providers']);
        self::assertSame(
            ['provider' => 'github', 'label' => 'GitHub Actions', 'path' => '.github/workflows/ci.yml'],
            $report['providers'][0],
        );
    }

    public function testRejectsUnknownFormats(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute(['--format' => 'xml']));
        self::assertStringContainsString('Unknown format "xml". Supported formats: text, json.', $tester->getDisplay());
    }

    public function testTheApplicationRegistersEveryCommand(): void
    {
        $application = new Application();

        self::assertSame(Application::NAME, $application->getName());
        self::assertSame(Application::VERSION, $application->getVersion());

        foreach (['ci:init', 'ci:check', 'ci:update', 'inspect', 'tools'] as $name) {
            self::assertTrue($application->has($name), $name);
        }
    }

    private function tester(): CommandTester
    {
        return new CommandTester((new Application())->find('tools'));
    }
}
