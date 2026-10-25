# Vortex Template Maintenance Guide

> **⚠️ MAINTENANCE MODE**: For **maintaining the Vortex template itself**.
> For **Drupal projects**, see `../CLAUDE.md`

## Project Structure

```text
vortex/
├── .vortex/                    # Test harness (removed on install)
│   ├── docs/                   # Documentation website
│   ├── cli/                    # Template CLI
│   ├── skills/                 # AI agent skills ('vortex' plugin)
│   ├── tests/                  # Template tests (PHPUnit)
│   └── tooling/                # 'drevops/vortex-tooling' Composer package
│       ├── src/                # Shipped PHP scripts
│       ├── tests/              # PHPUnit unit tests
│       └── playground/         # Manual integration scripts
└── [root files]                # The actual Drupal template
```

**Key principle**: outside `.vortex/` = template for users. Inside
`.vortex/` = test harness.

## Subsystems

| System       | Technology               | Purpose                       |
|--------------|--------------------------|-------------------------------|
| `docs/`      | Docusaurus, Jest         | vortextemplate.com            |
| `cli/`       | Symfony Console, PHPUnit | Template customization        |
| `tests/`     | PHPUnit                  | Template integration testing  |
| `tooling/`   | PHP, PHPUnit             | 'drevops/vortex-tooling' pkg  |

Each subsystem has its own CLAUDE.md. Read it when working there:

- `.vortex/docs/CLAUDE.md` - Documentation system, videos
- `.vortex/cli/CLAUDE.md` - CLI, fixtures, tokens
- `.vortex/tests/CLAUDE.md` - PHPUnit integration tests

