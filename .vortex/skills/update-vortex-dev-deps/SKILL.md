---
name: update-vortex-dev-deps
description: Use when refreshing Composer and Yarn dev dependencies across the three '.vortex/' subsystems (docs, cli, tests). Runs in-range lock-file refreshes, lints each updated subsystem locally (with auto-fix where the subsystem supports it), produces a 'majors-available' report, makes a single bulk commit covering both lock and lint-fix fallout, and opens a PR. Triggers on phrases like 'update vortex dev deps', 'refresh .vortex dependencies', 'bump .vortex lock files', '/vortex:update-vortex-dev-deps'.
user-invocable: true
---

# Update Vortex Dev Dependencies

Refresh Composer and Yarn dependencies across 3 `.vortex/` subsystems, run
each subsystem's local lint, generate a major-versions-available report,
and open a pull request. Local **lint must pass before pushing**. Local
**tests are intentionally skipped**: CI runs them and is the source of
truth.

## When to Use

- The user says 'update vortex dev deps', 'refresh .vortex dependencies',
  or 'bump .vortex lock files'.
- A periodic maintenance pass on the Vortex template's test harness.

Do NOT use for:

- The root project's dependencies. Renovate and `prepare-vortex-release`
  handle those.
- Major version bumps inside `.vortex/` manifests. This skill only
  refreshes lock files within existing constraints. The majors report
  flags what would need a manual bump.
- `.vortex/tooling/`. That directory ships as the standalone
  `drevops/vortex-tooling` Composer library. It doesn't commit a
  `composer.lock`, and consumer projects resolve its dependencies from
  Packagist. Touching it here would produce local-only artifacts that
  should never land in a PR.

## Scope

This skill touches files under exactly 3 `.vortex/` subsystems:

- `.vortex/docs/` (Yarn)
- `.vortex/cli/` (Composer, plus the `vendor-bin/box/` sub-composer)
- `.vortex/tests/` (Composer + Yarn)

