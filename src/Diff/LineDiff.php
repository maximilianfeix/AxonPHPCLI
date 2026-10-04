<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Diff;

/**
 * A small line-based diff for pipeline files, which are short enough for a plain LCS table.
 */
final class LineDiff
{
    public const KEPT = ' ';
    public const REMOVED = '-';
    public const ADDED = '+';
    public const GAP = '@';

    /**
     * @param int $context unchanged lines to show around each change
     *
     * @return list<array{string, string}> pairs of marker (one of the class constants) and line; empty when equal
     */
    public static function compare(string $old, string $new, int $context = 2): array
    {
        $operations = self::operations(self::lines($old), self::lines($new));
        $changed = array_keys(array_filter($operations, static fn (array $op): bool => self::KEPT !== $op[0]));

        if ([] === $changed) {
            return [];
        }

        $visible = [];

        foreach ($changed as $index) {
            for ($i = max(0, $index - $context); $i <= min(count($operations) - 1, $index + $context); ++$i) {
                $visible[$i] = true;
            }
        }

        $result = [];
        $previous = null;

        foreach ($operations as $index => $operation) {
            if (!isset($visible[$index])) {
                continue;
            }

            if (null !== $previous && $index > $previous + 1) {
                $result[] = [self::GAP, ''];
            }

            $result[] = $operation;
            $previous = $index;
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $lines = preg_split('/\R/', rtrim($text, "\r\n"));

        return false === $lines || '' === $text ? [] : $lines;
    }

    /**
     * @param list<string> $old
     * @param list<string> $new
     *
     * @return list<array{string, string}>
     */
    private static function operations(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);

        // $lengths[$i][$j] is the length of the longest common subsequence of $old[$i..] and $new[$j..].
        $lengths = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        for ($i = $oldCount - 1; $i >= 0; --$i) {
            for ($j = $newCount - 1; $j >= 0; --$j) {
                $lengths[$i][$j] = $old[$i] === $new[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $operations = [];
        $i = 0;
        $j = 0;

        while ($i < $oldCount && $j < $newCount) {
            if ($old[$i] === $new[$j]) {
                $operations[] = [self::KEPT, $old[$i]];
                ++$i;
                ++$j;
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $operations[] = [self::REMOVED, $old[$i++]];
            } else {
                $operations[] = [self::ADDED, $new[$j++]];
            }
        }

        while ($i < $oldCount) {
            $operations[] = [self::REMOVED, $old[$i++]];
        }

        while ($j < $newCount) {
            $operations[] = [self::ADDED, $new[$j++]];
        }

        return $operations;
    }
}
