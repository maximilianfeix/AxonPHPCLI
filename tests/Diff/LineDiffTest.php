<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Diff;

use AxonPHP\Cli\Diff\LineDiff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LineDiff::class)]
final class LineDiffTest extends TestCase
{
    public function testEqualTextsHaveNoDifference(): void
    {
        self::assertSame([], LineDiff::compare("a\nb\n", "a\nb\n"));
        self::assertSame([], LineDiff::compare('', ''));
    }

    public function testIgnoresLineEndingStyleAndTrailingNewlines(): void
    {
        self::assertSame([], LineDiff::compare("a\r\nb\r\n", "a\nb"));
    }

    public function testReportsChangedLinesWithContext(): void
    {
        $old = "one\ntwo\nthree\nfour\nfive\nsix\nseven\n";
        $new = "one\ntwo\nthree\nFOUR\nfive\nsix\nseven\n";

        self::assertSame(
            [
                [LineDiff::KEPT, 'three'],
                [LineDiff::REMOVED, 'four'],
                [LineDiff::ADDED, 'FOUR'],
                [LineDiff::KEPT, 'five'],
            ],
            LineDiff::compare($old, $new, 1),
        );
    }

    public function testSeparatesDistantChangesWithAGap(): void
    {
        $old = "a\nb\nc\nd\ne\nf\ng\n";
        $new = "A\nb\nc\nd\ne\nf\nG\n";

        self::assertSame(
            [
                [LineDiff::REMOVED, 'a'],
                [LineDiff::ADDED, 'A'],
                [LineDiff::KEPT, 'b'],
                [LineDiff::GAP, ''],
                [LineDiff::KEPT, 'f'],
                [LineDiff::REMOVED, 'g'],
                [LineDiff::ADDED, 'G'],
            ],
            LineDiff::compare($old, $new, 1),
        );
    }

    public function testReportsPureAdditionsAndRemovals(): void
    {
        self::assertSame([[LineDiff::ADDED, 'a'], [LineDiff::ADDED, 'b']], LineDiff::compare('', "a\nb\n"));
        self::assertSame([[LineDiff::REMOVED, 'a']], LineDiff::compare("a\n", ''));
        self::assertSame(
            [[LineDiff::KEPT, 'a'], [LineDiff::ADDED, 'b']],
            LineDiff::compare("a\n", "a\nb\n"),
        );
    }
}
