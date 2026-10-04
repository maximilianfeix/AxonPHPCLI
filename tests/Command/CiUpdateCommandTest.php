<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\CiUpdateCommand;
use AxonPHP\Cli\Command\PipelineCommand;
use AxonPHP\Cli\Command\PipelineState;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CiUpdateCommand::class)]
#[CoversClass(PipelineCommand::class)]
#[CoversClass(PipelineState::class)]
final class CiUpdateCommandTest extends TestCase
{
    use TemporaryProject;

    private const MANIFEST = [
        'require' => ['php' => '^8.3'],
        'require-dev' => ['phpunit/phpunit' => '^12.0'],
    ];

    public function testRewritesOnlyThePipelinesThatAreOutOfDate(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');
        $this->generate($directory, 'gitlab');
        $gitlab = (string) file_get_contents($directory.'/.gitlab-ci.yml');
        file_put_contents($directory.'/.github/workflows/ci.yml', "name: mine\n");

        $tester = $this->tester('ci:update');
        $exitCode = $tester->execute(['--working-dir' => $directory]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
        self::assertStringContainsString('.github/workflows/ci.yml updated', $display);
        self::assertStringContainsString('- name: mine', $display);
        self::assertStringContainsString('.gitlab-ci.yml is up to date', $display);
        self::assertStringContainsString('Updated 1 pipeline. Review the changes and commit them.', $display);
        self::assertSame($gitlab, file_get_contents($directory.'/.gitlab-ci.yml'));

        self::assertSame(Command::SUCCESS, $this->tester('ci:check')->execute(['--working-dir' => $directory]));
    }

    public function testDoesNothingWhenEverythingIsUpToDate(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');

        $tester = $this->tester('ci:update');

        self::assertSame(Command::SUCCESS, $tester->execute(['--working-dir' => $directory]));
        self::assertStringContainsString('.github/workflows/ci.yml is up to date', $tester->getDisplay());
        self::assertStringNotContainsString('Updated', $tester->getDisplay());
    }

    public function testDryRunShowsTheDifferenceWithoutWriting(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', "stages: []\n");
        file_put_contents($directory.'/bitbucket-pipelines.yml', "image: php\n");

        $tester = $this->tester('ci:update');
        $exitCode = $tester->execute(['--working-dir' => $directory, '--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('.gitlab-ci.yml would be updated', $tester->getDisplay());
        self::assertStringContainsString('- stages: []', $tester->getDisplay());
        self::assertStringContainsString('Dry run: nothing was written.', $tester->getDisplay());
        self::assertSame("stages: []\n", file_get_contents($directory.'/.gitlab-ci.yml'));
    }

    public function testCreatesTheRequestedPipelineWhenItIsMissing(): void
    {
        $directory = $this->createProject(self::MANIFEST);

        $tester = $this->tester('ci:update');
        $exitCode = $tester->execute(['provider' => 'circleci', '--working-dir' => $directory]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('.circleci/config.yml created', $tester->getDisplay());
        self::assertFileExists($directory.'/.circleci/config.yml');
    }

    public function testAppliesTheOptionsItIsGiven(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $this->generate($directory, 'github');
        $this->generate($directory, 'gitlab');

        $tester = $this->tester('ci:update');
        $tester->execute(['--working-dir' => $directory, '--branch' => ['trunk']]);

        self::assertStringContainsString('Updated 2 pipelines.', $tester->getDisplay());
        self::assertStringContainsString("branches: ['trunk']", (string) file_get_contents($directory.'/.github/workflows/ci.yml'));
    }

    public function testFailsWhenTheProjectHasNoPipeline(): void
    {
        $tester = $this->tester('ci:update');

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $this->createProject(self::MANIFEST)]));
        self::assertStringContainsString('No pipeline found', $tester->getDisplay());
    }

    public function testReportsInvalidInputAndBrokenProjects(): void
    {
        $tester = $this->tester('ci:update');

        self::assertSame(Command::INVALID, $tester->execute(['provider' => 'jenkins', '--working-dir' => $this->createProject()]));
        self::assertStringContainsString('Unknown provider "jenkins"', $tester->getDisplay());

        self::assertSame(Command::FAILURE, $tester->execute(['--working-dir' => $this->createProject('{ broken')]));
        self::assertStringContainsString('is not valid JSON', $tester->getDisplay());
    }

    public function testReportsAPipelineThatCannotBeWritten(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        // A file where the directory of the configuration has to go.
        file_put_contents($directory.'/.circleci', '');

        $tester = $this->tester('ci:update');

        self::assertSame(Command::FAILURE, $tester->execute(['provider' => 'circleci', '--working-dir' => $directory]));
        self::assertStringContainsString('Could not create the directory', (string) preg_replace('/\s+/', ' ', $tester->getDisplay()));
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
