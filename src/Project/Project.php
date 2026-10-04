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
     * @param ?string      $name          the Composer package name, if any
     */
    public function __construct(
        public array $phpVersions,
        public array $extensions = [],
        public array $tools = [],
        public bool $usesComposer = true,
        public ?string $phpConstraint = null,
        public ?string $name = null,
        public ProjectSettings $settings = new ProjectSettings(),
    ) {}

    /**
     * @param list<string> $phpVersions
     */
    public function withPhpVersions(array $phpVersions): self
    {
        return new self(
            $phpVersions,
            $this->extensions,
            $this->tools,
            $this->usesComposer,
            $this->phpConstraint,
            $this->name,
            $this->settings,
        );
    }

    public function latestPhpVersion(): string
    {
        return $this->extremePhpVersion('>') ?? PhpVersionResolver::DEFAULT[count(PhpVersionResolver::DEFAULT) - 1];
    }

    public function oldestPhpVersion(): string
    {
        return $this->extremePhpVersion('<') ?? PhpVersionResolver::DEFAULT[0];
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

    /**
     * The test runner that can measure code coverage, if the project has one.
     */
    public function coverageTool(): ?Tool
    {
        foreach ($this->tools(ToolType::Tests) as $tool) {
            if (null !== $tool->coverageCommand) {
                return $tool;
            }
        }

        return null;
    }

    /**
     * @param '<'|'>' $operator
     */
    private function extremePhpVersion(string $operator): ?string
    {
        $extreme = null;

        foreach ($this->phpVersions as $version) {
            if (null === $extreme || version_compare($version, $extreme, $operator)) {
                $extreme = $version;
            }
        }

        return $extreme;
    }
}
