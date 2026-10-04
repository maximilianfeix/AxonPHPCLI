<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Pipeline\Step;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ToolCatalog;

final class GitLabCiProvider implements Provider
{
    public function name(): string
    {
        return 'gitlab';
    }

    public function label(): string
    {
        return 'GitLab CI';
    }

    public function path(): string
    {
        return '.gitlab-ci.yml';
    }

    public function render(Project $project, PipelineOptions $options): string
    {
        $plan = Plan::from($project, $options);

        $sections = [
            Yaml::HEADER,
            $this->stages($plan),
            $this->workflow($plan->branches),
        ];

        if ($plan->usesComposer) {
            $sections[] = $this->composerDefaults($plan);
        }

        if ([] !== $plan->quality) {
            $sections[] = $this->qualityJob($plan);
        }

        $sections[] = $this->testsJob($plan);

        if ($plan->lowest) {
            $sections[] = $this->lowestJob($plan);
        }

        if (null !== $plan->coverage) {
            $sections[] = $this->coverageJob($plan, $plan->coverage);
        }

        return implode("\n\n", $sections)."\n";
    }

    private function stages(Plan $plan): string
    {
        $lines = ['stages:'];

        if ([] !== $plan->quality) {
            $lines[] = '  - quality';
        }

        $lines[] = '  - test';

        return implode("\n", $lines);
    }

    /**
     * Runs merge request pipelines plus branch pipelines for the given branches.
     * A push to a branch that has an open merge request only runs the merge request pipeline.
     *
     * @param list<string> $branches
     */
    private function workflow(array $branches): string
    {
        $lines = [
            'workflow:',
            '  rules:',
            "    - if: \$CI_PIPELINE_SOURCE == 'merge_request_event'",
            '    - if: $CI_COMMIT_BRANCH && $CI_OPEN_MERGE_REQUESTS',
            '      when: never',
            '    - if: $CI_COMMIT_TAG',
        ];

        foreach ($branches as $branch) {
            $lines[] = '    - if: $CI_COMMIT_BRANCH == '.Yaml::quote($branch);
        }

        return implode("\n", $lines);
    }

    private function composerDefaults(Plan $plan): string
    {
        return implode("\n", [
            'variables:',
            '  COMPOSER_ALLOW_SUPERUSER: '.Yaml::quote('1'),
            '  COMPOSER_NO_INTERACTION: '.Yaml::quote('1'),
            '  COMPOSER_CACHE_DIR: $CI_PROJECT_DIR/.composer-cache',
            '',
            'default:',
            '  cache:',
            '    key:',
            '      files:',
            '        - composer.lock',
            '        - composer.json',
            '    paths:',
            '      - .composer-cache/',
            '  before_script:',
            ...$this->items([...Yaml::dockerSetup($plan->extensions), Plan::INSTALL_COMMAND]),
        ]);
    }

    private function qualityJob(Plan $plan): string
    {
        return implode("\n", [
            'quality:',
            '  stage: quality',
            sprintf('  image: php:%s-cli', $plan->latestPhp),
            '  script:',
            ...$this->items(Plan::commands($plan->quality)),
        ]);
    }

    private function testsJob(Plan $plan): string
    {
        $script = Plan::commands($plan->tests);

        if ($plan->usesComposer) {
            array_unshift($script, Plan::VALIDATE_COMMAND);
        }

        return implode("\n", [
            'tests:',
            '  stage: test',
            '  image: php:${PHP_VERSION}-cli',
            '  parallel:',
            '    matrix:',
            '      - PHP_VERSION: '.Yaml::inlineList($plan->phpVersions),
            '  script:',
            ...$this->items($script),
        ]);
    }

    /**
     * The lowest dependencies only need to hold on the oldest PHP version the project supports.
     */
    private function lowestJob(Plan $plan): string
    {
        return implode("\n", [
            'tests:lowest:',
            '  stage: test',
            sprintf('  image: php:%s-cli', $plan->oldestPhp),
            // Replaces the default before_script: installing from the lock file first could fail on this PHP version.
            '  before_script:',
            ...$this->items([...Yaml::dockerSetup($plan->extensions), Plan::LOWEST_COMMAND]),
            '  script:',
            ...$this->items(Plan::commands($plan->tests)),
        ]);
    }

    private function coverageJob(Plan $plan, Step $coverage): string
    {
        return implode("\n", [
            'coverage:',
            '  stage: test',
            sprintf('  image: php:%s-cli', $plan->latestPhp),
            '  script:',
            ...$this->items([Yaml::PCOV_COMMAND, $coverage->command]),
            // Matches the summary line of both PHPUnit ("Lines: 91.30%") and Pest ("Total: 91.3 %").
            "  coverage: '/^\\s*(?:Lines|Total):\\s*\\d+\\.\\d+\\s*%/'",
            '  artifacts:',
            '    paths:',
            '      - '.ToolCatalog::COVERAGE_REPORT,
        ]);
    }

    /**
     * @param list<string> $commands
     *
     * @return list<string>
     */
    private function items(array $commands): array
    {
        return array_map(static fn (string $command): string => '    - '.$command, $commands);
    }
}
