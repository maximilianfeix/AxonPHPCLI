<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\CiCheckCommand;
use AxonPHP\Cli\Command\PipelineCommand;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CiCheckCommand::class)]
#[CoversClass(PipelineCommand::class)]
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
        self::assertStringContainsString('Run "ci:init --force" to regenerate it.', $display);
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
