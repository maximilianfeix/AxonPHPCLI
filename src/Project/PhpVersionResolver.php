<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

use Composer\Semver\VersionParser;

/**
 * Turns Composer "php" constraints into the list of minor versions worth testing.
 */
final class PhpVersionResolver
{
    /** Minor versions a constraint can be matched against, oldest first. */
    public const KNOWN = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

    /** Used when the project does not say which versions it supports. */
    public const DEFAULT = ['8.2', '8.3', '8.4', '8.5'];

    /**
     * A minor version is selected when at least one of its releases satisfies every constraint,
     * so "8.1.20" and ">=8.1 <8.1.30" both select 8.1.
     *
     * @param ?string ...$constraints the constraints the project puts on PHP; null and blank ones are skipped
     *
     * @return null|list<string> the defaults without any constraint, null when the constraints
     *                           cannot be parsed or allow none of the known versions
     */
    public function resolve(?string ...$constraints): ?array
    {
        $constraints = array_filter(
            $constraints,
            static fn (?string $constraint): bool => null !== $constraint && '' !== trim($constraint),
        );

        if ([] === $constraints) {
            return self::DEFAULT;
        }

        $parser = new VersionParser();
        $versions = [];

        try {
            $required = array_map($parser->parseConstraints(...), $constraints);

            foreach (self::KNOWN as $version) {
                [$major, $minor] = array_map(intval(...), explode('.', $version));

                // Every release of the minor version, excluding pre-releases of the next one.
                $releases = $parser->parseConstraints(sprintf('>=%d.%d.0 <%d.%d.0-dev', $major, $minor, $major, $minor + 1));

                foreach ($required as $constraint) {
                    if (!$constraint->matches($releases)) {
                        continue 2;
                    }
                }

                $versions[] = $version;
            }
        } catch (\UnexpectedValueException) {
            return null;
        }

        return [] === $versions ? null : $versions;
    }
}
