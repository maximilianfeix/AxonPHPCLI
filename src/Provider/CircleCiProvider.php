<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Pipeline\Step;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ToolCatalog;

/**
 * CircleCI builds every pushed branch, so the trigger branches of the plan are not used here.
 */
final class CircleCiProvider implements Provider
{
    private const CACHE_DIR = '/tmp/composer-cache';
    private const CACHE_KEY = 'composer-{{ checksum "composer.json" }}';

    public function name(): string
    {
        return 'circleci';
    }

    public function label(): string
    {
        return 'CircleCI';
    }

    public function path(): string
    {
        return '.circleci/config.yml';
    }

    public function render(Project $project, PipelineOptions $options): string
    {
        $plan = Plan::from($project, $options);

        $jobs = [];
        $workflow = [];

        if ([] !== $plan->quality) {
            $jobs[] = $this->job($plan, 'quality', $plan->latestPhp, $plan->quality);
            $workflow[] = '      - quality';
        }

        $tests = $plan->tests;

        if ($plan->usesComposer) {
            array_unshift($tests, new Step('Validate composer.json', Plan::VALIDATE_COMMAND));
        }

        $jobs[] = $this->job($plan, 'tests', '<< parameters.php >>', $tests, parameter: true);
        $workflow = [
            ...$workflow,
            '      - tests:',
            '          name: tests-php-<< matrix.php >>',
            '          matrix:',
            '            parameters:',
            '              php: '.Yaml::inlineList($plan->phpVersions),
        ];

        if ($plan->lowest) {
            // Installing from the lock file first could fail on this PHP version.
            $jobs[] = $this->job($plan, 'tests-lowest', $plan->oldestPhp, $plan->tests, install: Plan::LOWEST_COMMAND);
            $workflow[] = '      - tests-lowest';
        }

        if (null !== $plan->coverage) {
            $jobs[] = implode("\n", [
                $this->job($plan, 'coverage', $plan->latestPhp, [new Step('Install PCOV', Yaml::PCOV_COMMAND), $plan->coverage]),
                '      - store_artifacts:',
                '          path: '.ToolCatalog::COVERAGE_REPORT,
                // After the report is stored, so it is available when the threshold fails the job.
                ...$this->runSteps(null === $plan->coverageThreshold ? [] : [$plan->coverageThreshold]),
            ]);
            $workflow[] = '      - coverage';
        }

        return implode("\n\n", [
            Yaml::HEADER,
            'version: 2.1',
            "jobs:\n".implode("\n\n", $jobs),
            implode("\n", ['workflows:', '  ci:', '    jobs:', ...$workflow]),
        ])."\n";
    }

    /**
     * @param string     $phpVersion a version or a pipeline parameter expression
     * @param list<Step> $steps
     * @param bool       $parameter  whether the job takes the PHP version as its "php" parameter
     * @param string     $install    the command that installs the dependencies
     */
    private function job(
        Plan $plan,
        string $name,
        string $phpVersion,
        array $steps,
        bool $parameter = false,
        string $install = Plan::INSTALL_COMMAND,
    ): string {
        $lines = [sprintf('  %s:', $name)];

        if ($parameter) {
            $lines = [...$lines, '    parameters:', '      php:', '        type: string'];
        }

        $lines = [...$lines, '    docker:', sprintf('      - image: php:%s-cli', $phpVersion)];

        if ($plan->usesComposer) {
            $lines = [
                ...$lines,
                '    environment:',
                '      COMPOSER_ALLOW_SUPERUSER: '.Yaml::quote('1'),
                '      COMPOSER_NO_INTERACTION: '.Yaml::quote('1'),
                '      COMPOSER_CACHE_DIR: '.self::CACHE_DIR,
            ];
        }

        $lines = [...$lines, '    steps:', '      - checkout'];

        if ($plan->usesComposer) {
            $lines = [
                ...$lines,
                '      - run:',
                '          name: Set up PHP',
                '          command: |',
                ...array_map(static fn (string $command): string => '            '.$command, Yaml::dockerSetup($plan->extensions)),
                '      - restore_cache:',
                '          keys:',
                '            - '.self::CACHE_KEY,
                '            - composer-',
                ...$this->runSteps([new Step('Install dependencies', $install)]),
                '      - save_cache:',
                '          key: '.self::CACHE_KEY,
                '          paths:',
                '            - '.self::CACHE_DIR,
            ];
        }

        return implode("\n", [...$lines, ...$this->runSteps($steps)]);
    }

    /**
     * @param list<Step> $steps
     *
     * @return list<string>
     */
    private function runSteps(array $steps): array
    {
        $lines = [];

        foreach ($steps as $step) {
            $lines = [...$lines, '      - run:', '          name: '.$step->name, '          command: '.$step->command];
        }

        return $lines;
    }
}
