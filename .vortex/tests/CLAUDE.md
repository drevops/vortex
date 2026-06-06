# Testing System Guide

## Overview

This directory holds **PHPUnit** integration tests that exercise complete
workflows in real Docker containers. Tests run against an actual Drupal site
with real database and services. Slow execution (~minutes).

## Setup

```bash
cd .vortex
ahoy install  # Install dependencies (run once)
```

## Commands

```bash
cd .vortex/tests

composer install      # Install dependencies
composer lint         # Run phpcs, phpstan, rector --dry-run
composer lint-fix     # Run rector, phpcbf
composer test         # Run PHPUnit tests
```

## PHPUnit - Integration Testing Workflows

**Use when**: Changed ahoy commands, Docker setup, or need to test full build/deploy workflow.

**References**:
- `vendor/alexskrypnyk/phpunit-helpers/README.md` - Test helpers, traits, assertions
- `vendor/alexskrypnyk/file/README.md` - File operations, batch processing

```bash
cd .vortex/tests
./vendor/bin/phpunit                          # All tests
./vendor/bin/phpunit --filter "TestClassName" # Specific test
```

### cmd() Function

Combines `processRun()` + `assertProcessSuccessful()` + assertions:

```php
$this->cmd('ahoy drush cr');                    // Simple
$this->cmd('ahoy info', 'Project name');        // With assertion
$this->cmd('ahoy info', ['line1', 'line2']);    // Multiple
$this->cmd('ahoy reset', inp: ['y'], tio: 300); // Named params
```

### cmdFail() Function

```php
$this->cmdFail('ahoy lint-be', tio: 120);
```

### Output Prefixes

**CRITICAL**: If using ANY prefix, ALL strings must have prefixes.

- `+` exact match present
- `*` substring present
- `-` exact match absent
- `!` substring absent

```php
// ✅ All prefixed
$this->cmd('ahoy info', ['* Xdebug', '! Enabled']);

// ❌ Mixed - throws exception
$this->cmd('ahoy info', ['Xdebug', '! Enabled']);
```

### Cross-Platform File Operations

Use `AlexSkrypnyk\File\File` class:

```php
use AlexSkrypnyk\File\File;

File::mkdir($path);
File::dump($path, $content);
File::remove($path);
File::rmdir($path);
```

Use `self::$tmp` for temporary files (auto-cleaned).

## Shell script patterns

Shipped scripts follow a shared structure - see the Tooling package section in
[`.vortex/CLAUDE.md`](../CLAUDE.md#tooling-package).

## Test maintenance

When updating PHPUnit tests:

1. Update the test class.
2. Run `ahoy update-snapshots` from `.vortex/` (see Snapshots in `.vortex/CLAUDE.md`).
