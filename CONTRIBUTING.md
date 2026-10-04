# Contributing

Thanks for helping improve AxonPHP CLI. Bug reports, new tool detections and
new providers are all welcome.

## Setup

You need PHP 8.2 or newer and [Composer](https://getcomposer.org/).

```bash
git clone https://github.com/maximilianfeix/AxonPHPCLI.git
cd AxonPHPCLI
composer install
```

Run the command from the clone against any project:

```bash
php bin/axonphp ci:init github --working-dir /path/to/project --dry-run
```

## Checks

One command runs everything CI runs:

```bash
composer check
```

| Command           | What it does                      |
| ----------------- | --------------------------------- |
| `composer test`   | Runs the PHPUnit test suite       |
| `composer coverage` | Runs the tests and prints the line coverage (needs Xdebug or PCOV) |
| `composer stan`   | Runs PHPStan at the maximum level |
| `composer cs`     | Checks the code style             |
| `composer cs:fix` | Fixes the code style              |
| `composer ci:check` | Checks that the CI workflow matches the project |
| `composer site`   | Rebuilds the website in `docs/`   |

## How the code is organised

| Path            | Responsibility                                                       |
| --------------- | -------------------------------------------------------------------- |
| `src/Project/`  | Reads `composer.json` and describes the project (`ProjectInspector`) |
| `src/Pipeline/` | Decides which jobs a pipeline has and what they run (`Plan`)         |
| `src/Provider/` | Translates a `Plan` into the YAML of one CI service                  |
| `src/Command/`  | The console commands: input validation, output, writing the file     |
| `src/Diff/`     | The line diff printed by `ci:check`                                  |
| `site/`         | Template and build script for the website in `docs/`                 |

### Detecting another tool

Add one entry to `ToolCatalog::definitions()` with the Composer package names
that provide the tool, its type and the command CI should run. Add a case to
`ProjectInspectorTest`, then run `composer site` to update the table on the
website.

### Adding a provider

1. Implement `AxonPHP\Cli\Provider\Provider`.
2. Register it in `ProviderRegistry::default()`.
3. Add a test that parses the rendered YAML and asserts on its structure, like
   `GitHubActionsProviderTest`.
4. Document it in the README and run `composer site`, which picks the new
   provider up for the website.

## Pull requests

- Branch from `develop` and open the pull request against `develop`.
- Keep a pull request to one change, and add tests for new behaviour. CI
  requires 100% line coverage of `src/`.
- Add a line to the "Unreleased" section of `CHANGELOG.md` for anything users
  will notice.
