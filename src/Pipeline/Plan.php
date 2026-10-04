<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Pipeline;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\PipelineOptions;

/**
 * What a pipeline has to do, independent of the CI service that runs it.
 *
 * The plan decides which jobs exist and what they run; a provider only translates it to its own YAML dialect.
 */
final readonly class Plan
{
    public const LINT_COMMAND = "find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l";

    public const VALIDATE_COMMAND = 'composer validate --strict';

    public const INSTALL_COMMAND = 'composer install --prefer-dist --no-progress';

    public const LOWEST_COMMAND = 'composer update --prefer-lowest --prefer-stable --prefer-dist --no-progress';

    public const AUDIT_COMMAND = 'composer audit';

    /**
     * @param list<string> $branches    branches whose pushes trigger the pipeline
     * @param list<string> $extensions  PHP extensions to install, without the "ext-" prefix
     * @param list<string> $phpVersions minor versions the tests run on, oldest first
     * @param list<Step>   $quality     checks that run once, on the newest PHP version; no quality job when empty
     * @param list<Step>   $tests       what runs on every PHP version
     * @param bool         $lowest      also run the tests with the lowest allowed dependencies on the oldest PHP version
     * @param ?Step        $coverage    runs the tests with a coverage driver and writes the Clover report
     */
    public function __construct(
        public array $branches,
        public bool $usesComposer,
        public array $extensions,
        public array $phpVersions,
        public string $latestPhp,
        public string $oldestPhp,
        public array $quality,
        public array $tests,
        public bool $lowest = false,
        public ?Step $coverage = null,
    ) {}

    public static function from(Project $project, PipelineOptions $options): self
    {
        $quality = [];

        if ($options->audit && $project->usesComposer) {
            $quality[] = new Step('Security audit (Composer)', self::AUDIT_COMMAND);
        }

        foreach ($project->tools(ToolType::StaticAnalysis, ToolType::CodeStyle) as $tool) {
            $quality[] = self::toolStep($tool);
        }

        $tests = array_map(self::toolStep(...), $project->tools(ToolType::Tests));

        if ([] === $tests) {
            $tests[] = new Step('Lint PHP files', self::LINT_COMMAND);
        }

        $coverageTool = $options->coverage ? $project->coverageTool() : null;

        return new self(
            $options->branches,
            $project->usesComposer,
            $project->extensions,
            $project->phpVersions,
            $project->latestPhpVersion(),
            $project->oldestPhpVersion(),
            $quality,
            $tests,
            $options->lowest && $project->usesComposer,
            null === $coverageTool ? null : new Step(
                sprintf('Code coverage (%s)', $coverageTool->name),
                (string) $coverageTool->coverageCommand,
            ),
        );
    }

    /**
     * @param list<Step> $steps
     *
     * @return list<string>
     */
    public static function commands(array $steps): array
    {
        return array_map(static fn (Step $step): string => $step->command, $steps);
    }

    private static function toolStep(Tool $tool): Step
    {
        return new Step(sprintf('%s (%s)', $tool->type->label(), $tool->name), $tool->command);
    }
}
