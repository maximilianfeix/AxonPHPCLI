<?php

declare(strict_types=1);

/*
 * Builds docs/index.html, the GitHub Pages site.
 *
 * The pipeline examples, the detection table and the terminal summary are rendered
 * by the same code the CLI runs. A test runs this script with --check, so a change to
 * the CLI that is not followed by "composer site" fails the build.
 *
 * Usage:
 *   composer site          write docs/index.html
 *   composer site:check    exit with 1 when docs/index.html is out of date
 */

use AxonPHP\Cli\Application;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\ProjectInspector;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Project\ToolType;
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

/**
 * Highlights the keys and string values of pretty-printed JSON.
 */
function highlightJson(string $json): string
{
    $lines = [];

    foreach (explode("\n", $json) as $line) {
        $parts = preg_split('/("(?:[^"\\\]|\\\.)*")(\s*:)?/', $line, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $parts = false === $parts ? [$line] : $parts;
        $html = '';

        // Each match contributes the quoted string and, for keys, the colon that follows it.
        for ($index = 0, $count = count($parts); $index < $count; ++$index) {
            if (1 !== $index % 3) {
                $html .= e($parts[$index]);

                continue;
            }

            $isKey = '' !== ($parts[$index + 1] ?? '');
            $html .= sprintf('<span class="%s">%s</span>', $isKey ? 'k' : 's', e($parts[$index]));
        }

        $lines[] = $html;
    }

    return implode("\n", $lines);
}

/**
 * Runs the real inspection on a composer.json written to a temporary directory.
 *
 * @param ?array<string, mixed> $manifest null for a project without composer.json
 */
function inspectManifest(ProjectInspector $inspector, ?array $manifest): Project
{
    $directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'axonphp-site-'.bin2hex(random_bytes(6));
    $file = $directory.\DIRECTORY_SEPARATOR.'composer.json';
    mkdir($directory);

    try {
        if (null !== $manifest) {
            file_put_contents($file, json_encode($manifest, \JSON_THROW_ON_ERROR));
        }

        return $inspector->inspect($directory);
    } finally {
        if (is_file($file)) {
            unlink($file);
        }

        rmdir($directory);
    }
}

// The projects of the playground. Each one goes through the same inspection and rendering as a real project.
$definitions = [
    [
        'id' => 'library',
        'label' => 'Library',
        'about' => 'A package that supports four PHP versions, measures coverage and tests its lowest dependencies.',
        'manifest' => [
            'name' => 'acme/library',
            'require' => ['php' => '^8.2', 'ext-intl' => '*'],
            'require-dev' => [
                'phpunit/phpunit' => '^12.0',
                'phpstan/phpstan' => '^2.0',
                'friendsofphp/php-cs-fixer' => '^3.0',
            ],
            'extra' => ['axonphp' => ['min-coverage' => 90, 'lowest' => true]],
        ],
    ],
    [
        'id' => 'laravel',
        'label' => 'Laravel app',
        'about' => 'Pest, Larastan and Pint, with a security audit before anything else runs.',
        'manifest' => [
            'name' => 'acme/shop',
            'require' => ['php' => '^8.3', 'ext-pdo' => '*', 'laravel/framework' => '^12.0'],
            'require-dev' => [
                'pestphp/pest' => '^4.0',
                'larastan/larastan' => '^3.0',
                'laravel/pint' => '^1.0',
            ],
            'extra' => ['axonphp' => ['audit' => true]],
        ],
    ],
    [
        'id' => 'symfony',
        'label' => 'Symfony app',
        'about' => 'One PHP version, architecture rules, dependency checks and two long-lived branches.',
        'manifest' => [
            'name' => 'acme/api',
            'require' => ['php' => '>=8.4', 'ext-ctype' => '*', 'ext-iconv' => '*', 'symfony/framework-bundle' => '^7.3'],
            'require-dev' => [
                'phpunit/phpunit' => '^12.0',
                'phpstan/phpstan' => '^2.0',
                'deptrac/deptrac' => '^3.0',
                'friendsofphp/php-cs-fixer' => '^3.0',
                'icanhazstring/composer-unused' => '^0.9',
            ],
            'extra' => ['axonphp' => ['branches' => ['main', 'develop'], 'audit' => true]],
        ],
    ],
    [
        'id' => 'plain',
        'label' => 'No Composer',
        'about' => 'Nothing to detect yet. The pipeline lints every PHP file, so it is useful from day one.',
        'manifest' => null,
    ],
];

$inspector = new ProjectInspector();
$providers = ProviderRegistry::default()->all();
$presets = [];

foreach ($definitions as $definition) {
    $project = inspectManifest($inspector, $definition['manifest']);
    $settings = $project->settings;
    $options = new PipelineOptions(
        $settings->branches ?? ['main'],
        $settings->coverage ?? null !== $settings->minCoverage,
        $settings->lowest ?? false,
        $settings->audit ?? false,
        $settings->minCoverage,
    );

    $outputs = [];

    foreach ($providers as $provider) {
        $outputs[$provider->name()] = highlightYaml($provider->render($project, $options));
    }

    $presets[] = [
        'id' => $definition['id'],
        'label' => $definition['label'],
        'about' => $definition['about'],
        'manifest' => null === $definition['manifest']
            ? null
            : highlightJson(json_encode($definition['manifest'], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)),
        'versions' => $project->phpVersions,
        'tools' => array_map(static fn (Tool $tool): string => $tool->name, $project->tools),
        'outputs' => $outputs,
    ];
}

$services = [];

foreach ($providers as $provider) {
    $services[] = ['name' => $provider->name(), 'label' => $provider->label(), 'path' => $provider->path()];
}

$tools = [];

foreach (ToolCatalog::definitions() as [$candidates, $tool]) {
    $tools[] = [
        'name' => $tool->name,
        'type' => $tool->type->value,
        'typeLabel' => $tool->type->label(),
        'packages' => $candidates,
        'command' => $tool->command,
    ];
}

$toolTypes = [];

foreach (ToolType::cases() as $type) {
    $toolTypes[$type->value] = $type->label();
}

// The rows "ci:init" prints for the first project, shown in the terminal at the top of the page.
$library = inspectManifest($inspector, $definitions[0]['manifest']);
$toolNames = static fn (ToolType $type): string => implode(', ', array_map(
    static fn (Tool $tool): string => $tool->name,
    $library->tools($type),
));

$summary = [
    ['Project', '/home/you/acme-library', ''],
    ['PHP versions', implode(', ', $library->phpVersions), sprintf('(from "php": "%s")', (string) $library->phpConstraint)],
    ['Extensions', implode(', ', $library->extensions), ''],
    ['Tests', $toolNames(ToolType::Tests), ''],
    ['Static analysis', $toolNames(ToolType::StaticAnalysis), ''],
    ['Code style', $toolNames(ToolType::CodeStyle), ''],
    ['Branches', 'main', ''],
    ['Extras', 'coverage (at least 90%), lowest dependencies', ''],
];

$version = Application::VERSION;
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
