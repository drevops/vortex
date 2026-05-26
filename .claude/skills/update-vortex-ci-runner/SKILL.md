---
name: update-vortex-ci-runner
description: Use when testing a canary build of 'drevops/ci-runner' against a Vortex project before official release, or when incrementing the pinned 'drevops/ci-runner' version after a new release. Triggers on phrases like 'test ci-runner canary', 'update ci-runner', 'switch ci-runner to canary', 'bump ci-runner to X.Y.Z'.
user-invocable: true
---

# Vortex Update CI Runner

Two-phase workflow for verifying a new `drevops/ci-runner` release on a Vortex-based Drupal project:

- **Phase 1 (Canary):** Switch all `drevops/ci-runner` image references to `:canary` (no SHA pin), open a PR, monitor CI. If green, STOP and notify the user so they can cut the official release.
- **Phase 2 (Release):** After the user confirms a release version, roll the same PR forward to pin `:NEW_VERSION@sha256:NEW_SHA`, push, monitor CI. On green, notify the user the PR is ready to merge.

## When to Use

- User says 'test ci-runner canary' / 'switch ci-runner to canary' / 'try the new ci-runner'.
- User says 'update ci-runner to X.Y.Z' (skip to Phase 2 if no canary needed).
- Vortex release prep needs a ci-runner verification.

Do NOT use for `uselagoon/*` images (Renovate handles those) or `ci-builder` (different image).

## Prerequisites

- Working in a Vortex-based project (look for `.vortex/` directory or `drevops/ci-runner` references).
- `gh` CLI authenticated.
- Docker available (only needed for the Phase 2 SHA lookup).
- Clean working tree.

## Phase 1: Canary

### 1. Discover references

The file list is not fixed: older or downstream projects may have it in more places. Always rediscover.

```bash
grep -rln "drevops/ci-runner" --include="*.yml" .
```

Repeat for `*.yaml` and `Dockerfile*`. **Exclude `*/tests/Fixtures/*` and `*/test/Fixtures/*` paths** since those are snapshots used by installer tests, not live CI configs.

Record the current version and SHA before changing anything: you need to know what you are rolling forward from in case of rollback.

### 2. Branch

```bash
git checkout -b feature/update-ci-runner
```

This branch is reused for Phase 2; do not create a new branch when the release version is provided.

### 3. Replace image references

For every non-fixture file from Step 1, replace:

```
drevops/ci-runner:<VERSION>@sha256:<HASH>
```

with:

```
drevops/ci-runner:canary
```

**Drop the SHA pin in canary mode.** Pinning a SHA defeats the canary: CI re-runs would test the same frozen build, not whatever the `canary` tag currently points to. The whole point of the canary phase is to track a moving target.

Use the `Edit` tool with `replace_all: true` per file. Verify with grep afterwards: the only remaining hits should be in fixture paths.

### 4. Commit and push

Stage specific files (never `git add .`):

```bash
git add .github/workflows/build-test-deploy.yml
```

```bash
git commit -m "Switched 'drevops/ci-runner' to 'canary' for verification."
```

```bash
git push -u origin feature/update-ci-runner
```

### 5. Open PR

Invoke the `/open-pr` skill. PR title:

```
Tested 'drevops/ci-runner' canary build.
```

PR body should note: this is a canary test (NOT for merge yet), the previous pinned version, files changed, and that the next step is for the user to release a new `drevops/ci-runner` once CI passes.

### 6. Monitor CI

Watch GitHub Actions via `gh pr checks <PR>` and CircleCI via the PR link.

**On failure:** Diagnose before reporting. Common causes:

- **Tool version drift** in canary (PHP, Node, Composer bumped in the ci-runner image but pinned in project configs: `composer.json` `config.platform.php`, `phpstan.neon` `phpVersion`, `phpcs.xml` `testVersion`).
- A tool removed from the canary image.
- Cache key issues (rare: cache keys are tied to Lagoon versions, not ci-runner).
- Image pull / registry timeout.

Report with: failing job name, failing step, ~30 lines of the most relevant log, and your hypothesis. Do not paste raw logs without a hypothesis.

**On success:** STOP. Do not merge. Tell the user:

> Canary verification passed: CI is green on `<PR URL>`. Ready for you to cut the new `drevops/ci-runner` release. Let me know the new version (e.g. `26.4.0`) when ready.

## Phase 2: Release Pin

Triggered when the user confirms a new released version (e.g. 'released as 26.4.0').

### 1. Get the SHA digest

```bash
docker pull drevops/ci-runner:<NEW_VERSION>
```

```bash
docker inspect --format="{{index .RepoDigests 0}}" drevops/ci-runner:<NEW_VERSION>
```

The output is `drevops/ci-runner@sha256:<HASH>`. Record `<HASH>`.

If docker is unavailable, use `docker manifest inspect drevops/ci-runner:<NEW_VERSION>` and read the linux/amd64 digest.

### 2. Replace canary references

Rediscover files (rerun the grep from Phase 1 Step 1). Replace:

```
drevops/ci-runner:canary
```

with:

```
drevops/ci-runner:<NEW_VERSION>@sha256:<NEW_HASH>
```

### 3. Commit and push on the same branch

```bash
git commit -m "Updated 'drevops/ci-runner' to '<NEW_VERSION>'."
```

```bash
git push
```

### 4. Update PR metadata

```bash
gh pr edit <PR> --title "Updated 'drevops/ci-runner' to '<NEW_VERSION>'."
```

Add a comment summarising the canary -> release transition.

### 5. Monitor CI

Same as Phase 1 Step 6. On success:

> CI green on pinned `<NEW_VERSION>`. PR `<URL>` ready to merge.

Do NOT merge: the user merges.

## Red Flags

- About to pin a SHA in Phase 1: don't. Canary needs the moving tag.
- About to edit a file under `tests/Fixtures/`: don't. Those are test snapshots.
- About to merge the PR: don't. User merges.
- About to use `gh pr create`: don't. Use `/open-pr`.
- About to start Phase 2 without Phase 1 CI green: confirm with user first.
- Hardcoding the file list from this skill: don't. Always grep first.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Hardcoded list of files | Always grep: the list grows over time |
| Included fixture files in edits | Exclude `*/Fixtures/*` and `*/tests/*` paths |
| Kept SHA pin in canary phase | Strip SHA: canary tag must be unpinned to test moving target |
| Used `git add .` | Stage specific files only |
| Reported CI failure raw | Read failing step, form a hypothesis, then report |
| Created PR via `gh pr create` | Use `/open-pr` skill |
| New branch for Phase 2 | Reuse the Phase 1 branch and PR |
