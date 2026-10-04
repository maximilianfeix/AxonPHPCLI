<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Project;

use AxonPHP\Cli\Project\PhpVersionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionResolver::class)]
final class PhpVersionResolverTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[DataProvider('constraints')]
    public function testResolvesConstraintToMinorVersions(?string $constraint, array $expected): void
    {
        self::assertSame($expected, (new PhpVersionResolver())->resolve($constraint));
    }

    /**
     * @return iterable<string, array{?string, list<string>}>
     */
    public static function constraints(): iterable
    {
        yield 'caret' => ['^8.2', ['8.2', '8.3', '8.4', '8.5']];

        yield 'caret with patch' => ['^8.3.4', ['8.3', '8.4', '8.5']];

        yield 'tilde on a minor' => ['~8.3.0', ['8.3']];

        yield 'range' => ['>=8.1 <8.4', ['8.1', '8.2', '8.3']];

        yield 'alternatives' => ['^7.4 || ^8.0', PhpVersionResolver::KNOWN];

        yield 'wildcard' => ['8.4.*', ['8.4']];

        yield 'missing' => [null, PhpVersionResolver::DEFAULT];

        yield 'blank' => ['  ', PhpVersionResolver::DEFAULT];

        yield 'unparsable' => ['not a constraint', PhpVersionResolver::DEFAULT];

        yield 'no known version matches' => ['^5.6', PhpVersionResolver::DEFAULT];
    }
}
