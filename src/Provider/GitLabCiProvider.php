<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Project\ToolType;

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
        $qualityTools = $project->tools(ToolType::StaticAnalysis, ToolType::CodeStyle);
        $audit = $options->audit && $project->usesComposer;
        $withQuality = [] !== $qualityTools || $audit;
        $coverageTool = $options->coverage ? $project->coverageTool() : null;

        $sections = [
            Yaml::HEADER,
            $this->stages($withQuality),
            $this->workflow($options->branches),
        ];

        if ($project->usesComposer) {
            $sections[] = $this->composerDefaults($project);
        }

        if ($withQuality) {
            $sections[] = $this->qualityJob($project, $qualityTools, $audit);
        }

        $sections[] = $this->testsJob($project);

        if ($options->lowest && $project->usesComposer) {
            $sections[] = $this->lowestJob($project);
        }

        if (null !== $coverageTool) {
            $sections[] = $this->coverageJob($project, $coverageTool);
        }

        return implode("\n\n", $sections)."\n";
    }

    private function stages(bool $withQuality): string
    {
        $lines = ['stages:'];

        if ($withQuality) {
            $lines[] = '  - quality';
        }

        $lines[] = '  - test';

        return implode("\n", $lines);
    }

    /**
     * Runs merge request pipelines plus branch pipelines for the given branches,
     * so a push to a branch with an open merge request does not run twice.
     *
     * @param list<string> $branches
     */
    private function workflow(array $branches): string
    {
        $lines = [
            'workflow:',
            '  rules:',
            "    - if: \$CI_PIPELINE_SOURCE == 'merge_request_event'",
            '    - if: $CI_COMMIT_TAG',
        ];

        foreach ($branches as $branch) {
            $lines[] = '    - if: $CI_COMMIT_BRANCH == '.Yaml::quote($branch);
        }

        return implode("\n", $lines);
    }

    private function composerDefaults(Project $project): string
    {
        $lines = [
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
        ];

        foreach ([...Yaml::dockerSetup($project->extensions), Yaml::INSTALL_COMMAND] as $command) {
            $lines[] = '    - '.$command;
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<Tool> $tools
     */
    private function qualityJob(Project $project, array $tools, bool $audit): string
    {
        $script = array_map(static fn (Tool $tool): string => $tool->command, $tools);

        if ($audit) {
            array_unshift($script, Yaml::AUDIT_COMMAND);
        }

        return implode("\n", [
            'quality:',
            '  stage: quality',
            sprintf('  image: php:%s-cli', $project->latestPhpVersion()),
            '  script:',
            ...$this->items($script),
        ]);
    }

    private function testsJob(Project $project): string
    {
        $script = Yaml::testCommands($project);

        if ($project->usesComposer) {
            array_unshift($script, 'composer validate --strict');
        }

        return implode("\n", [
            'tests:',
            '  stage: test',
            '  image: php:${PHP_VERSION}-cli',
            '  parallel:',
            '    matrix:',
            '      - PHP_VERSION: '.Yaml::inlineList($project->phpVersions),
            '  script:',
            ...$this->items($script),
        ]);
    }

    /**
     * The lowest dependencies only need to hold on the oldest PHP version the project supports.
     */
    private function lowestJob(Project $project): string
    {
        return implode("\n", [
            'tests:lowest:',
            '  stage: test',
            sprintf('  image: php:%s-cli', $project->oldestPhpVersion()),
            '  script:',
            ...$this->items([Yaml::LOWEST_COMMAND, ...Yaml::testCommands($project)]),
        ]);
    }

    private function coverageJob(Project $project, Tool $tool): string
    {
        return implode("\n", [
            'coverage:',
            '  stage: test',
            sprintf('  image: php:%s-cli', $project->latestPhpVersion()),
            '  script:',
            ...$this->items(['install-php-extensions pcov', (string) $tool->coverageCommand]),
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
