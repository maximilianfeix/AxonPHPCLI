<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

/**
 * A quality tool found in the project and the command that runs it in CI.
 */
final readonly class Tool
{
    public function __construct(
        public string $name,
        public ToolType $type,
        public string $command,
    ) {}
}
