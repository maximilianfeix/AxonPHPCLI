# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2026-10-04

### Added

- A PHAR build, `axonphp.phar`, attached to every release together with its
  SHA-256 checksum. `composer phar` builds it locally.
- Detection of ParaTest, PHPSpec and Behat. ParaTest replaces PHPUnit and can
  measure coverage; PHPSpec and Behat run in addition to the PHPUnit family.
- The website has a playground: four example projects, each rendered for all
  four providers by the code the command runs.

### Changed

- The website is redesigned: dark by default with a light theme, a filter for
  the tool table and a section on keeping pipelines in sync.

## [0.4.0] - 2026-10-04

### Added

- `ci:update` command: rewrites every pipeline in the project that is out of
  date and leaves the others alone. `--dry-run` shows the difference only.
- `tools` command: lists the supported tools with the packages that trigger
  them and the commands they run, and the supported providers, as text or JSON.
- CircleCI provider (`ci:init circleci`).
- `--min-coverage <percent>` and `extra.axonphp.min-coverage`: the coverage job
  fails below that line coverage. Setting it switches the coverage job on.
- `ci:check --format json` for scripts and `--format github` to annotate the
  pipeline file in a pull request.
- Detection of PHPArkitect, Twig-CS-Fixer, Composer Normalize, Composer Require
  Checker, Composer Unused and Composer Dependency Analyser. Dependency checks
  are a tool type of their own and run in the quality job.

### Changed

- `ci:check` points to `ci:update` instead of `ci:init --force`.
- GitLab keeps the coverage report as an artifact when the job fails.
- The providers render a shared `Plan` instead of each deciding which jobs a
  pipeline has. Generated pipelines are otherwise unchanged.
- The shell commands moved from `Provider\Yaml` to `Pipeline\Plan`.
- This repository requires 100% line coverage in CI.

## [0.3.0] - 2026-10-04

### Added

- `ci:check` command: compares the committed pipeline with what the project
  needs now, prints the difference and exits with 1 when they differ.
- `inspect` command: shows the detected PHP versions, extensions, tools and
  options as text or JSON without writing anything.
- Bitbucket Pipelines provider (`ci:init bitbucket`).
- `--coverage` adds a code coverage job on the newest PHP version (PHPUnit or
  Pest) and keeps `coverage.xml` as an artifact.
- `--lowest` also tests the lowest allowed dependencies on the oldest PHP
  version.
- `--audit` adds a `composer audit` step.
- Pipeline options can be stored under `extra.axonphp` in `composer.json`.
  Command line options win over the stored ones.
- Detection of Rector, Deptrac, ECS and Codeception.
- A website at https://maximilianfeix.github.io/AxonPHPCLI/, built from the
  same code that renders the pipelines (`composer site`).
- A logo, favicon, banner, how-it-works diagram and social preview image in
  PHP's colours.

### Changed

- The summary printed by `ci:init` lists the trigger branches and the enabled
  extras.
- `Provider::render()` takes a `PipelineOptions` object instead of the list of
  branches.

### Fixed

- A branch name or PHP version ending in a newline was accepted.
- `"php": "8.1.20"` and `">=8.1 <8.1.30"` resolved to the default matrix
  instead of 8.1. A `php` constraint in `require-dev` now narrows the matrix,
  and a constraint that matches no known version produces a warning.
- GitLab: a push to a listed branch with an open merge request started two
  pipelines.
- GitLab and Bitbucket: the lowest-dependencies job installed from the lock
  file before resolving the lowest versions, which could fail on the oldest
  PHP version.
- Composer's `bin-dir` and `vendor-dir` were ignored in the generated commands.
- The pipeline file is written atomically, so a failed write cannot leave a
  truncated file behind.
- Empty `--php` and `--branch` values, branch names git would refuse and a JSON
  array as `composer.json` are rejected.

## [0.2.0] - 2026-10-04

### Added

- Project detection: `ci:init` reads `composer.json` and derives the PHP version
  matrix from the `php` constraint, the required extensions from `ext-*`
  packages, and the tools to run from the installed packages.
- Detected tools: PHPUnit, Pest, Symfony PHPUnit Bridge, PHPStan (including
  Larastan), Psalm, PHP-CS-Fixer, Pint and PHP_CodeSniffer.
- GitLab CI provider (`ci:init gitlab`).
- `--php` and `--branch` options to override the detected PHP versions and the
  branches that trigger the pipeline.
- `--dry-run` to print the pipeline to stdout instead of writing it.
- `--working-dir` to generate a pipeline for a project in another directory.
- Interactive provider selection when no provider is passed.
- Shell completion for the provider argument.
- Test suite, PHPStan at the maximum level, and a CI workflow generated by the
  tool itself.

### Changed

- The GitHub workflow is now written to `.github/workflows/ci.yml` (previously
  `test.yml`), runs on pull requests as well as pushes, caches Composer
  dependencies, uses least-privilege permissions and cancels superseded runs.
- An existing pipeline file is no longer overwritten silently: the command asks
  for confirmation, or requires `--force` when run non-interactively.
- Command output is now in English.
- The code moved to `src/` under the `AxonPHP\Cli` namespace.
- `symfony/console` 8 is supported alongside 7.3+.

### Fixed

- The generated workflow ran `phpunit` from the global path, which fails unless
  PHPUnit is installed globally. It now runs the project's own binaries from
  `vendor/bin`.
- The binary could not find the autoloader when installed as a dependency.

## 0.1.0 - 2026-06-05

### Added

- `ci:init github` command that copies a static GitHub Actions workflow into
  the project.

[Unreleased]: https://github.com/maximilianfeix/AxonPHPCLI/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/maximilianfeix/AxonPHPCLI/releases/tag/v0.5.0
[0.4.0]: https://github.com/maximilianfeix/AxonPHPCLI/releases/tag/v0.4.0
[0.3.0]: https://github.com/maximilianfeix/AxonPHPCLI/releases/tag/v0.3.0
[0.2.0]: https://github.com/maximilianfeix/AxonPHPCLI/releases/tag/v0.2.0
