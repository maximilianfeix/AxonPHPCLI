<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Provider\PipelineOptions;

/**
 * The resolved input of a pipeline command: where the project is, what it contains and how to build its pipeline.
 */
final readonly class PipelineContext
{
    /**
     * @param string $phpSource where the PHP versions come from, for display
     */
    public function __construct(
        public string $directory,
        public Project $project,
        public PipelineOptions $options,
        public string $phpSource,
    ) {}
}
