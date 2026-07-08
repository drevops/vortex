# AGENTS.md

This file provides guidance to AI agents when working with
code in this repository.


## Project Overview

This project is a PHP library for building panel-based terminal forms:
keyboard-driven questionnaires that collect answers interactively through a
TUI or headlessly from a JSON payload and environment variables.


## PHP Application Architecture


### Library API

This package is a library consumed programmatically - it has no CLI entry point
of its own. The public surface is:

- **`DrevOps\Tui\Tui`** - the facade: collect a form's answers, headlessly or
  through the interactive panel TUI.
- **`DrevOps\Tui\Builder\Form`** - the fluent builder for declaring a form's
  panels and fields.

A consumer declares a form with `Form::create(...)->panel(...)` and drives it
through the `Tui` facade.


### Namespace Structure

- Source code: `DrevOps\Tui\`
- Tests: `DrevOps\Tui\Tests\`
- Autoloading: PSR-4 via Composer

## Commands

### Code Quality

```bash
# Run all linters (PHPCS, PHPStan, Rector)
composer lint

# Auto-fix code style issues
composer lint-fix

# Individual tools
./vendor/bin/phpcs # Check coding standards
./vendor/bin/phpcbf # Fix coding standards
./vendor/bin/phpstan # Static analysis (level 9)
./vendor/bin/rector --dry-run # Check Rector suggestions
```

### Testing

```bash
# Run all PHPUnit tests (fast, no coverage)
composer test

# Run with coverage reports
composer test-coverage
# Coverage reports: .logs/.coverage-html/index.html, .logs/cobertura.xml

# Run specific test file
./vendor/bin/phpunit tests/phpunit/Unit/TuiTest.php

# Run specific test method
./vendor/bin/phpunit --filter testMethodName
```


### Dependencies


```bash
# Clean and reinstall dependencies
composer reset # removes vendor/ and composer.lock
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
- **Never model a closed set of values as string literals.** Any value that is
  one-of-a-fixed-set (a kind, a state, a mode, a source) is a backed or pure
  enum, and every property, parameter and return that carries it is typed with
  the enum - existing examples: `FieldType`, `Provenance`, `Source`,
  `KeyName`. String literals for such values are forbidden in source and in
  tests alike; use the enum case (and its `->value` only at a rendering or
  serialization boundary).

## Testing Patterns

### PHPUnit Structure

- `tests/phpunit/Unit/` - Unit tests, no real TTY I/O
- `tests/phpunit/Fixtures/` - Fixture handler classes used by the tests

### Writing Tests

Tests should use PHPUnit 11 features:

- Coverage attributes: `#[CoversClass(ClassName::class)]`
- Test attributes: `#[Test]` (optional, using `test` prefix is also fine)
- Data providers: `#[DataProvider('providerMethodName')]`


## CI/CD

GitHub Actions workflows test across:

- PHP versions: 8.3, 8.4, 8.5 (normal and lowest dependency sets)
- One matrix job runs lint, tests and the coverage upload (Codecov)

Key workflows:

- `.github/workflows/test-php.yml` - PHP testing


## Documentation

Architecture diagrams and a narrative walkthrough live in `docs/architecture/`.
After a structural change, update them with the `render-tui-diagrams` skill.

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
