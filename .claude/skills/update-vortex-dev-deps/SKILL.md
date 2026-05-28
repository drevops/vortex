---
name: update-vortex-dev-deps
description: Use when refreshing Composer and Yarn dev dependencies across the four '.vortex/' subsystems (docs, installer, tests, tooling). Runs in-range lock-file refreshes, produces a 'majors-available' report, makes a single bulk commit, and opens a PR. Triggers on phrases like 'update vortex dev deps', 'refresh .vortex dependencies', 'bump .vortex lock files', '/update-vortex-dev-deps'.
user-invocable: true
---

# Update Vortex Dev Dependencies

Refresh Composer and Yarn dependencies across all four `.vortex/` subsystems, generate a major-versions-available report, and open a pull request. Local validation is intentionally skipped - CI is the source of truth.

## When to Use

- User says 'update vortex dev deps', 'refresh .vortex dependencies', 'bump .vortex lock files'.
- A periodic maintenance pass on the Vortex template's test harness.

Do NOT use for:

- The root project's dependencies (those are handled by Renovate and `prepare-vortex-release`).
- Major version bumps inside `.vortex/` manifests. This skill only refreshes lock files within existing constraints; the majors report flags what would need a manual bump.

## Scope

This skill ONLY touches files under `.vortex/`:

- `.vortex/docs/` (Yarn)
- `.vortex/installer/` (Composer, plus `vendor-bin/box/` sub-composer)
- `.vortex/tests/` (Composer + Yarn)
- `.vortex/tooling/` (Composer + Yarn)

Manifests (`composer.json`, `package.json`) are not edited by hand. Only lock files (`composer.lock`, `yarn.lock`, `patches.lock.json`) change. A `composer.json` will only change if it has `bump-after-update` configured; in that case stage and commit it along with its lock file.

## Prerequisites

- Composer 2.x available on the host.
- Yarn 1.x available on the host.
- `gh` CLI authenticated.
- Working tree clean.

## Naming Convention

Branch and artifact names use a slug derived from the current month and year. Compute it once:

```bash
date +"%B-%Y"
```

Lowercase the result (e.g. `May-2026` becomes `may-2026`).

- Branch: `feature/vortex-dev-deps-{slug}`
- Artifact directory: `.artifacts/vortex-dev-deps-{slug}/`
- Update log: `.artifacts/vortex-dev-deps-{slug}/update-log.txt`
- Majors report: `.artifacts/vortex-dev-deps-{slug}/majors.md`

## Subsystem Matrix

Process in fixed order. Smallest blast radius first.

| # | Subsystem (`.vortex/...`) | Composer manifest(s)                              | Yarn manifest    | `patches.lock.json` |
|---|---------------------------|---------------------------------------------------|------------------|---------------------|
| 1 | `docs`                    | -                                                 | `package.json`   | -                   |
| 2 | `installer`               | `composer.json` + `vendor-bin/box/composer.json`  | -                | yes                 |
| 3 | `tests`                   | `composer.json`                                   | `package.json`   | yes                 |
| 4 | `tooling`                 | `composer.json`                                   | `package.json`   | -                   |

## Workflow

### Step 1: Prepare

Check the working tree is clean:

```bash
git status
```

If uncommitted changes exist, STOP and ask the user before proceeding.

Determine the slug (e.g. `may-2026`).

Create the artifact directory:

```bash
mkdir -p .artifacts/vortex-dev-deps-{slug}
```

### Step 2: Create feature branch

Detect the default branch:

```bash
gh repo view --json defaultBranchRef --jq '.defaultBranchRef.name'
```

Fall back to `main` if `gh` fails.

```bash
git checkout {default-branch}
```

```bash
git pull origin {default-branch}
```

```bash
git checkout -b feature/vortex-dev-deps-{slug}
```

### Step 3: Refresh dependencies

Process the matrix in order. For each row, run the applicable commands below from the project root. Append every command's full output to `.artifacts/vortex-dev-deps-{slug}/update-log.txt` with a clear `### {subsystem}` heading per subsystem.

`composer update` (no package args, no `--with-all-dependencies`) and `yarn upgrade` (no `--latest`) both honour existing version constraints. Manifests should not change unless `composer.json` has `bump-after-update`.

**3.1 - docs (Yarn only):**

```bash
yarn --cwd .vortex/docs upgrade
```

**3.2 - installer (Composer, plus sub-composer):**

```bash
composer --working-dir .vortex/installer update
```

```bash
composer --working-dir .vortex/installer/vendor-bin/box update
```

**3.3 - tests (Composer + Yarn):**

```bash
composer --working-dir .vortex/tests update
```

```bash
yarn --cwd .vortex/tests upgrade
```

