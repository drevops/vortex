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

**CRITICAL**: Never modify fixture files directly. Only modify the root
template files, then regenerate the fixtures.

See `.vortex/CLAUDE.md` for the snapshot update process (always
`ahoy update-snapshots` from `.vortex/`, never composer directly).

### Updating the Installer Video

**Whenever the installer prompt flow changes** (new handler added, prompt
renamed, section reordered, prompt removed), the installer video shown in the
documentation goes stale and must be regenerated.

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
- Reordering prompts inside `PromptManager::runPrompts()`.
- Change to `TOTAL_RESPONSES` constant.

## Conditional Token System

### When to use fences

Fences (`#;< TOKEN` / `#;> TOKEN`) are for **partial** removal of content from
within a file that survives the install regardless of the choice. Use them
**only** when:

- A specific block (a few lines, a section, a function) needs to disappear
  conditionally while the rest of the file stays.
- The choice can flip independently of any other selection.

**Do not** wrap an entire file in fences if the installer removes the whole
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
| Database | `DB_FETCH_SOURCE_<SOURCE>`, `MIGRATION_DB_FETCH_SOURCE_<SOURCE>` per source; combined: `DB_FETCH_SOURCE_LAGOON_ANY` (primary or migration source is Lagoon), `DB_FETCH_SOURCE_ACQUIA_LAGOON` / `MIGRATION_DB_FETCH_SOURCE_ACQUIA_LAGOON` (hosting-connected sources) |

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

## Patches

The installer applies a single patch to `laravel/prompts` via
`cweagans/composer-patches` v2 to add three behaviors it depends on.

### Patch location

- File: `patches/laravel-prompts.patch`
- Registered in: `composer.json` under `extra.patches`
- Lockfile: `patches.lock.json` (regenerated by composer-patches; do not hand-edit)

### What the patch adds

1. **`description` field on every Prompt class and renderer**. A new
   `RendersDescription` trait under
   `vendor/laravel/prompts/src/Themes/Default/Concerns/` renders a
   description block inside the prompt frame. `PromptManager::runPrompts()`
   threads `$handler->description($responses)` into `$args['description']`
   for every handler that returns a non-null description.
2. **`Prompt::validateUsing(?Closure $callback)` accepts null**, so
   `tests/Traits/TuiTrait.php::tuiTeardown()` can clear the validator
   between tests.
3. **The static `validateUsing` callback runs before the instance
   `validate` closure and receives `$value`**. `TuiTrait::tuiSetUp()` uses
   this to intercept every prompt's validation and throw `RuntimeException`
   on failure, so non-interactive tests fail fast instead of hanging
   waiting for user input.

The patch also includes a small `Concerns/TypedValue.php` tweak
(`strlen($default) > 0` → truthy check); not exercised directly by the
installer.

### Re-roll procedure

Whenever the `laravel/prompts` version in `composer.json` is bumped
(Renovate PR or manual update), the patch must be re-rolled because the
description trait touches renderers that frequently change between
releases.

```bash
# 1. After bumping the version in .vortex/installer/composer.json, try the
#    existing patch first.
composer --working-dir .vortex/installer update laravel/prompts --with-dependencies
rm -f ~/Library/Caches/composer/patches/*.patch  # macOS cache path
rm -f ~/.cache/composer/patches/*.patch          # Linux cache path
composer --working-dir .vortex/installer patches-relock
composer --working-dir .vortex/installer patches-repatch
```

If `patches-repatch` succeeds, jump to the verify step.

If it fails (`No available patcher was able to apply patch`), the patch
conflicts with the new upstream and needs editing:

```bash
# 2. Extract the new upstream source for editing.
git clone https://github.com/laravel/prompts.git /tmp/prompts-upstream
git -C /tmp/prompts-upstream checkout v<NEW_TAG>
cp -R /tmp/prompts-upstream/src /tmp/upstream-pristine-src

# 3. Apply the three changes to /tmp/prompts-upstream/src using
#    .vortex/installer/patches/laravel-prompts.patch as reference:
#    a) Re-introduce the RendersDescription trait and thread `description`
#       through every Prompt constructor + matching renderer.
#    b) Update Prompt::validateUsing() signature to `?Closure $callback`.
#    c) Swap the match arms in Prompt::validate() so the static validator
#       runs first and receives $value as the second argument.

# 4. Regenerate the unified diff against the pristine upstream copy.
diff -ruN /tmp/upstream-pristine-src /tmp/prompts-upstream/src > /tmp/raw.patch

# 5. Re-root paths to `a/src/...` / `b/src/...` and strip timestamps using
#    the committed helper.
php .vortex/installer/patches/reroot-patch.php \
  /tmp/raw.patch \
  .vortex/installer/patches/laravel-prompts.patch \
  /tmp/upstream-pristine-src/ \
  /tmp/prompts-upstream/src/

# 6. Refresh the lockfile and re-apply.
rm -f ~/Library/Caches/composer/patches/*.patch
rm -f ~/.cache/composer/patches/*.patch
composer --working-dir .vortex/installer patches-relock
composer --working-dir .vortex/installer patches-repatch
```

Verify:

```bash
composer --working-dir .vortex/installer test
composer --working-dir .vortex/installer lint
```
