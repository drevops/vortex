# Vortex Template Maintenance Guide

> **⚠️ MAINTENANCE MODE**: For **maintaining the Vortex template itself**.
> For **Drupal projects**, see `../CLAUDE.md`

## Project Structure

```text
vortex/
├── .vortex/                    # Test harness (removed on install)
│   ├── docs/                   # Documentation website
│   ├── installer/              # Template installer
│   ├── tests/                  # Template tests (PHPUnit)
│   └── tooling/                # 'drevops/vortex-tooling' Composer package
│       ├── src/                # Shipped PHP scripts
│       ├── tests/              # PHPUnit unit tests
│       └── playground/         # Manual integration scripts
└── [root files]                # The actual Drupal template
```

**Key principle**: outside `.vortex/` = template for users. Inside `.vortex/` =
test harness.

## Subsystems

| System       | Technology               | Purpose                       |
|--------------|--------------------------|-------------------------------|
| `docs/`      | Docusaurus, Jest         | vortextemplate.com            |
| `installer/` | Symfony Console, PHPUnit | Template customization        |
| `tests/`     | PHPUnit                  | Template integration testing  |
| `tooling/`   | PHP, PHPUnit             | 'drevops/vortex-tooling' pkg  |

Each subsystem has its own CLAUDE.md - read it when working there:

- `.vortex/docs/CLAUDE.md` - Documentation system, videos
- `.vortex/installer/CLAUDE.md` - Installer, fixtures, tokens
- `.vortex/tests/CLAUDE.md` - PHPUnit integration tests