**3.4 - tooling (Composer + Yarn):**

```bash
composer --working-dir .vortex/tooling update
```

```bash
yarn --cwd .vortex/tooling upgrade
```

**On command failure** (dependency resolution conflict, registry error, etc.): STOP, report which subsystem and which command failed and the relevant error excerpt, leave the working tree as-is so the user can investigate. Do NOT proceed to the next subsystem and do NOT delete the partial diff.

### Step 4: Generate majors-available report

For each Composer manifest in the matrix (including the `vendor-bin/box` one):

```bash
composer --working-dir <subsystem> outdated --direct --major-only --format=json
```

For each Yarn manifest in the matrix:

```bash
yarn --cwd <subsystem> outdated --json
```

`yarn outdated` exits with status `1` when outdated packages are found. That is expected output, not a failure.

Parse the outputs and write `.artifacts/vortex-dev-deps-{slug}/majors.md` with one table per subsystem-manager pair that has any major upgrade available. Include only packages whose latest **major** is strictly greater than the installed major (Composer's `--major-only` already filters this; for Yarn, compare `current` and `latest` semver majors). If a pair has nothing, omit its table.

Format:

```markdown
# Major versions available

## `.vortex/docs` (Yarn)

| Package | Installed | Latest |
|---------|-----------|--------|
| @docusaurus/core | 3.4.0 | 4.0.1 |

## `.vortex/installer` (Composer)

| Package | Installed | Latest |
|---------|-----------|--------|
| symfony/console | 6.4.0 | 7.0.0 |
```

If no subsystem has any major available, write a single line:

```text
No major versions available outside current constraints.
```

### Step 5: Commit

Stage only the dependency files. Never `git add .` or `git add -A`.

```bash
git add .vortex/docs/yarn.lock
```

```bash
git add .vortex/installer/composer.json
```

```bash
git add .vortex/installer/composer.lock
```

```bash
git add .vortex/installer/patches.lock.json
```

```bash
git add .vortex/installer/vendor-bin/box/composer.json
```

```bash
git add .vortex/installer/vendor-bin/box/composer.lock
```

```bash
git add .vortex/tests/composer.json
```

```bash
git add .vortex/tests/composer.lock
```

```bash
git add .vortex/tests/patches.lock.json
```

```bash
git add .vortex/tests/yarn.lock
```

```bash
git add .vortex/tooling/composer.json
```

```bash
git add .vortex/tooling/composer.lock
```

```bash
git add .vortex/tooling/yarn.lock
```

Run `git status` to confirm only `.vortex/**` paths are staged. Staging an unchanged file is a no-op.

Commit (with the slug title-cased, e.g. `May 2026`):

```bash
git commit -m "Refreshed '.vortex/' dev dependencies for {Slug Title Case}."
```

### Step 6: Open PR

Invoke the `/open-pr` skill. The PR description must include:

1. **Scope statement** - one sentence: "Refreshes lock files under `.vortex/` for `docs` / `installer` / `tests` / `tooling`. No manifest constraint changes."
2. **Subsystems touched** - bullet list of the four subsystems with a yes/no marker for Composer and Yarn changes (read from `git diff --stat`).
3. **Majors report** - paste the contents of `.artifacts/vortex-dev-deps-{slug}/majors.md` inline so reviewers see what is available outside the constraints in the same place as the diff.
4. **Update log reference** - point to `.artifacts/vortex-dev-deps-{slug}/update-log.txt` for the full Composer / Yarn output; do NOT paste the full log into the PR body.

`/open-pr` handles the push, PR creation, CI watching, and review comment remediation. Local lint and tests are intentionally skipped because CI runs them already and is the source of truth.

## Red Flags

- About to run `composer update` or `yarn upgrade` at the project root: stop. The root project is out of scope for this skill.
- About to pass `--with-all-dependencies`, `--latest`, a package name, or any other flag that changes constraint behaviour: stop. This skill performs in-range refreshes only.
- About to edit a `composer.json` or `package.json` constraint to "fix" a resolution failure: stop. Surface the failure to the user.
- About to run `ahoy lint`, `ahoy test`, `composer test`, or any local validation: stop. CI is the source of truth.
- About to `git add .` or `git add -A`: stop. Stage specific files only.
- About to skip the majors report because "nothing changed": still write the report (or the "no majors available" line). Reviewers expect it.
- About to delete the partial diff after a failed update so the next subsystem can run: stop. On failure, leave the diff for investigation.

## Command Rules - CRITICAL

NEVER use compound or composite commands. Every Bash tool call must contain exactly ONE simple command.

**NEVER use:** `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, heredocs.

**ALWAYS:** Make multiple separate Bash tool calls, one command per call.
