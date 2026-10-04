<p align="center">
  <img src="docs/banner.svg" alt="AxonPHP CLI: one command, a CI pipeline that fits your PHP project" width="100%">
</p>

<p align="center">
  <a href="https://github.com/maximilianfeix/AxonPHPCLI/actions/workflows/ci.yml"><img src="https://github.com/maximilianfeix/AxonPHPCLI/actions/workflows/ci.yml/badge.svg" alt="CI status"></a>
  <img src="https://img.shields.io/badge/php-8.2%20%E2%80%93%208.5-777BB4?logo=php&logoColor=white" alt="PHP 8.2 to 8.5">
  <img src="https://img.shields.io/badge/PHPStan-level%20max-2a5ea7" alt="PHPStan level max">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-3da639" alt="MIT license"></a>
</p>

# AxonPHP CLI

AxonPHP CLI writes the CI pipeline for your PHP project so you don't have to
copy one from your last repository and fix it up by hand.

It reads your `composer.json`, works out which PHP versions you support and
which tools you use, and generates a GitHub Actions workflow or a GitLab CI
pipeline that runs exactly those. No config file, no questions to answer.

<p align="center">
  <img src="docs/demo.svg" alt="Terminal output of axonphp ci:init github" width="860">
</p>

## Contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [What gets detected](#what-gets-detected)
- [Command reference](#command-reference)
- [Example output](#example-output)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Zero configuration.** Everything is derived from `composer.json`.
- **A PHP matrix that matches your constraint.** `"php": "^8.2"` becomes a test
  matrix of 8.2, 8.3, 8.4 and 8.5.
- **Runs the tools you already use.** PHPUnit or Pest, PHPStan or Psalm,
  PHP-CS-Fixer, Pint or PHP_CodeSniffer.
- **Installs the extensions you require.** Every `ext-*` in `require` is set up
  in the pipeline.
- **Sensible pipeline defaults.** Composer caching, least-privilege
  permissions, cancellation of superseded runs, and no duplicate pipelines for
  a push to a branch with an open pull request.
- **Safe to run.** It never overwrites an existing pipeline without asking, and
  `--dry-run` shows the result first.
- **GitHub Actions and GitLab CI** from the same detection.

## Installation

AxonPHP CLI requires PHP 8.2 or newer. Install it as a development dependency
of the project you want a pipeline for.

The package is not on Packagist yet, so point Composer at the repository first:

```bash
composer config repositories.axonphp vcs https://github.com/maximilianfeix/AxonPHPCLI
composer require --dev maxim/axonphp-cli:dev-main
```

## Quick start

From the root of your project:

```bash
vendor/bin/axonphp ci:init github
```

That creates `.github/workflows/ci.yml`. Commit it, push, and the pipeline runs.

For GitLab:

```bash
vendor/bin/axonphp ci:init gitlab
```

Leave the provider out and AxonPHP asks which one you want. To look before you
write anything:

```bash
vendor/bin/axonphp ci:init github --dry-run
```

## What gets detected

| From `composer.json`              | Result in the pipeline                                    |
| --------------------------------- | --------------------------------------------------------- |
| `require.php`                     | The PHP versions in the test matrix                       |
| `ext-*` packages                  | Extensions installed before your dependencies             |
| `pestphp/pest`                    | `vendor/bin/pest`                                         |
| `phpunit/phpunit`                 | `vendor/bin/phpunit`                                      |
| `symfony/phpunit-bridge`          | `vendor/bin/simple-phpunit`                               |
| `phpstan/phpstan`, `larastan/larastan` | `vendor/bin/phpstan analyse --no-progress`           |
| `vimeo/psalm`                     | `vendor/bin/psalm --no-progress`                          |
| `friendsofphp/php-cs-fixer`, `php-cs-fixer/shim` | `vendor/bin/php-cs-fixer check --diff`     |
| `laravel/pint`                    | `vendor/bin/pint --test`                                  |
| `squizlabs/php_codesniffer`       | `vendor/bin/phpcs`                                        |

Tests run on every PHP version in the matrix. Code style and static analysis
run once, on the newest version, in a separate job.

A few details worth knowing:

- Without a `php` constraint, the matrix defaults to 8.2, 8.3, 8.4 and 8.5.
- Pest wins over PHPUnit when both are installed, since Pest ships with it.
- With no test tool installed, the pipeline lints every PHP file with `php -l`
  instead, so it is still useful on day one.
- With no `composer.json` at all, you get that lint-only pipeline and a warning.

## Command reference

```text
axonphp ci:init [options] [<provider>]
```

| Argument / option     | Description                                                              |
| --------------------- | ------------------------------------------------------------------------ |
| `provider`            | `github` or `gitlab`. Asked interactively when omitted.                   |
| `-p, --php=VERSION`   | PHP version to test against. Repeat it to build your own matrix.          |
| `-b, --branch=NAME`   | Branch whose pushes trigger the pipeline. Repeatable. Default: `main`.    |
| `-d, --working-dir=DIR` | Project directory. Default: the current directory.                      |
| `-f, --force`         | Overwrite an existing pipeline file without asking.                       |
| `--dry-run`           | Print the pipeline to stdout and write nothing.                           |

| Provider | Service        | File written               |
| -------- | -------------- | -------------------------- |
| `github` | GitHub Actions | `.github/workflows/ci.yml` |
| `gitlab` | GitLab CI      | `.gitlab-ci.yml`           |

Some examples:

```bash
# Test only on PHP 8.4 and 8.5, and also run on pushes to develop
vendor/bin/axonphp ci:init github --php 8.4 --php 8.5 --branch main --branch develop

# Generate a pipeline for a project somewhere else
vendor/bin/axonphp ci:init gitlab --working-dir ../other-project

# Regenerate after adding PHPStan to the project
vendor/bin/axonphp ci:init github --force
```

In `--dry-run` mode the summary goes to stderr and only the pipeline goes to
stdout, so you can redirect or diff it:

```bash
vendor/bin/axonphp ci:init github --dry-run | diff .github/workflows/ci.yml -
```

The command exits with `0` on success, `1` when the project cannot be read or
the file cannot be written, and `2` for invalid arguments.

Shell completion for commands, options and provider names is available through
`vendor/bin/axonphp completion --help`.

## Example output

This repository uses the workflow AxonPHP CLI generated for itself:
[`.github/workflows/ci.yml`](.github/workflows/ci.yml).

<details>
<summary>GitHub Actions workflow for a project with PHPUnit, PHPStan and PHP-CS-Fixer</summary>

```yaml
name: CI

on:
  push:
    branches: ['main']
  pull_request:

permissions:
  contents: read

concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  quality:
    name: Code quality
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v7

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          coverage: none

      - name: Install dependencies
        uses: ramsey/composer-install@v4

      - name: Static analysis (PHPStan)
        run: vendor/bin/phpstan analyse --no-progress

      - name: Code style (PHP-CS-Fixer)
        run: vendor/bin/php-cs-fixer check --diff

  tests:
    name: Tests (PHP ${{ matrix.php }})
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        php: ['8.2', '8.3', '8.4', '8.5']

    steps:
      - name: Checkout
        uses: actions/checkout@v7

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none

      - name: Validate composer.json
        run: composer validate --strict

      - name: Install dependencies
        uses: ramsey/composer-install@v4

      - name: Tests (PHPUnit)
        run: vendor/bin/phpunit
```

</details>

<details>
<summary>GitLab CI pipeline for the same project</summary>

```yaml
stages:
  - quality
  - test

workflow:
  rules:
    - if: $CI_PIPELINE_SOURCE == 'merge_request_event'
    - if: $CI_COMMIT_TAG
    - if: $CI_COMMIT_BRANCH == 'main'

variables:
  COMPOSER_ALLOW_SUPERUSER: '1'
  COMPOSER_NO_INTERACTION: '1'
  COMPOSER_CACHE_DIR: $CI_PROJECT_DIR/.composer-cache

default:
  cache:
    key:
      files:
        - composer.lock
        - composer.json
    paths:
      - .composer-cache/
  before_script:
    - curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
    - chmod +x /usr/local/bin/install-php-extensions
    - install-php-extensions @composer zip
    - composer install --prefer-dist --no-progress

quality:
  stage: quality
  image: php:8.5-cli
  script:
    - vendor/bin/phpstan analyse --no-progress
    - vendor/bin/php-cs-fixer check --diff

tests:
  stage: test
  image: php:${PHP_VERSION}-cli
  parallel:
    matrix:
      - PHP_VERSION: ['8.2', '8.3', '8.4', '8.5']
  script:
    - composer validate --strict
    - vendor/bin/phpunit
```

</details>

The generated file is a starting point that belongs to you. Edit it freely;
AxonPHP only touches it again when you run the command with `--force`.

## Development

```bash
git clone https://github.com/maximilianfeix/AxonPHPCLI.git
cd AxonPHPCLI
composer install
composer check   # code style, PHPStan (level max) and tests
```

The code is small and split by responsibility:

```text
src/
├── Command/    the ci:init console command
├── Project/    reads composer.json: PHP versions, extensions, tools
└── Provider/   renders a pipeline for GitHub Actions or GitLab CI
```

Adding a tool is one entry in `ToolCatalog`. Adding a CI service is one class
implementing `Provider`. [CONTRIBUTING.md](CONTRIBUTING.md) walks through both.

## Contributing

Issues and pull requests are welcome. Please read
[CONTRIBUTING.md](CONTRIBUTING.md) first, and report security problems
privately as described in [SECURITY.md](SECURITY.md).

Changes are recorded in the [changelog](CHANGELOG.md).

## License

Released under the [MIT License](LICENSE).
