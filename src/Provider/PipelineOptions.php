<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

/**
 * Choices about the pipeline that cannot be read off the project's dependencies.
 */
final readonly class PipelineOptions
{
    /**
     * @param list<string> $branches branches whose pushes trigger the pipeline
     * @param bool         $coverage measure code coverage on the newest PHP version
     * @param bool         $lowest   also test the lowest allowed dependencies on the oldest PHP version
     * @param bool         $audit    fail on dependencies with known security advisories
     */
    public function __construct(
        public array $branches = ['main'],
        public bool $coverage = false,
        public bool $lowest = false,
        public bool $audit = false,
    ) {}
}
