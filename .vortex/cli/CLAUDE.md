# CLI System Guide

## Overview

Symfony Console application that customizes the Vortex template based on user
selections.

**Technology**: PHP, Symfony Console, PHPUnit

## Commands

```bash
cd .vortex/cli

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

### Updating the install demo video

**Whenever the prompt flow changes** (new handler added, prompt
renamed, section reordered, prompt removed), the install demo video shown in
the documentation goes stale and must be regenerated.

```bash
# From .vortex/ directory
ahoy update-videos cli-install
```

Requires `asciinema`, `expect`, `php`, `composer`, `npx` on PATH. Produces
`cli-install.json` (asciicast), `cli-install.svg`, `cli-install.png`, and
`cli-install.gif` under `.vortex/docs/static/img/`. Requires explicit user
permission before running.

Triggers that require re-recording:
- New `Handlers/*.php` class or handler removal.
- Change to `label()`, `hint()`, `options()` or `validate()` of any existing
  handler.
- Reordering questions or panels inside `VortexForm::create()`.
- Widget or layout change in `Form/TuiAdapter.php` or `Prompts/PromptType.php`.

The expect script drives the panels by position (a fixed number of arrow keys
to reach a panel and the closing actions), so any change to the number or
order of panels needs the key counts in `build_install_expect_script()`
adjusted to match.

## Conditional Token System

### When to use fences

Fences (`#;< TOKEN` / `#;> TOKEN`) are for **partial** removal of content from
within a file that survives the install regardless of the choice. Use them
**only** when:

- A specific block (a few lines, a section, a function) needs to disappear
  conditionally while the rest of the file stays.
- The choice can flip independently of any other selection.

**Do not** wrap an entire file in fences if the install command removes the whole
file via `File::remove($t . '/path/to/file')` based on the same selection.
The file removal is the conditional behaviour - the fences are dead noise and
add visual clutter to the shipped file. Examples:

- `.github/workflows/assign-author.yml` is deleted whole by `AssignAuthorPr`
  on "no" - no fences inside the file.
- `.github/workflows/test-vr.yml` is deleted whole by `VisualRegression` on
  "no" - no fences inside the file.

If a fence-wrapped region would cover everything between the first and last
line of the file, delete the fences and rely on the handler's `File::remove()`
call instead.

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
| Services | `SERVICE_ANTIVIRUS`, `SERVICE_SEARCH`, `SERVICE_CACHE`                                  |
| CI       | `CI_PROVIDER_GHA`, `CI_PROVIDER_CIRCLECI`                                          |
| Hosting  | `HOSTING_LAGOON`, `HOSTING_ACQUIA`                                                 |
| Deploy   | `DEPLOY_TYPES_WEBHOOK`, `DEPLOY_TYPES_ARTIFACT`                                    |
| Database | `DB_FETCH_SOURCE_<SOURCE>` for the primary database, `DB2_FETCH_SOURCE_<SOURCE>` for the second (migration) database; combined: `DB_FETCH_ANY_SOURCE_LAGOON` (primary or migration source is Lagoon), `DB_FETCH_SOURCE_HOSTED` / `DB2_FETCH_SOURCE_HOSTED` (hosting-connected sources). Token names must never contain another removable token as a substring - marker matching is substring-based, so removing the shorter token would swallow the longer token's regions. |

### Handler Locations

`.vortex/cli/src/Prompts/Handlers/`:

- `CiProvider.php`, `HostingProvider.php`, `Services.php`, `Theme.php`

## Handler Development

### Key Pattern

Handlers **queue** operations, the processor **executes**:

```php
// In handlers - queue only
File::replaceContentAsync('old', 'new');
File::removeTokenAsync('TOKEN');

// In Processor - execute all
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

