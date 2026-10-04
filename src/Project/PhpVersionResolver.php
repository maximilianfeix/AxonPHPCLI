<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

use Composer\Semver\Semver;

/**
 * Turns a Composer "php" constraint into the list of minor versions worth testing.
 */
final class PhpVersionResolver
{
    /** Minor versions a constraint can be matched against, oldest first. */
    public const KNOWN = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

    /** Used when the project does not say which versions it supports. */
    public const DEFAULT = ['8.2', '8.3', '8.4', '8.5'];

    /**
     * @return list<string>
     */
    public function resolve(?string $constraint): array
    {
        if (null === $constraint || '' === trim($constraint)) {
            return self::DEFAULT;
        }

        $versions = [];

        try {
            foreach (self::KNOWN as $version) {
                // Match against a late patch release so that "^8.2.4" still selects 8.2.
                if (Semver::satisfies($version.'.99', $constraint)) {
                    $versions[] = $version;
                }
            }
        } catch (\UnexpectedValueException) {
            return self::DEFAULT;
        }

        return [] === $versions ? self::DEFAULT : $versions;
    }
}