`tooling/` has no CLAUDE.md - it is published to consumer projects, so any notes
shipped with it would leak to those sites. Its guidance is the [Tooling
package](#tooling-package) section below.

## Tooling package

This monorepo is the **source of truth** for the tooling: make every change to
the shipped scripts here in `.vortex/tooling/`, never in the downstream repo.
`.vortex/tooling/` is published automatically to the standalone
`drevops/vortex-tooling` repository - a read-only mirror that is tagged and
released from *its own* repo, not from here. Consumer projects install that
Composer package and run the shipped scripts from
`vendor/drevops/vortex-tooling/src/`. `tests/` and `playground/` are stripped
from the published archive via `.gitattributes` `export-ignore`; only `src/`
ships.

Renaming or editing a tooling script is therefore a single-repo change made
entirely here - the source script plus every in-repo caller, doc, and test.
There is no second repo to edit and no release to wait on for local work: the
root `composer.json` path repository resolves `drevops/vortex-tooling` to the
in-tree copy (see Publishing below), and `vendor/drevops/vortex-tooling` is a
symlink to `.vortex/tooling/`.

**Tests**:

- **Unit** - PHPUnit tests in `tooling/tests/` cover the shipped scripts with
  external commands mocked. Run with `ahoy test-tooling` from `.vortex/`.
- **Integration** - the scripts are exercised end-to-end by the template's
  PHPUnit functional tests in `.vortex/tests/`.
- **Manual** - `tooling/playground/` holds PHP scripts that hit live services
  (Slack, JIRA, New Relic). Not automated.

**Tooling script pattern** (shipped PHP scripts):

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/helpers.php';

// Variables via getenv_default()/getenv_required() (with fallback chains).
$var = getenv_default('VAR', 'default');

// Output and utilities come from the shared helpers: info(), task(), note(),
// pass(), fail(), request_get()/request_post(), command_must_exist(), etc.
```

Shared helpers live in `src/helpers.php`. Consumer projects that need to alter a
shipped script do so with a `cweagans/composer-patches` patch (see the tooling
`README.md`), never by overriding it at runtime. The `scripts/` provision
subscripts remain Bash.

**Publishing**: the version is injected at publish time - never hardcode
`version` in the package `composer.json`. While `2.0` is unreleased, the
template's root `composer.json` requires
`"drevops/vortex-tooling": "^2.0@alpha"` and the path repository pins
`"versions": {"drevops/vortex-tooling": "2.0.0-alpha1"}` so the in-repo copy
resolves during development. The installer strips that path repository from
consumer sites; until a `2.0` pre-release is published to Packagist, scaffolded
sites cannot resolve the tooling - acceptable during 2.x pre-release
development. Once a `2.0` release is published, switch the constraint to a plain
`^2.0`.

## Quick Commands

```bash
cd .vortex
ahoy install          # Install all dependencies
ahoy update-snapshots # Update fixtures (see Snapshots below)
ahoy lint-scripts     # Lint Bash scripts
ahoy lint-tooling     # Lint the PHP tooling package
ahoy test-tooling     # Run PHPUnit tests for the tooling package
ahoy update-docs      # Regenerate docs variables from scripts
ahoy lint-markdown    # Lint markdown files
```

## Snapshots

`ahoy update-snapshots` (run from `.vortex/`) is the **only** way to regenerate
fixtures. It wraps the `tests/` and `installer/` snapshot runs together with the
required `XDEBUG_MODE=off` and parallel jobs. Never call
`composer update-snapshots` directly and never set `UPDATE_SNAPSHOTS` by hand -
both bypass part of the workflow and produce partial, inconsistent fixtures.

**HARD RULES** - every snapshot run, no exceptions:

- **Commit first.** Snapshots regenerate from the *committed* baseline; any
  uncommitted template change (root or `.vortex/`) is not picked up.
- **Foreground only.** It regenerates ~130 scenarios and auto-commits as it
  runs. Never background it (`run_in_background`, trailing `&`) - that fires
  async commits, can leave a partial state, and hides failures.
- **No wrappers.** Never wrap it in a helper script, heredoc, or temp file to
  dodge a `cd` or one-command-per-call constraint. If `cd` does not persist,
  use the pointer flag: `ahoy --file .vortex/.ahoy.yml update-snapshots`.

## Cross-System Workflow

When updating template scripts:

1. Modify the script in `.vortex/tooling/src/` (shipped PHP) or `scripts/` (Bash provision subscripts).
2. Run `ahoy lint-tooling` (PHP tooling) or `ahoy lint-scripts` (Bash scripts).
3. Run `ahoy update-docs`.
4. Update PHPUnit tests in `.vortex/tooling/tests/`.
5. Run `ahoy test-tooling`.
6. **Commit.**
7. Run `ahoy update-snapshots` and commit the regenerated fixtures.

When updating template files (settings, configs, Dockerfiles, etc.):

1. Make the change.
2. **Commit.**
3. Run `ahoy update-snapshots` and commit the regenerated fixtures.

When the installer prompt flow changes (any change under
`.vortex/installer/src/Prompts/` - new or removed handler, reordered or reworded
prompt, `TOTAL_RESPONSES` bump), also run `ahoy update-videos installer` to
re-record the demo, since the video records the live prompt flow.
`update-snapshots` commits automatically; `update-videos` does not - stage and
commit its output manually. Run both after the code change is committed.

## Documentation videos

Six terminal demo videos live in `.vortex/docs/static/img/` (`installer.*`,
`build.*`, `provision.*`, `lint.*`, `test.*`, `test-bdd.*`). Regenerate from
`.vortex/` with `ahoy update-videos [names]`. A video goes stale when the
command it records changes behavior:

- `installer` - any prompt flow change.
- `build`, `provision` - changes to `.ahoy.yml` build/provision targets or
  `scripts/vortex/provision*`.
- `lint`, `test`, `test-bdd` - changes to the linter or test-runner setup.

`update-videos` does not commit - review the diff under `.vortex/docs/static/img/`
and commit manually. See `.vortex/docs/CLAUDE.md` for the pipeline internals
(orchestrator, workspace, `--keep` iteration, the `VIDEOS` config array).

## Environment Variables

| Variable              | Purpose               |
|-----------------------|-----------------------|
| `VORTEX_DEBUG=1`      | Debug mode in scripts |
| `TEST_VORTEX_DEBUG=1` | Debug output in tests |

## AI Assistant Guidelines

- **NEVER** modify `.vortex/installer/tests/Fixtures/` directly - change the root
  template files, then run `ahoy update-snapshots`.
- American English spelling in documentation; sentence case for headings
  (capitalize proper nouns only).
- **Code**: single lines preferred, no character limit. Multi-line only for many
  parameters, arrays, chained methods, or complex conditionals.
- **Comments**: wrap at 80-120 characters.
