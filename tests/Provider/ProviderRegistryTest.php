<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Exception\InvalidInputException;
use AxonPHP\Cli\Provider\GitHubActionsProvider;
use AxonPHP\Cli\Provider\GitLabCiProvider;
use AxonPHP\Cli\Provider\ProviderRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProviderRegistry::class)]
final class ProviderRegistryTest extends TestCase
{
    public function testListsTheDefaultProviders(): void
    {
        $registry = ProviderRegistry::default();

        self::assertSame(['github', 'gitlab', 'bitbucket'], $registry->names());
        self::assertSame(
            ['github' => 'GitHub Actions', 'gitlab' => 'GitLab CI', 'bitbucket' => 'Bitbucket Pipelines'],
            $registry->labels(),
        );
        self::assertCount(3, $registry->all());
    }

    public function testFindsProvidersCaseInsensitively(): void
    {
        $registry = ProviderRegistry::default();

        self::assertInstanceOf(GitHubActionsProvider::class, $registry->get('GitHub'));
        self::assertInstanceOf(GitLabCiProvider::class, $registry->get('gitlab'));
    }

    public function testRejectsUnknownProviders(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Unknown provider "jenkins". Supported providers: github, gitlab, bitbucket.');

        ProviderRegistry::default()->get('jenkins');
    }
}
