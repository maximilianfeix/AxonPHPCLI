<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;

/**
 * A CI service AxonPHP can generate a pipeline for.
 */
interface Provider
{
    /**
     * The identifier used on the command line, e.g. "github".
     */
    public function name(): string;

    /**
     * The human-readable name, e.g. "GitHub Actions".
     */
    public function label(): string;

    /**
     * Where the pipeline file lives, relative to the project root, using forward slashes.
     */
    public function path(): string;

    public function render(Project $project, PipelineOptions $options): string;
}
