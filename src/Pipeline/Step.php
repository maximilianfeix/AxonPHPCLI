<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Pipeline;

/**
 * One command a job runs, with the name it is shown under.
 */
final readonly class Step
{
    public function __construct(
        public string $name,
        public string $command,
    ) {}
}
