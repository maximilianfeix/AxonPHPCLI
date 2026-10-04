<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Project;

use AxonPHP\Cli\Exception\ProjectException;
use AxonPHP\Cli\Project\PhpVersionResolver;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Tests\TemporaryProject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectInspector::class)]
#[CoversClass(ToolCatalog::class)]
#[CoversClass(Project::class)]
final class ProjectInspectorTest extends TestCase
{
    use TemporaryProject;

    public function testReadsPhpVersionsExtensionsAndTools(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'require' => ['php' => '^8.3', 'ext-pdo' => '*', 'ext-intl' => '*', 'symfony/console' => '^7.3'],
            'require-dev' => [
                'phpunit/phpunit' => '^12.0',
                'phpstan/phpstan' => '^2.0',
                'friendsofphp/php-cs-fixer' => '^3.0',
            ],
        ]));

        self::assertTrue($project->usesComposer);
        self::assertSame('^8.3', $project->phpConstraint);
        self::assertSame(['8.3', '8.4', '8.5'], $project->phpVersions);
        self::assertSame('8.5', $project->latestPhpVersion());
        self::assertSame(['intl', 'pdo'], $project->extensions);
        self::assertSame(['PHPUnit', 'PHPStan', 'PHP-CS-Fixer'], self::names($project->tools));
        self::assertSame(['PHPUnit'], self::names($project->tools(ToolType::Tests)));
        self::assertSame(
            ['PHPStan', 'PHP-CS-Fixer'],
            self::names($project->tools(ToolType::StaticAnalysis, ToolType::CodeStyle)),
        );
    }

    public function testPestTakesPrecedenceOverThePhpUnitItShipsWith(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'require-dev' => ['phpunit/phpunit' => '^12.0', 'pestphp/pest' => '^4.0', 'laravel/pint' => '^1.0'],
        ]));

        self::assertSame(['Pest', 'Pint'], self::names($project->tools));
    }

    public function testCodeceptionTakesPrecedenceOverPhpUnitAndCannotMeasureCoverage(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'name' => 'acme/shop',
            'require-dev' => [
                'phpunit/phpunit' => '^12.0',
                'codeception/codeception' => '^5.0',
                'rector/rector' => '^2.0',
                'qossmic/deptrac-shim' => '^1.0',
                'symplify/easy-coding-standard' => '^12.0',
            ],
        ]));

        self::assertSame('acme/shop', $project->name);
        self::assertSame(['Codeception', 'Rector', 'Deptrac', 'ECS'], self::names($project->tools));
        self::assertNull($project->coverageTool());
    }

    public function testFindsTheToolThatMeasuresCoverage(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'require-dev' => ['phpunit/phpunit' => '^12.0'],
        ]));

        $tool = $project->coverageTool();

        self::assertNotNull($tool);
        self::assertSame('PHPUnit', $tool->name);
        self::assertSame('vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml', $tool->coverageCommand);
    }

    public function testReadsSettingsFromComposerExtra(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'extra' => ['axonphp' => ['php' => ['8.4'], 'branches' => ['main', 'develop'], 'audit' => false]],
        ]));

        self::assertSame(
            ['php' => ['8.4'], 'branches' => ['main', 'develop'], 'audit' => false],
            $project->settings->toArray(),
        );
        self::assertNull($project->settings->coverage);
        self::assertSame('8.2', $project->oldestPhpVersion());
    }

    public function testFallsBackToDefaultsWithoutComposerFile(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject());

        self::assertFalse($project->usesComposer);
        self::assertNull($project->phpConstraint);
        self::assertSame(PhpVersionResolver::DEFAULT, $project->phpVersions);
        self::assertSame([], $project->tools);
    }

    public function testToleratesMalformedSections(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'require' => 'oops',
            'require-dev' => ['phpunit/phpunit' => ['not', 'a', 'string']],
        ]));

        self::assertSame(PhpVersionResolver::DEFAULT, $project->phpVersions);
        self::assertSame([], $project->tools);
    }

    public function testRejectsInvalidJson(): void
    {
        $directory = $this->createProject('{ "require": ');

        $this->expectException(ProjectException::class);
        $this->expectExceptionMessage('is not valid JSON');

        (new ProjectInspector())->inspect($directory);
    }

    public function testRejectsNonObjectManifest(): void
    {
        $directory = $this->createProject('"just a string"');

        $this->expectException(ProjectException::class);
        $this->expectExceptionMessage('must contain a JSON object');

        (new ProjectInspector())->inspect($directory);
    }

    public function testLatestPhpVersionIgnoresTheOrderOfTheList(): void
    {
        self::assertSame('8.10', (new Project(['8.10', '8.4', '8.9']))->latestPhpVersion());
    }

    public function testRequireDevPhpConstraintNarrowsTheMatrix(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject([
            'require' => ['php' => '^8.2'],
            'require-dev' => ['php' => '^8.4'],
        ]));

        self::assertSame('^8.2', $project->phpConstraint);
        self::assertSame(['8.4', '8.5'], $project->phpVersions);
        self::assertTrue($project->phpResolved);
    }

    public function testFlagsConstraintsThatMatchNoKnownVersion(): void
    {
        $project = (new ProjectInspector())->inspect($this->createProject(['require' => ['php' => '^5.6']]));

        self::assertFalse($project->phpResolved);
        self::assertSame(PhpVersionResolver::DEFAULT, $project->phpVersions);
    }

    public function testHonoursTheComposerBinDir(): void
    {
        $inspector = new ProjectInspector();
        $requireDev = ['require-dev' => ['phpunit/phpunit' => '^12.0']];

        $custom = $inspector->inspect($this->createProject($requireDev + ['config' => ['bin-dir' => 'tools/']]));
        self::assertSame('tools/phpunit', $custom->tools[0]->command);
        self::assertSame('tools/phpunit --coverage-text --coverage-clover=coverage.xml', $custom->tools[0]->coverageCommand);

        $vendor = $inspector->inspect($this->createProject($requireDev + ['config' => ['vendor-dir' => 'lib']]));
        self::assertSame('lib/bin/phpunit', $vendor->tools[0]->command);

        $placeholder = $inspector->inspect($this->createProject(
            $requireDev + ['config' => ['vendor-dir' => 'lib', 'bin-dir' => '{$vendor-dir}/exec']],
        ));
        self::assertSame('lib/exec/phpunit', $placeholder->tools[0]->command);
    }

    public function testRejectsBinDirsThatAreNotPlainRelativePaths(): void
    {
        $inspector = new ProjectInspector();

        foreach (['../bin', '/usr/local/bin', 'bin; rm -rf .', 'my bin'] as $binDir) {
            try {
                $inspector->inspect($this->createProject(['config' => ['bin-dir' => $binDir]]));
                self::fail(sprintf('The bin-dir "%s" was accepted.', $binDir));
            } catch (ProjectException $exception) {
                self::assertStringContainsString('Cannot use the Composer bin-dir', $exception->getMessage());
            }
        }
    }

    public function testRejectsAJsonArrayAsManifest(): void
    {
        $directory = $this->createProject('[]');

        $this->expectException(ProjectException::class);
        $this->expectExceptionMessage('must contain a JSON object');

        (new ProjectInspector())->inspect($directory);
    }

    public function testRejectsSettingsListsThatAreObjects(): void
    {
        $directory = $this->createProject(['extra' => ['axonphp' => ['branches' => ['a' => 'main']]]]);

        $this->expectException(ProjectException::class);
        $this->expectExceptionMessage('must be a non-empty list of strings');

        (new ProjectInspector())->inspect($directory);
    }

    /**
     * @param list<Tool> $tools
     *
     * @return list<string>
     */
    private static function names(array $tools): array
    {
        return array_map(static fn (Tool $tool): string => $tool->name, $tools);
    }
}