`.vortex/tooling/` is explicitly out of scope; see the "Do NOT use for"
list in [When to Use](#when-to-use).

Don't edit manifests (`composer.json`, `package.json`) by hand. Only lock
files (`composer.lock`, `yarn.lock`, `patches.lock.json`) change. A
`composer.json` changes only if it has `bump-after-update` configured. In
that case, stage and commit it with its lock file.

## Prerequisites

- Composer 2.x on the host.
- Yarn 1.x on the host.
- `gh` CLI authenticated.
- A clean working tree.

## Naming Convention

Branch and artifact names use a slug built from the current month and
year. Compute it once:

```bash
date +"%B-%Y"
```

Lowercase the result, for example `May-2026` becomes `may-2026`.

- Branch: `feature/vortex-dev-deps-{slug}`
- Artifact directory: `.artifacts/vortex-dev-deps-{slug}/`
- Update log: `.artifacts/vortex-dev-deps-{slug}/update-log.txt`
- Majors report: `.artifacts/vortex-dev-deps-{slug}/majors.md`
- PR description: `.artifacts/vortex-dev-deps-{slug}/pr-body.md`

## Subsystem Matrix

Process the subsystems in a fixed order, smallest blast radius first. Run
the lint and lint-fix commands from the project root.

| # | Subsystem (`.vortex/...`) | Composer manifest(s)                              | Yarn manifest    | `patches.lock.json` | Lint commands                                                                                                            | Lint-fix command                                          |
|---|---------------------------|---------------------------------------------------|------------------|---------------------|--------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------|
| 1 | `docs`                    | -                                                 | `package.json`   | -                   | `yarn --cwd .vortex/docs run lint` + `yarn --cwd .vortex/docs run spellcheck` + `yarn --cwd .vortex/docs run lint-prose` | `yarn --cwd .vortex/docs run lint-fix`                    |
| 2 | `cli`                     | `composer.json` + `vendor-bin/box/composer.json`  | -                | -                   | `composer --working-dir .vortex/cli lint`                                                                                | `composer --working-dir .vortex/cli lint-fix`             |
| 3 | `tests`                   | `composer.json`                                   | `package.json`   | yes                 | `composer --working-dir .vortex/tests lint`                                                                              | `composer --working-dir .vortex/tests lint-fix`           |

`.vortex/cli` doesn't require `cweagans/composer-patches`, so it has no
`patches.lock.json`.

Confirm the matrix against `.vortex/.ahoy.yml` before relying on it. Read
every command in the `lint-cli`, `lint-tests`, and `lint-docs` targets
and their `-fix` counterparts, not only the target names. If a target
gains, loses, or renames a command, this matrix is stale: run what the
target runs.

## Workflow

### Step 1: Prepare

Check that the working tree is clean:

```bash
git status
```

If uncommitted changes exist, STOP and ask the user before proceeding.

Determine the slug, for example `may-2026`.

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

Process the matrix in order. For each row, run the applicable commands
from the project root. Append every command's full output to
`.artifacts/vortex-dev-deps-{slug}/update-log.txt`, with a clear
`### {subsystem}` heading per subsystem.

`composer update` (no package args, no `--with-all-dependencies`) and
`yarn upgrade` (no `--latest`) both honor existing version constraints.
Manifests don't change unless `composer.json` has `bump-after-update`.

**3.1 - docs (Yarn only):**

```bash
yarn --cwd .vortex/docs upgrade
```

**3.2 - cli (Composer, plus sub-composer):**

```bash
composer --working-dir .vortex/cli update
```

```bash
composer --working-dir .vortex/cli/vendor-bin/box update
```

**3.3 - tests (Composer + Yarn):**

```bash
composer --working-dir .vortex/tests update
```

```bash
yarn --cwd .vortex/tests upgrade
```

**On command failure**, such as a dependency resolution conflict or a
registry error: STOP. Report which subsystem and which command failed,
with the relevant error excerpt. Leave the working tree as-is so the user
can investigate. Do NOT proceed to the next subsystem, and do NOT delete
the partial diff.

### Step 4: Generate majors-available report

For each Composer manifest in the matrix, including the `vendor-bin/box`
one:

```bash
composer --working-dir <subsystem> outdated --direct --major-only --format=json
```

For each Yarn manifest in the matrix:

```bash
yarn --cwd <subsystem> outdated --json
```

`yarn outdated` exits with status `1` when it finds outdated packages.
That's expected output, not a failure.

Parse the outputs and write `.artifacts/vortex-dev-deps-{slug}/majors.md`.
Add 1 table per subsystem-manager pair that has a major upgrade available.
Include only packages whose latest **major** is strictly greater than the
installed major. Composer's `--major-only` already filters this; for Yarn,
compare the `current` and `latest` semver majors. If a pair has nothing,
omit its table.

Use this format:

```markdown
# Major versions available

## `.vortex/docs` (Yarn)

| Package | Installed | Latest |
|---------|-----------|--------|
| @docusaurus/core | 3.4.0 | 4.0.1 |

## `.vortex/cli` (Composer)

| Package | Installed | Latest |
|---------|-----------|--------|
| symfony/console | 6.4.0 | 7.0.0 |
```

If no subsystem has a major available, write a single line:

```text
No major versions available outside current constraints.
```

### Step 5: Lint each updated subsystem (with auto-fix)

Lint MUST pass locally before committing or pushing. Tests do NOT run
locally; CI is the source of truth for tests.

Run the lint commands from the matrix in the same order as Step 3, from
the project root.

**5.1 - docs:**

```bash
yarn --cwd .vortex/docs run lint
```

```bash
yarn --cwd .vortex/docs run spellcheck
```

```bash
yarn --cwd .vortex/docs run lint-prose
```

**5.2 - cli:**

```bash
composer --working-dir .vortex/cli lint
```

**5.3 - tests:**

```bash
composer --working-dir .vortex/tests lint
```

#### On lint failure

A dependency refresh often surfaces lint violations. A code-style tool
(phpcs/Drupal coder, eslint, prettier, rector) was upgraded, and a rule
got stricter or a new rule was added. The first response is **always** to
run the subsystem's auto-fix.

For the failing subsystem, run its lint-fix command from the matrix:

- **docs**: `yarn --cwd .vortex/docs run lint-fix`
- **cli**: `composer --working-dir .vortex/cli lint-fix`
- **tests**: `composer --working-dir .vortex/tests lint-fix`

Then re-run that subsystem's lint commands from the matrix (Step 5.1,
5.2, or 5.3). The outcome is 1 of these:

1. **Lint passes.** Carry the auto-fixed source changes into the same
   commit as the dependency refresh in Step 6. They're part of what this
   update did, and reviewers should see them with the lock changes.
2. **Lint still fails.** STOP. Report which subsystem and which rules
   still fail, with the relevant excerpt. Do NOT commit, push, or open a
   PR. Leave the working tree as-is so the user can investigate. The
   remaining failures are real issues. They need a manual fix, or an
   explicit decision to suppress, pin, or revert the upgrade.

If the lint failure looks unrelated to the dependency refresh (the
upgraded packages have nothing to do with linting), tell the user before
continuing. Unrelated lint debt doesn't belong in the dependency refresh
PR.

### Step 6: Commit

Stage the dependency files first. Never `git add .` or `git add -A`.

```bash
git add .vortex/docs/yarn.lock
```

```bash
git add .vortex/cli/composer.json
```

