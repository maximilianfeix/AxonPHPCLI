<?php

declare(strict_types=1);

/*
 * Builds docs/index.html, the GitHub Pages site.
 *
 * The pipeline examples, the detection table and the counts are rendered by the
 * same code the CLI runs. A test runs this script with --check, so a change to
 * the CLI that is not followed by "composer site" fails the build.
 *
 * Usage:
 *   composer site          write docs/index.html
 *   composer site:check    exit with 1 when docs/index.html is out of date
 */

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\ProviderRegistry;

require dirname(__DIR__).'/vendor/autoload.php';

function e(string $text): string
{
    return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Minimal YAML highlighting: comments, keys and quoted strings.
 *
 * Tokens are found in the raw text and escaped one by one, so characters that
 * need escaping inside a string cannot break the highlighting.
 */
function highlightYaml(string $yaml): string
{
    $lines = [];

    foreach (explode("\n", rtrim($yaml)) as $line) {
        if (str_starts_with(ltrim($line), '#')) {
            $lines[] = '<span class="c">'.e($line).'</span>';

            continue;
        }

        $prefix = '';

        if (1 === preg_match('/^(\s*(?:- )?)([A-Za-z_][\w.:-]*?)(:)(?=\s|$)/', $line, $matches)) {
            $prefix = e($matches[1]).'<span class="k">'.e($matches[2]).'</span>'.$matches[3];
            $line = substr($line, strlen($matches[0]));
        }

        $parts = preg_split('/(\'(?:[^\']|\'\')*\'|"(?:[^"\\\]|\\\.)*")/', $line, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $html = '';

        foreach (false === $parts ? [$line] : $parts as $index => $part) {
            // With PREG_SPLIT_DELIM_CAPTURE the quoted strings sit at the odd positions.
            $html .= 1 === $index % 2 ? '<span class="s">'.e($part).'</span>' : e($part);
        }

        $lines[] = $prefix.$html;
    }

    return implode("\n", $lines);
}

$packages = ['phpunit/phpunit', 'phpstan/phpstan', 'friendsofphp/php-cs-fixer'];
$project = new Project(
    ['8.2', '8.3', '8.4', '8.5'],
    ['intl'],
    (new ToolCatalog())->detect($packages),
    true,
    '^8.2',
    'acme/app',
);
$options = new PipelineOptions(['main'], coverage: true);

$examples = [];

foreach (ProviderRegistry::default()->all() as $provider) {
    $examples[] = [
        'name' => $provider->name(),
        'label' => $provider->label(),
        'path' => $provider->path(),
        'yaml' => highlightYaml($provider->render($project, $options)),
    ];
}

$tools = [];

foreach (ToolCatalog::definitions() as [$candidates, $tool]) {
    $tools[] = [
        'name' => $tool->name,
        'type' => $tool->type->label(),
        'packages' => $candidates,
        'command' => $tool->command,
    ];
}

// The commands AxonPHP adds on top of the ones every Symfony Console application has.
$commandCount = count(array_diff(
    array_keys((new Application())->all()),
    ['help', 'list', 'completion', '_complete'],
));

$repository = 'https://github.com/maximilianfeix/AxonPHPCLI';

ob_start();

require __DIR__.'/index.php';

$html = (string) ob_get_clean();
$target = dirname(__DIR__).'/docs/index.html';

$arguments = $_SERVER['argv'] ?? [];

if (is_array($arguments) && in_array('--check', $arguments, true)) {
    $current = is_file($target) ? (string) file_get_contents($target) : '';

    if (str_replace("\r\n", "\n", $current) !== $html) {
        fwrite(\STDERR, "docs/index.html is out of date. Run \"composer site\".\n");

        exit(1);
    }

    fwrite(\STDOUT, "docs/index.html is up to date.\n");

    exit(0);
}

if (false === file_put_contents($target, $html)) {
    fwrite(\STDERR, sprintf("Could not write %s.\n", $target));

    exit(1);
}

fwrite(\STDOUT, sprintf("Wrote %s (%d bytes)\n", $target, strlen($html)));
