<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\CiCheckCommand;
use AxonPHP\Cli\Command\PipelineCommand;
use AxonPHP\Cli\Command\PipelineState;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CiCheckCommand::class)]
#[CoversClass(PipelineCommand::class)]
#[CoversClass(PipelineState::class)]
final class CiCheckCommandTest extends TestCase
{
    use TemporaryProject;

    private const MANIFEST = [
        'require' => ['php' => '^8.3'],
        'require-dev' => ['phpunit/phpunit' => '^12.0'],
    ];

    public function testPassesWhenEveryPipelineIsUpToDate(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');
        $this->generate($directory, 'gitlab');
        $tester = $this->tester('ci:check');

        $exitCode = $tester->execute(['--working-dir' => $directory]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('.github/workflows/ci.yml is up to date', $tester->getDisplay());
        self::assertStringContainsString('.gitlab-ci.yml is up to date', $tester->getDisplay());
        self::assertStringNotContainsString('bitbucket', $tester->getDisplay());
    }

    public function testFailsAndShowsTheDifferenceWhenTheProjectChanged(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');

        // The project starts using PHPStan, so the committed workflow is missing a job.
        $manifest = self::MANIFEST;
        $manifest['require-dev']['phpstan/phpstan'] = '^2.0';
        file_put_contents($directory.'/composer.json', json_encode($manifest, \JSON_THROW_ON_ERROR));

        $tester = $this->tester('ci:check');
        $exitCode = $tester->execute(['--working-dir' => $directory]);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = $tester->getDisplay();
        self::assertStringContainsString('.github/workflows/ci.yml is out of date', $display);
        self::assertStringContainsString('+   quality:', $display);
        self::assertStringContainsString('+         run: vendor/bin/phpstan analyse --no-progress', $display);
        // The closing message wraps on narrow terminals.
        self::assertStringContainsString(
            'Run "ci:update" to regenerate it.',
            (string) preg_replace('/\s+/', ' ', $display),
        );
    }

    public function testFailsWhenTheOptionsDiffer(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $directory, '--branch' => ['develop']]));
        self::assertStringContainsString("-     branches: ['main']", $tester->getDisplay());
        self::assertStringContainsString("+     branches: ['develop']", $tester->getDisplay());
    }

    public function testFailsWhenTheRequestedPipelineIsMissing(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['provider' => 'gitlab', '--working-dir' => $directory]));
        self::assertStringContainsString('.gitlab-ci.yml is missing', $tester->getDisplay());
    }

    public function testFailsWhenTheProjectHasNoPipelineAtAll(): void
    {
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $this->createProject(self::MANIFEST)]));
        self::assertStringContainsString('No pipeline found', $tester->getDisplay());
    }

    public function testRejectsUnknownProviders(): void
    {
        $tester = $this->tester('ci:check');

        self::assertSame(Command::INVALID, $tester->execute(['provider' => 'jenkins', '--working-dir' => $this->createProject()]));
        self::assertStringContainsString('Unknown provider "jenkins"', $tester->getDisplay());
    }

    public function testReportsAsJson(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');
        file_put_contents($directory.'/.gitlab-ci.yml', "stages: []\n");
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $directory, '--format' => 'json']));

        $report = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($report);
        self::assertFalse($report['upToDate']);
        self::assertIsArray($report['pipelines']);
        self::assertSame(
            ['provider' => 'github', 'path' => '.github/workflows/ci.yml', 'status' => 'up-to-date', 'diff' => []],
            $report['pipelines'][0],
        );
        self::assertIsArray($report['pipelines'][1]);
        self::assertSame('outdated', $report['pipelines'][1]['status']);
        self::assertIsArray($report['pipelines'][1]['diff']);
        self::assertSame(['marker' => '-', 'line' => 'stages: []'], $report['pipelines'][1]['diff'][0]);
    }

    public function testJsonReportsSuccessAndAnEmptyProject(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $directory, '--format' => 'json']));
        self::assertSame(
            ['upToDate' => false, 'pipelines' => []],
            json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR),
        );

        $this->generate($directory, 'bitbucket');

        self::assertSame(Command::SUCCESS, $tester->execute(['--working-dir' => $directory, '--format' => 'json']));
        self::assertStringContainsString('"upToDate": true', $tester->getDisplay());
    }

    public function testAnnotatesOutdatedAndMissingPipelinesForGitHub(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', "stages: []\n");
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $directory, '--format' => 'github']));
        self::assertStringContainsString(
            '::error file=.gitlab-ci.yml,title=Pipeline outdated::Run "axonphp ci:update" and commit the result.',
            $tester->getDisplay(),
        );

        self::assertSame(
            Command::FAILURE,
            $tester->execute(['provider' => 'github', '--working-dir' => $directory, '--format' => 'github']),
        );
        self::assertStringContainsString('::error file=.github/workflows/ci.yml,title=Pipeline missing::', $tester->getDisplay());
    }

    public function testRejectsUnknownFormats(): void
    {
        $tester = $this->tester('ci:check');

        self::assertSame(Command::INVALID, $tester->execute(['--working-dir' => $this->createProject(), '--format' => 'xml']));
        self::assertStringContainsString('Unknown format "xml". Supported formats: text, json, github.', $tester->getDisplay());
    }

    public function testReportsAnUnreadableManifest(): void
    {
        $tester = $this->tester('ci:check');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $this->createProject('{ broken')]));
        self::assertStringContainsString('is not valid JSON', $tester->getDisplay());
    }

    private function generate(string $directory, string $provider): void
    {
        $exitCode = $this->tester('ci:init')->execute(
            ['provider' => $provider, '--working-dir' => $directory],
            ['interactive' => false],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    private function tester(string $command): CommandTester
    {
        return new CommandTester((new Application())->find($command));
    }
}
