<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Command;

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Command\CiInitCommand;
use AxonPHP\Cli\Tests\ReadsYaml;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CiInitCommand::class)]
#[CoversClass(Application::class)]
final class CiInitCommandTest extends TestCase
{
    use ReadsYaml;
    use TemporaryProject;

    private const MANIFEST = [
        'require' => ['php' => '^8.3', 'ext-intl' => '*'],
        'require-dev' => ['phpunit/phpunit' => '^12.0', 'phpstan/phpstan' => '^2.0'],
    ];

    public function testWritesGitHubWorkflow(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester();

        $exitCode = $tester->execute(['provider' => 'github', '--working-dir' => $directory], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $tester->getDisplay();
        self::assertStringContainsString('GitHub Actions', $display);
        self::assertStringContainsString('8.3, 8.4, 8.5 (from "php": "^8.3")', $display);
        self::assertStringContainsString('PHPUnit', $display);
        self::assertStringContainsString('Created .github/workflows/ci.yml', $display);

        $workflow = self::read($directory.'/.github/workflows/ci.yml');
        self::assertSame(['8.3', '8.4', '8.5'], self::yaml($workflow, 'jobs', 'tests', 'strategy', 'matrix', 'php'));
        self::assertSame(['main'], self::yaml($workflow, 'on', 'push', 'branches'));
    }

    public function testWritesGitLabPipeline(): void
    {
        $directory = $this->createProject(self::MANIFEST);

        $exitCode = $this->tester()->execute(
            ['provider' => 'gitlab', '--working-dir' => $directory],
            ['interactive' => false],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(['quality', 'test'], self::yaml(self::read($directory.'/.gitlab-ci.yml'), 'stages'));
    }

    public function testOptionsOverrideDetectedVersionsAndBranches(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester();

        $tester->execute(
            [
                'provider' => 'github',
                '--working-dir' => $directory,
                '--php' => ['8.5', '8.4', '8.4'],
                '--branch' => ['main', 'develop'],
            ],
            ['interactive' => false],
        );

        $workflow = self::read($directory.'/.github/workflows/ci.yml');
        self::assertStringContainsString('8.4, 8.5 (from --php)', $tester->getDisplay());
        self::assertSame(['8.4', '8.5'], self::yaml($workflow, 'jobs', 'tests', 'strategy', 'matrix', 'php'));
        self::assertSame(['main', 'develop'], self::yaml($workflow, 'on', 'push', 'branches'));
    }

    public function testDryRunPrintsThePipelineWithoutWritingIt(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester();

        $exitCode = $tester->execute(
            ['provider' => 'github', '--working-dir' => $directory, '--dry-run' => true],
            ['interactive' => false, 'capture_stderr_separately' => true],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileDoesNotExist($directory.'/.github/workflows/ci.yml');
        // stdout carries nothing but the pipeline, so it can be redirected into a file.
        self::assertSame('CI', self::yaml($tester->getDisplay(), 'name'));
        self::assertStringContainsString('Dry run', $tester->getErrorOutput());
    }

    public function testRefusesToOverwriteWithoutForce(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', 'custom');
        $tester = $this->tester();

        $exitCode = $tester->execute(['provider' => 'gitlab', '--working-dir' => $directory], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Use --force to overwrite it.', $tester->getDisplay());
        self::assertSame('custom', self::read($directory.'/.gitlab-ci.yml'));
    }

    public function testForceOverwritesAnExistingPipeline(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', 'custom');

        $exitCode = $this->tester()->execute(
            ['provider' => 'gitlab', '--working-dir' => $directory, '--force' => true],
            ['interactive' => false],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNotSame('custom', self::read($directory.'/.gitlab-ci.yml'));
    }

    public function testAsksBeforeOverwritingInInteractiveMode(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        file_put_contents($directory.'/.gitlab-ci.yml', 'custom');

        $declined = $this->tester();
        $declined->setInputs(['no']);
        self::assertSame(Command::FAILURE, $declined->execute(['provider' => 'gitlab', '--working-dir' => $directory]));
        self::assertSame('custom', self::read($directory.'/.gitlab-ci.yml'));

        $confirmed = $this->tester();
        $confirmed->setInputs(['yes']);
        self::assertSame(Command::SUCCESS, $confirmed->execute(['provider' => 'gitlab', '--working-dir' => $directory]));
        self::assertNotSame('custom', self::read($directory.'/.gitlab-ci.yml'));
    }

    public function testAsksForTheProviderWhenNoneIsGiven(): void
    {
        $directory = $this->createProject(self::MANIFEST);
        $tester = $this->tester();
        $tester->setInputs(['gitlab']);

        $exitCode = $tester->execute(['--working-dir' => $directory]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Which CI provider do you use?', $tester->getDisplay());
        self::assertFileExists($directory.'/.gitlab-ci.yml');
    }

    public function testRequiresAProviderWhenNotInteractive(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['--working-dir' => $this->createProject()], ['interactive' => false]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('Pass a provider: github, gitlab, bitbucket.', $tester->getDisplay());
    }

    public function testRejectsInvalidInput(): void
    {
        $directory = $this->createProject(self::MANIFEST);

        $cases = [
            'Unknown provider "jenkins"' => ['provider' => 'jenkins', '--working-dir' => $directory],
            'Invalid PHP version "8"' => ['provider' => 'github', '--working-dir' => $directory, '--php' => ['8']],
            'Invalid branch name' => ['provider' => 'github', '--working-dir' => $directory, '--branch' => ["main'\n"]],
            'does not exist' => ['provider' => 'github', '--working-dir' => $directory.'/missing'],
        ];

        foreach ($cases as $message => $input) {
            $tester = $this->tester();

            self::assertSame(Command::INVALID, $tester->execute($input, ['interactive' => false]), $message);
            self::assertStringContainsString($message, $tester->getDisplay());
        }

        self::assertFileDoesNotExist($directory.'/.github/workflows/ci.yml');
    }

    public function testReportsAnUnreadableManifest(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(
            ['provider' => 'github', '--working-dir' => $this->createProject('{ broken')],
            ['interactive' => false],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('is not valid JSON', $tester->getDisplay());
    }

    public function testWarnsWhenTheProjectHasNoComposerFile(): void
    {
        $directory = $this->createProject();
        $tester = $this->tester();

        $exitCode = $tester->execute(['provider' => 'github', '--working-dir' => $directory], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No composer.json found', $tester->getDisplay());
        self::assertFileExists($directory.'/.github/workflows/ci.yml');
    }

    public function testReadsDefaultsFromComposerExtra(): void
    {
        $directory = $this->createProject(self::MANIFEST + [
            'extra' => ['axonphp' => [
                'php' => ['8.4'],
                'branches' => ['trunk'],
                'coverage' => true,
                'lowest' => true,
                'audit' => true,
            ]],
        ]);
        $tester = $this->tester();

        $tester->execute(['provider' => 'github', '--working-dir' => $directory], ['interactive' => false]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('8.4 (from extra.axonphp)', $display);
        self::assertStringContainsString('coverage, lowest dependencies, security audit', $display);

        $workflow = self::read($directory.'/.github/workflows/ci.yml');
        self::assertSame(['trunk'], self::yaml($workflow, 'on', 'push', 'branches'));
        self::assertSame(['quality', 'tests', 'coverage'], array_keys((array) self::yaml($workflow, 'jobs')));
        self::assertSame(
            ['php' => ['8.4'], 'dependencies' => ['highest'], 'include' => [['php' => '8.4', 'dependencies' => 'lowest']]],
            self::yaml($workflow, 'jobs', 'tests', 'strategy', 'matrix'),
        );
    }

    public function testCommandLineOptionsWinOverComposerExtra(): void
    {
        $directory = $this->createProject(self::MANIFEST + [
            'extra' => ['axonphp' => ['php' => ['8.4'], 'branches' => ['trunk'], 'coverage' => true]],
        ]);

        $this->tester()->execute(
            [
                'provider' => 'github',
                '--working-dir' => $directory,
                '--php' => ['8.5'],
                '--branch' => ['main'],
                '--no-coverage' => true,
            ],
            ['interactive' => false],
        );

        $workflow = self::read($directory.'/.github/workflows/ci.yml');
        self::assertSame(['main'], self::yaml($workflow, 'on', 'push', 'branches'));
        self::assertSame(['php' => ['8.5']], self::yaml($workflow, 'jobs', 'tests', 'strategy', 'matrix'));
        self::assertSame(['quality', 'tests'], array_keys((array) self::yaml($workflow, 'jobs')));
    }

    public function testWarnsWhenCoverageCannotBeMeasured(): void
    {
        $directory = $this->createProject(['require-dev' => ['phpstan/phpstan' => '^2.0']]);
        $tester = $this->tester();

        $tester->execute(
            ['provider' => 'github', '--working-dir' => $directory, '--coverage' => true],
            ['interactive' => false],
        );

        self::assertStringContainsString(
            'no test runner that can measure it',
            (string) preg_replace('/\s+/', ' ', $tester->getDisplay()),
        );
    }

    public function testRejectsInvalidSettingsInComposerExtra(): void
    {
        $cases = [
            'must be true or false' => ['coverage' => 'yes'],
            'must be a non-empty list of strings' => ['branches' => 'main'],
            'Unknown key "extra.axonphp.colour"' => ['colour' => 'purple'],
        ];

        foreach ($cases as $message => $settings) {
            $tester = $this->tester();
            $directory = $this->createProject(['extra' => ['axonphp' => $settings]]);

            self::assertSame(
                Command::FAILURE,
                $tester->execute(['provider' => 'github', '--working-dir' => $directory], ['interactive' => false]),
            );
            self::assertStringContainsString($message, (string) preg_replace('/\s+/', ' ', $tester->getDisplay()));
        }
    }

    public function testRejectsUnsafeValuesFromComposerExtra(): void
    {
        $tester = $this->tester();
        $directory = $this->createProject(['extra' => ['axonphp' => ['branches' => ["main\n"]]]]);

        self::assertSame(
            Command::INVALID,
            $tester->execute(['provider' => 'github', '--working-dir' => $directory], ['interactive' => false]),
        );
        self::assertStringContainsString('Invalid branch name', $tester->getDisplay());
    }

    private function tester(): CommandTester
    {
        return new CommandTester((new Application())->find('ci:init'));
    }

    private static function read(string $file): string
    {
        $contents = file_get_contents($file);
        self::assertIsString($contents);

        return $contents;
    }
}
