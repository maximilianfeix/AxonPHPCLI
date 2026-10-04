<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Project\Project;

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
        $plan = Plan::from($project, $options);

        // anchor => step definition, in the order the steps run
        $sequential = [];
        $parallel = [];

        if ([] !== $plan->quality) {
            $sequential['quality'] = $this->step($plan, 'quality', 'Code quality', $plan->latestPhp, Plan::commands($plan->quality));
        }

        foreach ($plan->phpVersions as $version) {
            $anchor = 'tests-php-'.str_replace('.', '-', $version);
            $script = Plan::commands($plan->tests);

            if ($plan->usesComposer) {
                array_unshift($script, Plan::VALIDATE_COMMAND);
            }

            $parallel[$anchor] = $this->step($plan, $anchor, sprintf('Tests (PHP %s)', $version), $version, $script);
        }

        if ($plan->lowest) {
            $parallel['tests-lowest'] = $this->step(
                $plan,
                'tests-lowest',
                sprintf('Tests (PHP %s, lowest dependencies)', $plan->oldestPhp),
                $plan->oldestPhp,
                Plan::commands($plan->tests),
                // Installing from the lock file first could fail on this PHP version.
                Plan::LOWEST_COMMAND,
            );
        }

        if (null !== $plan->coverage) {
            $parallel['coverage'] = $this->step(
                $plan,
                'coverage',
                'Code coverage',
                $plan->latestPhp,
                [Yaml::PCOV_COMMAND, $plan->coverage->command],
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

        foreach ($plan->branches as $branch) {
            $pipelines[] = sprintf('    %s:', Yaml::quote($branch));
            $pipelines = [...$pipelines, ...$run];
        }

        return implode("\n\n", [
            Yaml::HEADER,
            sprintf('image: php:%s-cli', $plan->latestPhp),
            implode("\n", ['definitions:', '  steps:', ...array_values($sequential), ...array_values($parallel)]),
            implode("\n", $pipelines),
        ])."\n";
    }

    /**
     * @param list<string> $script
     * @param string       $install the command that installs the dependencies
     */
    private function step(
        Plan $plan,
        string $anchor,
        string $name,
        string $phpVersion,
        array $script,
        string $install = Plan::INSTALL_COMMAND,
    ): string {
        $lines = [
            sprintf('    - step: &%s', $anchor),
            '        name: '.$name,
            sprintf('        image: php:%s-cli', $phpVersion),
        ];

        if ($plan->usesComposer) {
            $lines = [...$lines, '        caches:', '          - composer'];
            $script = [
                'export COMPOSER_ALLOW_SUPERUSER=1',
                ...Yaml::dockerSetup($plan->extensions),
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
