# Vortex CLI

This file provides guidance to AI agents when working with code in this
repository.

## Project Overview

The Vortex CLI is a thin Symfony Console application. It ships the TUI form
configuration (the questions and their panel structure) plus concrete handler
classes that extend the generic `drevops/tui` engine and carry the
project-specific behaviour. Keep this package as thin as possible: reusable,
project-agnostic logic belongs in `drevops/tui`, not here.

## PHP Application Architecture

### Symfony Console Application

Multi-command CLI application structure:

- **Location:** `src/Command/` directory
- **Entry point:** `vortex` (wraps `src/app.php`)

### Adding New Commands

To add a Symfony Console command:

1. Create class in `src/Command/YourCommand.php` extending
   `Symfony\Component\Console\Command\Command`
2. Register in `src/app.php`: `$application->add(new YourCommand());`
3. Add functional test in `tests/phpunit/Functional/YourCommandTest.php`

### Namespace Structure

- Source code: `DrevOps\VortexCli\`
- Tests: `DrevOps\VortexCli\Tests\`
- Autoloading: PSR-4 via Composer

## Commands

### Code Quality

```bash
# Run all linters (PHPCS, PHPStan, Rector)
composer lint

# Auto-fix code style issues
composer lint-fix
```

### Testing

```bash
# Run all PHPUnit tests (fast, no coverage)
composer test

# Run with coverage reports
composer test-coverage
# Coverage reports: .logs/.coverage-html/index.html, .logs/cobertura.xml

# Run a specific test file
./vendor/bin/phpunit tests/phpunit/Functional/SomeCommandTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testMethodName
```

### Building

```bash
# Build PHAR executable (installs Box first)
composer build
```

```bash
# Clean and reinstall dependencies
composer reset # removes vendor/, vendor-bin/, composer.lock
composer install
```

## Code Quality Standards

### Three-Layer Quality Stack

1. **PHP_CodeSniffer** - Drupal coding standards + strict types requirement
  - Config: `phpcs.xml`
  - Rules: Drupal standard, Generic.PHP.RequireStrictTypes
  - Relaxed rules in test files (long arrays, missing function docs)

2. **PHPStan** - Level 9 static analysis
  - Config: `phpstan.neon`
  - Ignores: Untyped iterables in tests/data providers

3. **Rector** - PHP 8.3 modernization + code quality
  - Config: `rector.php`
  - Sets: PHP_83, CODE_QUALITY, CODING_STYLE, DEAD_CODE,
    TYPE_DECLARATION

### Coding Conventions

- All PHP files must declare `strict_types=1`
- Use single quotes for strings (double quotes if containing single quote)
- All files must end with a newline character
- Local variables/method arguments: `snake_case`
- Method names/class properties: `camelCase`

## Testing Patterns

### PHPUnit Structure

- `tests/phpunit/Unit/` - Unit tests with mocks, no I/O
- `tests/phpunit/Functional/` - Integration tests, real file system
- `tests/phpunit/Traits/` - Shared test utilities

### Writing Tests

Tests should use PHPUnit 11 features:

- Coverage attributes: `#[CoversClass(ClassName::class)]`
- Test attributes: `#[Test]` (optional, using `test` prefix is also fine)
- Data providers: `#[DataProvider('providerMethodName')]`

## CI/CD

The CLI is tested by the monorepo's `.github/workflows/vortex-test-cli.yml`
workflow. The `drevops/tui` dependency lives in its own repository and is
installed from Packagist; it tests and releases there. The CLI PHAR is
released from the monorepo root via
`.github/workflows/vortex-release-cli.yml`.

## Committing the lock file

Unlike the `drevops/tui` library, this package is a distributable
application built into a PHAR, so its `composer.lock` **is** committed to lock
the exact dependency set the released binary is built from.

## Updating from the template

This project was generated from a template and can pull the template's latest
infrastructure (CI workflows, linting and test configuration, Docker, and docs)
at any time. When you are asked to "update scaffold", fetch the updater skill
from the template repository into the local `.claude/skills/` directory, then
invoke it:

1. Create the target directory if it does not exist:

   ```bash
   mkdir -p .claude/skills/update-consumer-scaffold
   ```

2. Download the skill:

   ```bash
   curl -sSL https://raw.githubusercontent.com/AlexSkrypnyk/scaffold/main/.scaffold/skills/update-consumer-scaffold/SKILL.md -o .claude/skills/update-consumer-scaffold/SKILL.md
   ```

3. Invoke the `update-consumer-scaffold` skill and follow its steps.

The skill directory is fetched on demand and is git-ignored - it is not
committed to the project.
