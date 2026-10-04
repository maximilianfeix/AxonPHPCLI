<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

/**
 * Pipeline defaults a project stores under "extra.axonphp" in composer.json.
 *
 * A null value means the project does not set it and the built-in default applies.
 */
final readonly class ProjectSettings
{
    public const KEYS = ['php', 'branches', 'coverage', 'lowest', 'audit'];

    /**
     * @param null|list<string> $php
     * @param null|list<string> $branches
     */
    public function __construct(
        public ?array $php = null,
        public ?array $branches = null,
        public ?bool $coverage = null,
        public ?bool $lowest = null,
        public ?bool $audit = null,
    ) {}

    /**
     * @return array<string, bool|list<string>> only the values the project sets
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'php' => $this->php,
                'branches' => $this->branches,
                'coverage' => $this->coverage,
                'lowest' => $this->lowest,
                'audit' => $this->audit,
            ],
            static fn (array|bool|null $value): bool => null !== $value,
        );
    }
}
