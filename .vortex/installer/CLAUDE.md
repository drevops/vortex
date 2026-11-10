# Installer System Guide

## Overview

Symfony Console application that customizes the Vortex template based on
user selections.

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

**CRITICAL**: Never modify fixture files directly. Only modify the root
template files, then regenerate the fixtures.

See `.vortex/CLAUDE.md` for the snapshot update process (always
`ahoy update-snapshots` from `.vortex/`, never composer directly).

### Updating the Installer Video

**Whenever the installer prompt flow changes** (new handler added, prompt
renamed, section reordered, prompt removed), the installer video shown in
the documentation goes stale and must be regenerated.

```bash
# From .vortex/ directory
ahoy update-videos installer
```

Requires `asciinema`, `expect`, `php`, `composer`, `npx` on PATH. Produces
`installer.json` (asciicast), `installer.svg`, `installer.png`, and
`installer.gif` under `.vortex/docs/static/img/`. Requires explicit user
permission before running.

Triggers that require re-recording:
- New `Handlers/*.php` class or handler removal.
- Wording change to `label()` or `hint()` of any existing handler.
- Adding, removing or reordering prompts inside
  `PromptManager::runPrompts()`, which also moves the progress denominator.

## Conditional Token System

### When to use fences

Fences (`#;< TOKEN` / `#;> TOKEN`) are for **partial** removal of content
from within a file that survives the install regardless of the choice. Use
them **only** when:

- A specific block (a few lines, a section, a function) needs to disappear
  conditionally while the rest of the file stays.
- The choice can flip independently of any other selection.

**Do not** wrap an entire file in fences if the installer removes the whole
file via `File::remove($t . '/path/to/file')` based on the same selection.
The file removal is the conditional behaviour - the fences are dead noise
and add visual clutter to the shipped file. Examples:

- `.github/workflows/assign-author.yml` is deleted whole by `AssignAuthorPr`
  on "no" - no fences inside the file.
- `.github/workflows/test-vr.yml` is deleted whole by `VisualRegression` on
  "no" - no fences inside the file.

If a fence-wrapped region would cover everything between the first and last
line of the file, delete the fences and rely on the handler's
`File::remove()` call instead.

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
| Theme    | `DRUPAL_THEME`, `STORYBOOK`                                                        |
| Services | `SERVICE_CLAMAV`, `SERVICE_SOLR`, `SERVICE_REDIS`                                  |
| CI       | `CI_PROVIDER_GHA`, `CI_PROVIDER_CIRCLECI`                                          |
| Hosting  | `HOSTING_LAGOON`, `HOSTING_ACQUIA`                                                 |
| Deploy   | `DEPLOY_TYPES_WEBHOOK`, `DEPLOY_TYPES_ARTIFACT`                                    |
| Database | `DB_FETCH_SOURCE_*`, `MIGRATION_DB_FETCH_SOURCE_*`, `DB_IN_IMAGE` (either database uses `container_registry`) |

### Handler Locations

`.vortex/installer/src/Prompts/Handlers/`:

- `CiProvider.php`, `HostingProvider.php`, `Services.php`, `Theme.php`

## Handler Development

### Ordering

`PromptManager::runPrompts()` is the catalogue of prompts: the `form()`
chain lists every prompt, in order, under its section heading. Add a new
prompt there, and the progress denominator follows automatically.

Processing runs in a different order, so each handler declares its own
`processWeight()` - lowest first, broadly the reverse of the prompt order so
that string replacements process more specific values before more generic
ones. It is declared on `HandlerInterface` with no default, so a new handler
does not compile until it states where it processes.

### Key Pattern

Handlers **queue** operations, PromptManager **executes**:

```php
// In handlers - queue only
File::replaceContentAsync('old', 'new');
File::replaceTokenAsync('TOKEN');

// In PromptManager::runProcessors() - execute all, after every handler has run
File::runDirectoryTasks($this->config->get(Config::TMP));
```

### Common Pitfalls

1. Don't call `File::runDirectoryTasks()` in handlers
2. Use `AlexSkrypnyk\File\Internal\ExtendedSplFileInfo`
3. Preserve complex logic in callbacks

## Test Organization

Each handler has dedicated test class extending
`AbstractHandlerProcessTestCase`:

```bash
./vendor/bin/phpunit --filter "HandlerNameInstallTest"
./vendor/bin/phpunit --filter "HandlerNameInstallTest.*scenario"
```

Structure: each `dataProvider*` sits immediately after the test it feeds;
helper methods go last.
