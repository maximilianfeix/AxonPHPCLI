<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Pipeline\Step;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ToolCatalog;

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
        $plan = Plan::from($project, $options);
        $jobs = [];

        if ([] !== $plan->quality) {
            $jobs[] = $this->qualityJob($plan);
        }

        $jobs[] = $this->testsJob($plan);

        if (null !== $plan->coverage) {
            $jobs[] = $this->coverageJob($plan, $plan->coverage);
        }

        return $this->preamble($plan->branches)."\n".implode("\n\n", $jobs)."\n";
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

    private function qualityJob(Plan $plan): string
    {
        return implode("\n", [
            '  quality:',
            '    name: Code quality',
            '    runs-on: ubuntu-latest',
            '',
            '    steps:',
            implode("\n\n", [
                ...$this->setupSteps($plan, Yaml::quote($plan->latestPhp)),
                ...$this->runSteps($plan->quality),
            ]),
        ]);
    }

    private function testsJob(Plan $plan): string
    {
        $steps = [
            ...$this->setupSteps(
                $plan,
                '${{ matrix.php }}',
                validate: true,
                dependencies: $plan->lowest ? '${{ matrix.dependencies }}' : null,
            ),
            ...$this->runSteps($plan->tests),
        ];

        $matrix = ['        php: '.Yaml::inlineList($plan->phpVersions)];
        $name = 'Tests (PHP ${{ matrix.php }})';

        if ($plan->lowest) {
            // The lowest dependencies only need to hold on the oldest PHP version the project supports.
            $name = 'Tests (PHP ${{ matrix.php }}, ${{ matrix.dependencies }} dependencies)';
            $matrix = [
                ...$matrix,
                '        dependencies: '.Yaml::inlineList(['highest']),
                '        include:',
                '          - php: '.Yaml::quote($plan->oldestPhp),
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

    private function coverageJob(Plan $plan, Step $coverage): string
    {
        $steps = [
            ...$this->setupSteps($plan, Yaml::quote($plan->latestPhp), coverage: 'pcov'),
            ...$this->runSteps([$coverage]),
            $this->step('Upload coverage report', uses: self::UPLOAD_ARTIFACT, with: [
                'name' => 'coverage',
                'path' => ToolCatalog::COVERAGE_REPORT,
            ]),
        ];

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
        Plan $plan,
        string $phpVersion,
        bool $validate = false,
        string $coverage = 'none',
        ?string $dependencies = null,
    ): array {
        $php = ['php-version' => $phpVersion];

        if ([] !== $plan->extensions) {
            $php['extensions'] = implode(', ', $plan->extensions);
        }

        $php['coverage'] = $coverage;

        $steps = [
            $this->step('Checkout', uses: self::CHECKOUT),
            $this->step('Set up PHP', uses: self::SETUP_PHP, with: $php),
        ];

        if (!$plan->usesComposer) {
            return $steps;
        }

        if ($validate) {
            $steps[] = $this->step('Validate composer.json', run: Plan::VALIDATE_COMMAND);
        }

        $steps[] = $this->step(
            'Install dependencies',
            uses: self::COMPOSER_INSTALL,
            with: null === $dependencies ? [] : ['dependency-versions' => $dependencies],
        );

        return $steps;
    }

    /**
     * @param list<Step> $steps
     *
     * @return list<string>
     */
    private function runSteps(array $steps): array
    {
        return array_map(fn (Step $step): string => $this->step($step->name, run: $step->command), $steps);
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
