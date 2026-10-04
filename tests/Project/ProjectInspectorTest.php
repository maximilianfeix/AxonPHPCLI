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
