# Installer System Guide

## Overview

Symfony Console application that customizes the Vortex template based on user
selections.

**Technology**: PHP, Symfony Console, PHPUnit

## Commands

```bash
cd .vortex/installer

composer install      # Install dependencies
composer lint         # Run phpcs, phpstan, rector --dry-run
composer lint-fix     # Run rector, phpcbf
composer test         # Run tests (no coverage)
composer test-coverage # Run tests with coverage

# Specific test filters
./vendor/bin/phpunit --filter "Handlers\\\\"     # Handler tests only
./vendor/bin/phpunit --filter "HandlerNameTest"  # Specific handler
```

## Fixture System

### Architecture

**Baseline + diff** system:

```
tests/Fixtures/install/
├── _baseline/              # Complete template files
├── services_no_clamav/     # Diff: removes ClamAV
├── services_no_solr/       # Diff: removes Solr
├── hosting_acquia/         # Diff: Acquia modifications
└── [other scenarios]/
```

### Updating Fixtures

**CRITICAL**: Never modify fixture files directly. All fixture files
(including `_baseline/`) are regenerated from the root template files by
`ahoy update-snapshots`. Only modify the root template files — the user
will run the snapshot update themselves.

```bash
# From .vortex/ directory (recommended)
ahoy update-snapshots

# Manual (for debugging specific scenarios)
cd .vortex/installer
UPDATE_SNAPSHOTS=1 ./vendor/bin/phpunit --filter "testHandlerProcess.*baseline"
```

## Conditional Token System

### Patterns

**Markdown**:

```markdown
[//]: # (#;< TOKEN_NAME)
Content removed if feature not selected
[//]: # (#;> TOKEN_NAME)
```

**Shell/YAML**:

```bash
#;< TOKEN_NAME
Content removed if feature not selected
#;> TOKEN_NAME
```

### Available Tokens

| Category | Tokens                                                                             |
|----------|------------------------------------------------------------------------------------|
| Theme    | `DRUPAL_THEME`                                                                     |
| Services | `SERVICE_CLAMAV`, `SERVICE_SOLR`, `SERVICE_REDIS`                                  |
| CI       | `CI_PROVIDER_GHA`, `CI_PROVIDER_CIRCLECI`                                          |
| Hosting  | `HOSTING_LAGOON`, `HOSTING_ACQUIA`                                                 |
| Deploy   | `DEPLOY_TYPES_CONTAINER_REGISTRY`, `DEPLOY_TYPES_WEBHOOK`, `DEPLOY_TYPES_ARTIFACT` |

### Handler Locations

`.vortex/installer/src/Prompts/Handlers/`:

- `CiProvider.php`, `HostingProvider.php`, `Services.php`, `Theme.php`

## Handler Development

### Key Pattern

Handlers **queue** operations, PromptManager **executes**:

```php
// In handlers - queue only
File::replaceContentAsync('old', 'new');
File::replaceTokenAsync('TOKEN');

// In PromptManager - execute all
File::runTaskDirectory($this->config->get(Config::TMP));
```

### Common Pitfalls

1. Don't call `File::runTaskDirectory()` in handlers
2. Use `AlexSkrypnyk\File\Internal\ExtendedSplFileInfo`
3. Preserve complex logic in callbacks

## Test Organization

Each handler has dedicated test class extending
`AbstractHandlerProcessTestCase`:

```bash
./vendor/bin/phpunit --filter "HandlerNameInstallTest"
./vendor/bin/phpunit --filter "HandlerNameInstallTest.*scenario"
```

Structure: Test methods → Data providers → Helper methods
