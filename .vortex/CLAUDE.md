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
│       ├── src/                # Shipped scripts
│       ├── tests/              # BATS unit tests
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
| `tooling/`   | Bash, BATS               | 'drevops/vortex-tooling' pkg  |

Each subsystem has its own CLAUDE.md - read it when working there:

- `.vortex/docs/CLAUDE.md` - Documentation system, videos
- `.vortex/installer/CLAUDE.md` - Installer, fixtures, tokens
- `.vortex/tests/CLAUDE.md` - PHPUnit integration tests

`tooling/` has no CLAUDE.md - it is published to consumer projects, so any notes
shipped with it would leak to those sites. Its guidance is the [Tooling
package](#tooling-package) section below.

## Tooling package

`tooling/` is published as the standalone `drevops/vortex-tooling` Composer
package - a read-only mirror split from `.vortex/tooling/`. Consumer projects
install it and run the shipped scripts from `vendor/drevops/vortex-tooling/src/`.
`tests/` and `playground/` are stripped from the published archive via
`.gitattributes` `export-ignore`; only `src/` ships.

**Tests**:

- **Unit** - BATS tests in `tooling/tests/unit/` cover the shipped scripts with
  external commands mocked. Run with `ahoy test-bats` from `.vortex/`.
- **Integration** - the scripts are exercised end-to-end by the template's
  PHPUnit functional tests in `.vortex/tests/`.
- **Manual** - `tooling/playground/` holds scripts that hit live services
  (Slack, JIRA, New Relic). Not automated; see `tooling/playground/README.md`.

**Script pattern** (shipped tooling scripts and `scripts/` provision subscripts):

```bash
#!/usr/bin/env bash
# Environment loading
t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && set +a && . "${t}" && rm "${t}"

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Variables with defaults
VAR="${VAR:-default}"

# Helpers
info() { printf "[INFO] %s\n" "${1}"; }
task() { printf "    > %s\n" "${1}"; }
note() { printf "      %s\n" "${1}"; }

# Main execution
```

**Publishing**: the version is injected at publish time - never hardcode
`version` in the package `composer.json`. The path repository in the template's
root `composer.json` declares `"versions": {"drevops/vortex-tooling": "1.1.0"}`
so the in-repo copy resolves during development; the installer strips that entry
from consumer sites so they resolve from Packagist.

## Quick Commands

```bash
cd .vortex
ahoy install          # Install all dependencies
ahoy update-snapshots # Update fixtures (see Snapshots below)
ahoy lint-scripts     # Lint scripts
ahoy test-bats        # Run BATS unit tests
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

1. Modify the script in `.vortex/tooling/src/` (shipped) or `scripts/` (provision subscripts).
2. Run `ahoy lint-scripts`.
3. Run `ahoy update-docs`.
4. Update BATS tests in `.vortex/tooling/tests/unit/`.
5. Run `ahoy test-bats`.
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