```bash
git add .vortex/cli/composer.lock
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

`.vortex/cli/vendor-bin/` is gitignored as a whole, so the box
`composer.json` and `composer.lock` that Step 3.2 refreshes stay local.
Don't try to force-add either.

#### Stage auto-fix changes (if any)

If Step 5 ran a lint-fix that modified files, those changes also belong in
this commit. They're the source-code fallout of the dependency refresh,
and they go with the lock changes. Find the modified files inside the
affected subsystems and stage them explicitly. For example:

```bash
git diff --name-only .vortex/docs/src .vortex/docs/tests
```

```bash
git diff --name-only .vortex/cli/src .vortex/cli/tests
```

```bash
git diff --name-only .vortex/tests
```

Stage each path the diff prints, with 1 `git add <path>` per file. Don't
glob `.` or use `-A`. Restrict the search to the in-scope subsystems
(`.vortex/docs/`, `.vortex/cli/`, `.vortex/tests/`). Anything under
`.vortex/tooling/` MUST stay unstaged.

#### Verify staging

Run `git status` to confirm that only `.vortex/**` paths are staged.
Staging an unchanged file does nothing. If anything under
`.vortex/tooling/` appears in `git status`, such as a `composer.lock` that
Composer wrote locally, do NOT stage it. That subsystem is out of scope.

Commit, with the slug in title case, for example `May 2026`:

```bash
git commit -m "Refreshed '.vortex/' dev dependencies for {Slug Title Case}."
```

If Step 5 needed auto-fix changes, mention them in the commit body or
extend the subject. Reviewers then know the source files in the diff are
intended:

```bash
git commit -m "Refreshed '.vortex/' dev dependencies and lint-fix fallout for {Slug Title Case}."
```

### Step 7: Open PR

Write the PR description to `.artifacts/vortex-dev-deps-{slug}/pr-body.md`.
It must include:

1. **Scope statement** - "Refreshes lock files under `.vortex/` for
   `docs` / `cli` / `tests`. `.vortex/tooling/` is out of scope." Follow
   it with 1 sentence on manifests, read from `git diff --stat`: "The
   only manifest edits are the `composer.json` constraint bumps that
   `bump-after-update` writes to match the new locks." when a
   `composer.json` changed, or "No manifest changes." when none did.
2. **Subsystems touched** - a bullet list of the 3 subsystems, with a
   yes/no marker for Composer and Yarn changes, read from
   `git diff --stat`.
3. **Lint status** - 1 sentence confirming that local lint passed for each
   updated subsystem. This is the gate for opening the PR at all. If Step
   5 needed auto-fix, briefly list which subsystems the lint-fix touched
   and roughly what it fixed, for example "phpcs array-indentation in cli,
   applied by `composer lint-fix`". Reviewers then see that the
   source-code changes are deliberate.
4. **Majors report** - paste the report from Step 4 inline: the body of
   `majors.md` or the "No major versions available" line. Reviewers then
   see what's available outside the constraints next to the diff. Do NOT
   reference the `.artifacts/` path; those files aren't staged and won't
   exist in the PR branch.

The full Composer and Yarn output stays in
`.artifacts/vortex-dev-deps-{slug}/update-log.txt` for local debugging
only. Don't reference this path in the PR description, and don't paste
the log into the PR body.

Invoke `/open-pr` with `pr-body.md` as the body, so it uses the file as
written instead of generating a description:

```text
/open-pr body=.artifacts/vortex-dev-deps-{slug}/pr-body.md
```

`/open-pr`'s verify step lints and unit-tests the root project, which
this skill never touches, and this skill runs no local tests. Skip it:
pass `--skip-verify` to `pr.php create` and state the bypass in the
report. The Step 5 lint is this skill's gate.

## Red Flags

Stop when you're about to do any of these:

- Run `composer update` or `yarn upgrade` at the project root. The root
  project is out of scope for this skill.
- Run `composer update` or `yarn upgrade` under `.vortex/tooling/`. The
  tooling subsystem is its own Composer library and is explicitly out of
  scope.
- Pass `--with-all-dependencies`, `--latest`, a package name, or any other
  flag that changes constraint behavior. This skill does in-range
  refreshes only.
- Edit a `composer.json` or `package.json` constraint to "fix" a
  resolution failure. Report the failure to the user.
- Run `composer test`, `ahoy test`, `phpunit`, `jest`, `/open-pr`'s
  verify step, or any local test runner. Tests run in CI, not in this
  skill.
- Commit, push, or open a PR while a Step 5 lint command fails. Lint is
  the local gate; fix or escalate first.
- Escalate a lint failure to the user without first running the
  subsystem's `lint-fix` command. Auto-fix is the first response to every
  Step 5 failure.
- Split lint-fix changes into a separate commit from the dependency
  refresh. They belong in the same commit, so reviewers see the full
  causal chain.
- Run `git add .` or `git add -A`. Stage specific files only.
- Stage a `composer.lock` under `.vortex/tooling/` because Composer just
  wrote it. That file is local-only.
- Delete the partial diff after a failed update so the next subsystem can
  run. On failure, leave the diff for investigation.

Don't skip the majors report because "nothing changed". Write the report,
or the "no majors available" line; reviewers expect it.

## Command Rules - CRITICAL

NEVER use compound or composite commands. Every Bash tool call must
contain exactly ONE simple command.

**NEVER use:** `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, heredocs.

**ALWAYS:** Make multiple separate Bash tool calls, 1 command per call.
