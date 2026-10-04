<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\InspectCommand;
use AxonPHP\Cli\Command\PipelineCommand;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(InspectCommand::class)]
#[CoversClass(PipelineCommand::class)]
final class InspectCommandTest extends TestCase
{
    use TemporaryProject;

    private const MANIFEST = [
        'name' => 'acme/app',
        'require' => ['php' => '^8.4', 'ext-intl' => '*'],
        'require-dev' => ['pestphp/pest' => '^4.0', 'rector/rector' => '^2.0'],
        'extra' => ['axonphp' => ['coverage' => true]],
    ];

    public function testPrintsTheDetectionAsJson(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', 'stages: []');
        $tester = $this->tester();

        $exitCode = $tester->execute(['--working-dir' => $directory, '--format' => 'json', '--audit' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(
            [
                'directory' => $directory,
                'name' => 'acme/app',
                'composer' => true,
                'php' => ['constraint' => '^8.4', 'versions' => ['8.4', '8.5'], 'source' => 'from "php": "^8.4"'],
                'extensions' => ['intl'],
                'tools' => [
                    ['name' => 'Pest', 'type' => 'tests', 'command' => 'vendor/bin/pest'],
                    ['name' => 'Rector', 'type' => 'static-analysis', 'command' => 'vendor/bin/rector process --dry-run'],
                ],
                'options' => ['branches' => ['main'], 'coverage' => true, 'min-coverage' => null, 'lowest' => false, 'audit' => true],
                'pipelines' => [
                    ['provider' => 'github', 'label' => 'GitHub Actions', 'path' => '.github/workflows/ci.yml', 'exists' => false],
                    ['provider' => 'gitlab', 'label' => 'GitLab CI', 'path' => '.gitlab-ci.yml', 'exists' => true],
                    ['provider' => 'bitbucket', 'label' => 'Bitbucket Pipelines', 'path' => 'bitbucket-pipelines.yml', 'exists' => false],
                    ['provider' => 'circleci', 'label' => 'CircleCI', 'path' => '.circleci/config.yml', 'exists' => false],
                ],
            ],
            json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function testPrintsAReadableReport(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--working-dir' => $this->createProject(self::MANIFEST)]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $tester->getDisplay();
        self::assertStringContainsString('acme/app', $display);
        self::assertStringContainsString('8.4, 8.5 (from "php": "^8.4")', $display);
        self::assertStringContainsString('vendor/bin/rector process --dry-run', $display);
        self::assertStringContainsString('Bitbucket Pipelines', $display);
    }

    public function testExplainsTheFallbackWhenNothingIsDetected(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--working-dir' => $this->createProject()]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No composer.json found', $tester->getDisplay());
        self::assertStringContainsString('None detected.', $tester->getDisplay());
    }

    public function testShowsTheMinimumCoverage(): void
    {
        $tester = $this->tester();

        $tester->execute(['--working-dir' => $this->createProject(self::MANIFEST), '--min-coverage' => '80']);

        self::assertMatchesRegularExpression('/Minimum coverage\s+80%/', $tester->getDisplay());
    }

    public function testReportsAnUnreadableManifest(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $this->createProject('[]')]));
        self::assertStringContainsString('must contain a JSON object', $tester->getDisplay());
    }

    public function testRejectsUnknownFormats(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--working-dir' => $this->createProject(), '--format' => 'xml']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('Unknown format "xml"', $tester->getDisplay());
    }

    private function tester(): CommandTester
    {
        return new CommandTester((new Application())->find('inspect'));
    }
}
