<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

/**
 * Everything a provider needs to know about the project it generates a pipeline for.
 */
final readonly class Project
{
    /**
     * @param list<string> $phpVersions   minor versions to test against, oldest first
     * @param list<string> $extensions    PHP extensions the project requires, without the "ext-" prefix
     * @param list<Tool>   $tools
     * @param ?string      $phpConstraint the raw "php" constraint from composer.json, if any
     */
    public function __construct(
        public array $phpVersions,
        public array $extensions = [],
        public array $tools = [],
        public bool $usesComposer = true,
        public ?string $phpConstraint = null,
    ) {}

    /**
     * @param list<string> $phpVersions
     */
    public function withPhpVersions(array $phpVersions): self
    {
        return new self($phpVersions, $this->extensions, $this->tools, $this->usesComposer, $this->phpConstraint);
    }

    public function latestPhpVersion(): string
    {
        $latest = null;

        foreach ($this->phpVersions as $version) {
            if (null === $latest || version_compare($version, $latest, '>')) {
                $latest = $version;
            }
        }

        return $latest ?? PhpVersionResolver::DEFAULT[count(PhpVersionResolver::DEFAULT) - 1];
    }

    /**
     * @return list<Tool>
     */
    public function tools(ToolType ...$types): array
    {
        return array_values(array_filter(
            $this->tools,
            static fn (Tool $tool): bool => in_array($tool->type, $types, true),
        ));
    }
}
