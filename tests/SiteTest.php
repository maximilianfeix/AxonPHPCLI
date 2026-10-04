<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SiteTest extends TestCase
{
    /**
     * The website shows real pipeline output, so it has to be rebuilt whenever that output changes.
     */
    public function testTheCommittedWebsiteMatchesTheCode(): void
    {
        $script = dirname(__DIR__).'/site/build.php';

        if (!is_file($script)) {
            self::markTestSkipped('The site sources are not part of this checkout.');
        }

        exec(sprintf('%s %s --check 2>&1', escapeshellarg(\PHP_BINARY), escapeshellarg($script)), $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
    }
}
