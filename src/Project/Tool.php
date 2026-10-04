<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

/**
 * A quality tool found in the project and the command that runs it in CI.
 */
final readonly class Tool
{
    /**
     * @param ?string $coverageCommand the command that also writes coverage.xml, for tools that can measure coverage
     * @param ?string $minimumOption   the option of the coverage command that fails the run below a percentage, if the tool has one
     */
    public function __construct(
        public string $name,
        public ToolType $type,
        public string $command,
        public ?string $coverageCommand = null,
        public ?string $minimumOption = null,
    ) {}
}
