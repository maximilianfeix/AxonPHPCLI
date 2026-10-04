<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Project\ToolType;

final class GitHubActionsProvider implements Provider
{
    private const CHECKOUT = 'actions/checkout@v7';
    private const SETUP_PHP = 'shivammathur/setup-php@v2';
    private const COMPOSER_INSTALL = 'ramsey/composer-install@v4';
    private const UPLOAD_ARTIFACT = 'actions/upload-artifact@v7';

    public function name(): string
    {
        return 'github';
    }

    public function label(): string
    {
        return 'GitHub Actions';
    }

    public function path(): string
    {
        return '.github/workflows/ci.yml';
    }

    public function render(Project $project, PipelineOptions $options): string
    {
        $qualityTools = $project->tools(ToolType::StaticAnalysis, ToolType::CodeStyle);
        $audit = $options->audit && $project->usesComposer;
        $coverageTool = $options->coverage ? $project->coverageTool() : null;

        $jobs = [];

        if ([] !== $qualityTools || $audit) {
            $jobs[] = $this->qualityJob($project, $qualityTools, $audit);
        }

        $jobs[] = $this->testsJob($project, $options->lowest && $project->usesComposer);

        if (null !== $coverageTool) {
            $jobs[] = $this->coverageJob($project, $coverageTool);
        }

        return $this->preamble($options->branches)."\n".implode("\n\n", $jobs)."\n";
    }

    /**
     * @param list<string> $branches
     */
    private function preamble(array $branches): string
    {
        return implode("\n", [
            Yaml::HEADER,
            '',
            'name: CI',
            '',
            'on:',
            '  push:',
            '    branches: '.Yaml::inlineList($branches),
            '  pull_request:',
            '',
            'permissions:',
            '  contents: read',
            '',
            'concurrency:',
            '  group: ${{ github.workflow }}-${{ github.ref }}',
            '  cancel-in-progress: true',
            '',
            'jobs:',
        ]);
    }

    /**
     * @param list<Tool> $tools
     */
    private function qualityJob(Project $project, array $tools, bool $audit): string
    {
        $steps = $this->setupSteps($project, Yaml::quote($project->latestPhpVersion()));

        if ($audit) {
            $steps[] = $this->step('Security audit (Composer)', run: Yaml::AUDIT_COMMAND);
        }

        foreach ($tools as $tool) {
            $steps[] = $this->toolStep($tool);
        }

        return implode("\n", [
            '  quality:',
            '    name: Code quality',
            '    runs-on: ubuntu-latest',
            '',
            '    steps:',
            implode("\n\n", $steps),
        ]);
    }

    private function testsJob(Project $project, bool $lowest): string
    {
        $steps = $this->setupSteps(
            $project,
            '${{ matrix.php }}',
            validate: true,
            dependencies: $lowest ? '${{ matrix.dependencies }}' : null,
        );
        $tools = $project->tools(ToolType::Tests);

        foreach ($tools as $tool) {
            $steps[] = $this->toolStep($tool);
        }

        if ([] === $tools) {
            $steps[] = $this->step('Lint PHP files', run: Yaml::LINT_COMMAND);
        }

        $matrix = ['        php: '.Yaml::inlineList($project->phpVersions)];
        $name = 'Tests (PHP ${{ matrix.php }})';

        if ($lowest) {
            // The lowest dependencies only need to hold on the oldest PHP version the project supports.
            $name = 'Tests (PHP ${{ matrix.php }}, ${{ matrix.dependencies }} dependencies)';
            $matrix = [
                ...$matrix,
                '        dependencies: '.Yaml::inlineList(['highest']),
                '        include:',
                '          - php: '.Yaml::quote($project->oldestPhpVersion()),
                '            dependencies: '.Yaml::quote('lowest'),
            ];
        }

        return implode("\n", [
            '  tests:',
            '    name: '.$name,
            '    runs-on: ubuntu-latest',
            '',
            '    strategy:',
            '      fail-fast: false',
            '      matrix:',
            ...$matrix,
            '',
            '    steps:',
            implode("\n\n", $steps),
        ]);
    }

    private function coverageJob(Project $project, Tool $tool): string
    {
        $steps = $this->setupSteps($project, Yaml::quote($project->latestPhpVersion()), coverage: 'pcov');
        $steps[] = $this->step(sprintf('Code coverage (%s)', $tool->name), run: $tool->coverageCommand);
        $steps[] = $this->step('Upload coverage report', uses: self::UPLOAD_ARTIFACT, with: [
            'name' => 'coverage',
            'path' => ToolCatalog::COVERAGE_REPORT,
        ]);

        return implode("\n", [
            '  coverage:',
            '    name: Code coverage',
            '    runs-on: ubuntu-latest',
            '',
            '    steps:',
            implode("\n\n", $steps),
        ]);
    }

    /**
     * @param string  $phpVersion   a ready-to-emit YAML value: a quoted version or an expression
     * @param ?string $dependencies a ready-to-emit YAML value for the dependency versions to install
     *
     * @return list<string>
     */
    private function setupSteps(
        Project $project,
        string $phpVersion,
        bool $validate = false,
        string $coverage = 'none',
        ?string $dependencies = null,
    ): array {
        $php = ['php-version' => $phpVersion];

        if ([] !== $project->extensions) {
            $php['extensions'] = implode(', ', $project->extensions);
        }

        $php['coverage'] = $coverage;

        $steps = [
            $this->step('Checkout', uses: self::CHECKOUT),
            $this->step('Set up PHP', uses: self::SETUP_PHP, with: $php),
        ];

        if (!$project->usesComposer) {
            return $steps;
        }

        if ($validate) {
            $steps[] = $this->step('Validate composer.json', run: 'composer validate --strict');
        }

        $steps[] = $this->step(
            'Install dependencies',
            uses: self::COMPOSER_INSTALL,
            with: null === $dependencies ? [] : ['dependency-versions' => $dependencies],
        );

        return $steps;
    }

    private function toolStep(Tool $tool): string
    {
        return $this->step(sprintf('%s (%s)', $tool->type->label(), $tool->name), run: $tool->command);
    }

    /**
     * @param array<string, string> $with
     */
    private function step(string $name, ?string $uses = null, array $with = [], ?string $run = null): string
    {
        $lines = ['      - name: '.$name];

        if (null !== $uses) {
            $lines[] = '        uses: '.$uses;
        }

        if ([] !== $with) {
            $lines[] = '        with:';

            foreach ($with as $key => $value) {
                $lines[] = sprintf('          %s: %s', $key, $value);
            }
        }

        if (null !== $run) {
            $lines[] = '        run: '.$run;
        }

        return implode("\n", $lines);
    }
}