`tooling/` has no CLAUDE.md. The package is published to consumer
projects, so any notes shipped with it would leak to those sites. Its
guidance is the [Tooling package](#tooling-package) section.

## Tooling package

This monorepo is the **source of truth** for the tooling. Make every change
to the shipped scripts here in `.vortex/tooling/`, never in the downstream
repo. `.vortex/tooling/` is published automatically to the standalone
`drevops/vortex-tooling` repository. That repository is a read-only mirror,
tagged and released from *its own* repo, not from here.

Consumer projects install that Composer package and run the shipped
scripts from `vendor/drevops/vortex-tooling/src/`. `.gitattributes`
`export-ignore` strips `tests/` and `playground/` from the published
archive, so of the code only `src/` ships.

Renaming or editing a tooling script is a single-repo change made entirely
here: the source script plus every in-repo caller, doc, and test. There is
no second repo to edit and no release to wait on for local work. The root
`composer.json` path repository resolves `drevops/vortex-tooling` to the
in-tree copy (see Publishing), and `vendor/drevops/vortex-tooling` is a
symlink to `.vortex/tooling/`.

**Tests**:

- **Unit** - PHPUnit tests in `tooling/tests/` cover the shipped scripts
  with external commands mocked. Run them with `ahoy test-tooling` from
  `.vortex/`.
- **Integration** - the template's PHPUnit functional tests in
  `.vortex/tests/` run the scripts end-to-end.
- **Manual** - `tooling/playground/` holds PHP scripts that hit live
  services (Slack, JIRA, New Relic). Not automated.

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

Shared helpers live in `src/helpers.php`. A consumer project that needs to
alter a shipped script does so with a `cweagans/composer-patches` patch
(see the tooling `README.md`), never by overriding it at runtime.
Per-project provisioning logic is a `DeployStep` plugin, not a script.

**Publishing**: the version is injected at publish time, so never hardcode
`version` in the package `composer.json`. While `2.0` is unreleased, the
template's root `composer.json` requires
`"drevops/vortex-tooling": "^2.0@alpha"`. The path repository pins
`"versions": {"drevops/vortex-tooling": "2.0.0-alpha1"}` so the in-repo
copy resolves during development.

The CLI install command strips that path repository from consumer sites.
Until a `2.0` pre-release is published to Packagist, scaffolded sites
can't resolve the tooling. That's acceptable during 2.x pre-release
development. After a `2.0` release is published, switch the constraint to
a plain `^2.0`.

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

`ahoy update-snapshots` (run from `.vortex/`) is the **only** way to
regenerate fixtures. It runs the `tests/` and `cli/` snapshot updates
together, with the required `XDEBUG_MODE=off` and parallel jobs. Never call
`composer update-snapshots` directly and never set `UPDATE_SNAPSHOTS` by
hand. Both bypass part of the workflow and produce partial, inconsistent
fixtures.

**HARD RULES** - every snapshot run, no exceptions:

- **Commit first.** Snapshots regenerate from the *committed* baseline, so
  an uncommitted template change (root or `.vortex/`) isn't picked up.
- **Foreground only.** It regenerates ~170 scenarios and auto-commits as
  it runs. Never background it (`run_in_background`, trailing `&`). A
  background run fires async commits, can leave a partial state, and hides
  failures.
- **No wrappers.** Never wrap it in a helper script, heredoc, or temp file
  to dodge a `cd` or one-command-per-call constraint. If `cd` doesn't
  persist, use the pointer flag:
  `ahoy --file .vortex/.ahoy.yml update-snapshots`.

## Cross-System Workflow

When updating template scripts:

1. Modify the script in `.vortex/tooling/src/` (shipped PHP) or `scripts/`
   (Bash).
2. Run `ahoy lint-tooling` (PHP tooling) or `ahoy lint-scripts` (Bash
   scripts).
3. Run `ahoy update-docs`.
4. Update PHPUnit tests in `.vortex/tooling/tests/`.
5. Run `ahoy test-tooling`.
6. **Commit.**
7. Run `ahoy update-snapshots` and commit the regenerated fixtures.

When updating template files (settings, configs, Dockerfiles, etc.):

1. Make the change.
2. **Commit.**
3. Run `ahoy update-snapshots` and commit the regenerated fixtures.

When anything the form puts on screen changes, also run
`ahoy update-videos cli-install`. The video records the live prompt flow,
so these changes make it stale:

- A new or removed handler, or a changed label, hint, option list, or
  validation under `.vortex/cli/src/Prompts/`.
- A reordered or reworded prompt or panel in
  `.vortex/cli/src/Form/VortexForm.php`.
- A widget or layout change in `.vortex/cli/src/Form/TuiAdapter.php` or
  `.vortex/cli/src/Prompts/PromptType.php`.

`update-snapshots` commits automatically; `update-videos` doesn't, so stage
and commit its output manually. Run both after the code change is
committed.

## Documentation videos

9 terminal demo videos live in `.vortex/docs/static/img/`
(`cli-install.*`, `build.*`, `provision.*`, `lint.*`, `test.*`,
`test-bdd.*`, `info.*`, `doctor.*`, `doctor-info.*`). Regenerate them from
`.vortex/` with `ahoy update-videos [names]`. A video goes stale when the
command it records changes behavior:

- `cli-install` - any prompt flow change.
- `build`, `provision` - changes to `.ahoy.yml` build/provision targets or
  `.vortex/tooling/src/vortex-provision*`.
- `lint`, `test`, `test-bdd` - changes to the linter or test-runner setup.
- `info`, `doctor`, `doctor-info` - changes to the reported environment.

A published video claims that the command it records runs clean. Each
recording is decoded to plain text at `.artifacts/videos/<name>.txt`, and
it isn't rendered when that text reports an error, a warning, or a
failure. There is no override flag: fix the output at the command that
emits it and record again.

`update-videos` doesn't commit. Review the diff under
`.vortex/docs/static/img/` and commit manually. See
`.vortex/docs/CLAUDE.md` for the pipeline internals (orchestrator,
workspace, `--keep` iteration, the `VIDEOS` config array).

## Environment Variables

| Variable              | Purpose               |
|-----------------------|-----------------------|
| `VORTEX_DEBUG=1`      | Debug mode in scripts |
| `TEST_VORTEX_DEBUG=1` | Debug output in tests |

## AI Assistant Guidelines

- **NEVER** modify `.vortex/cli/tests/Fixtures/` directly. Change the root
  template files, then run `ahoy update-snapshots`.
- American English spelling in documentation; sentence case for headings
  (capitalize proper nouns only).
- **Code**: single lines preferred, no character limit. Multi-line only for
  many parameters, arrays, chained methods, or complex conditionals.
- **Comments**: wrap at 80-120 characters.
- **Markdown**: wrap `.md` and `.mdx` files at 76 characters. markdownlint
  (`MD013`) enforces it in `ahoy lint-markdown` and `ahoy lint-docs`.
