<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;

final class GitHubActionsProvider implements Provider
{
    private const CHECKOUT = 'actions/checkout@v7';
    private const SETUP_PHP = 'shivammathur/setup-php@v2';
    private const COMPOSER_INSTALL = 'ramsey/composer-install@v4';

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

    public function render(Project $project, array $branches): string
    {
        $qualityTools = $project->tools(ToolType::CodeStyle, ToolType::StaticAnalysis);

        $jobs = [];

        if ([] !== $qualityTools) {
            $jobs[] = $this->qualityJob($project, $qualityTools);
        }

        $jobs[] = $this->testsJob($project);

        return $this->preamble($branches)."\n".implode("\n\n", $jobs)."\n";
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
     * @param non-empty-list<Tool> $tools
     */
    private function qualityJob(Project $project, array $tools): string
    {
        $steps = $this->setupSteps($project, Yaml::quote($project->latestPhpVersion()));

        foreach ($tools as $tool) {
            $steps[] = $this->step(sprintf('%s (%s)', $tool->type->label(), $tool->name), run: $tool->command);
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

    private function testsJob(Project $project): string
    {
        $steps = $this->setupSteps($project, '${{ matrix.php }}', validate: true);
        $tools = $project->tools(ToolType::Tests);

        foreach ($tools as $tool) {
            $steps[] = $this->step(sprintf('%s (%s)', $tool->type->label(), $tool->name), run: $tool->command);
        }

        if ([] === $tools) {
            $steps[] = $this->step('Lint PHP files', run: Yaml::LINT_COMMAND);
        }

        return implode("\n", [
            '  tests:',
            '    name: Tests (PHP ${{ matrix.php }})',
            '    runs-on: ubuntu-latest',
            '',
            '    strategy:',
            '      fail-fast: false',
            '      matrix:',
            '        php: '.Yaml::inlineList($project->phpVersions),
            '',
            '    steps:',
            implode("\n\n", $steps),
        ]);
    }

    /**
     * @param string $phpVersion a ready-to-emit YAML value: a quoted version or an expression
     *
     * @return list<string>
     */
    private function setupSteps(Project $project, string $phpVersion, bool $validate = false): array
    {
        $php = ['php-version' => $phpVersion];

        if ([] !== $project->extensions) {
            $php['extensions'] = implode(', ', $project->extensions);
        }

        $php['coverage'] = 'none';

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

        $steps[] = $this->step('Install dependencies', uses: self::COMPOSER_INSTALL);

        return $steps;
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
