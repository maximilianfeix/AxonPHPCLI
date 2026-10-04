<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Exception\InvalidInputException;

final class ProviderRegistry
{
    /** @var array<string, Provider> */
    private array $providers = [];

    public function __construct(Provider ...$providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->name()] = $provider;
        }
    }

    public static function default(): self
    {
        return new self(new GitHubActionsProvider(), new GitLabCiProvider(), new BitbucketPipelinesProvider());
    }

    /**
     * @return list<Provider>
     */
    public function all(): array
    {
        return array_values($this->providers);
    }

    public function get(string $name): Provider
    {
        return $this->providers[strtolower($name)] ?? throw new InvalidInputException(sprintf(
            'Unknown provider "%s". Supported providers: %s.',
            $name,
            implode(', ', $this->names()),
        ));
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->providers);
    }

    /**
     * @return array<string, string> provider name => label
     */
    public function labels(): array
    {
        return array_map(static fn (Provider $provider): string => $provider->label(), $this->providers);
    }
}
