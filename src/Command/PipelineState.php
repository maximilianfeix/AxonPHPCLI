<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Command;

use AxonPHP\Cli\Provider\Provider;

/**
 * How the pipeline file in the project compares with the one AxonPHP would generate now.
 */
final readonly class PipelineState
{
    public const CURRENT = 'up-to-date';
    public const OUTDATED = 'outdated';
    public const MISSING = 'missing';

    /**
     * @param self::*                     $status
     * @param string                      $expected the pipeline as it should be
     * @param list<array{string, string}> $diff     the result of LineDiff::compare(); empty unless outdated
     */
    public function __construct(
        public Provider $provider,
        public string $status,
        public string $expected,
        public array $diff = [],
    ) {}

    public function isCurrent(): bool
    {
        return self::CURRENT === $this->status;
    }
}
