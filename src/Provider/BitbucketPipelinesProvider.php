<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;

final class BitbucketPipelinesProvider implements Provider
{
    public function name(): string
    {
        return 'bitbucket';
    }

    public function label(): string
    {
        return 'Bitbucket Pipelines';
    }

    public function path(): string
    {
        return 'bitbucket-pipelines.yml';
    }

    public function render(Project $project, PipelineOptions $options): string
    {
        $qualityTools = $project->tools(ToolType::StaticAnalysis, ToolType::CodeStyle);
        $audit = $options->audit && $project->usesComposer;
        $coverageTool = $options->coverage ? $project->coverageTool() : null;
        $latest = $project->latestPhpVersion();

        // anchor => step definition, in the order the steps run
        $sequential = [];
        $parallel = [];

        if ([] !== $qualityTools || $audit) {
            $script = array_map(static fn (Tool $tool): string => $tool->command, $qualityTools);

            if ($audit) {
                array_unshift($script, Yaml::AUDIT_COMMAND);
            }

            $sequential['quality'] = $this->step($project, 'quality', 'Code quality', $latest, $script);
        }

        foreach ($project->phpVersions as $version) {
            $anchor = 'tests-php-'.str_replace('.', '-', $version);
            $script = Yaml::testCommands($project);

            if ($project->usesComposer) {
                array_unshift($script, 'composer validate --strict');
            }

            $parallel[$anchor] = $this->step($project, $anchor, sprintf('Tests (PHP %s)', $version), $version, $script);
        }

        if ($options->lowest && $project->usesComposer) {
            $oldest = $project->oldestPhpVersion();
            $parallel['tests-lowest'] = $this->step(
                $project,
                'tests-lowest',
                sprintf('Tests (PHP %s, lowest dependencies)', $oldest),
                $oldest,
                Yaml::testCommands($project),
                // Installing from the lock file first could fail on this PHP version.
                Yaml::LOWEST_COMMAND,
            );
        }

        if (null !== $coverageTool) {
            $parallel['coverage'] = $this->step(
                $project,
                'coverage',
                'Code coverage',
                $latest,
                ['install-php-extensions pcov', (string) $coverageTool->coverageCommand],
            );
        }

        $run = $this->run(array_keys($sequential), array_keys($parallel));

        $pipelines = [
            'pipelines:',
            '  pull-requests:',
            "    '**':",
            ...$run,
            '  branches:',
        ];

        foreach ($options->branches as $branch) {
            $pipelines[] = sprintf('    %s:', Yaml::quote($branch));
            $pipelines = [...$pipelines, ...$run];
        }

        return implode("\n\n", [
            Yaml::HEADER,
            sprintf('image: php:%s-cli', $latest),
            implode("\n", ['definitions:', '  steps:', ...array_values($sequential), ...array_values($parallel)]),
            implode("\n", $pipelines),
        ])."\n";
    }

    /**
     * @param list<string> $script
     * @param string       $install the command that installs the dependencies
     */
    private function step(
        Project $project,
        string $anchor,
        string $name,
        string $phpVersion,
        array $script,
        string $install = Yaml::INSTALL_COMMAND,
    ): string {
        $lines = [
            sprintf('    - step: &%s', $anchor),
            '        name: '.$name,
            sprintf('        image: php:%s-cli', $phpVersion),
        ];

        if ($project->usesComposer) {
            $lines = [...$lines, '        caches:', '          - composer'];
            $script = [
                'export COMPOSER_ALLOW_SUPERUSER=1',
                ...Yaml::dockerSetup($project->extensions),
                $install,
                ...$script,
            ];
        }

        $lines[] = '        script:';

        foreach ($script as $command) {
            $lines[] = '          - '.$command;
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $sequential anchors of the steps that run one after another
     * @param list<string> $parallel   anchors of the steps that run side by side afterwards
     *
     * @return list<string>
     */
    private function run(array $sequential, array $parallel): array
    {
        $lines = array_map(static fn (string $anchor): string => '      - step: *'.$anchor, $sequential);

        if (1 === count($parallel)) {
            $lines[] = '      - step: *'.$parallel[0];

            return $lines;
        }

        $lines[] = '      - parallel:';

        foreach ($parallel as $anchor) {
            $lines[] = '          - step: *'.$anchor;
        }

        return $lines;
    }
}
