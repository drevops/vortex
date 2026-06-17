---
name: propagate-vortex-main-to-2x
description: Forward-port commits that landed on 'main' into the deviated '2.x' development branch. Lists every 'main' commit absent from '2.x', analyses each diff against the current state of '2.x', recommends apply/adapt/skip with a rationale, lets you select, then cherry-picks or hand-reapplies the chosen commits onto one feature branch and opens a single PR via /open-pr. Keeps a decisions ledger so re-runs across the release cycle never re-ask about settled commits. Triggers on 'propagate main to 2.x', 'port main to 2.x', 'forward-port to 2.x', 'bring main commits into 2.x', '/propagate-vortex-main-to-2x'.
user-invocable: true
---

# Propagate `main` commits to `2.x`

`main` (the current release line) and `2.x` (the next major, in active development) have deviated. New work keeps landing on `main` - fixes, dependency bumps, CI changes - and only some of it belongs on `2.x`, where the same areas may have been refactored, renamed, or removed. This skill **forward-ports** selected `main` commits onto `2.x`: it lists what is missing, **analyses each commit against the current state of `2.x` before recommending anything**, lets you choose, and then applies the chosen commits and opens one PR.

It is meant to be run repeatedly over the whole `2.x` development cycle, until `2.x` is released and becomes `main`. A local decisions ledger remembers what you have already applied or skipped, so each run only surfaces genuinely new, undecided commits.

The skill is available on both the **1.x line (`main`)** and **`2.x`**, but it always operates on `2.x`. Because it can be invoked from either line - including from `main`/1.x - its very first action (Step 1) is to switch the checkout to `2.x`, so it never runs against the wrong line.

This is **not** blind cherry-picking. The analysis comes first; the recommendation is evidence-based; the selection is yours.

## Direction (do not get this backwards)

- **Source** = `main` (where the commits are now).
- **Target** = `2.x` (where they are going).
- Bringing changes from the stable line into the newer development branch is a **forward-port**. The opposite direction (`2.x` -> `main`) is a backport and is **out of scope** for this skill.

## When to use

- "propagate main to 2.x", "port main to 2.x", "forward-port to 2.x", "bring main commits into 2.x".
- Periodically while `2.x` is the development branch, to keep it from drifting too far behind `main`.

## When NOT to use

- Moving commits the other way (`2.x` -> `main`).
- A wholesale rebase of `2.x` onto `main`. This skill is selective and per-commit by design; if you want a full rebase, do that directly.
- Porting your own in-flight work between feature branches.

## Prerequisites

- `git` with network access to `origin`.
- `gh` CLI authenticated (for the `/open-pr` handoff).
- A clean working tree on the current branch (the skill will tell you to stash or commit first if not).
- Docker available **if** any selected commit touches template files and snapshots must be regenerated (Step 8).

## Naming convention

Compute a run slug once from the date:

```bash
date +"%Y%m%d"
```

- Work branch: `feature/propagate-main-{slug}` (e.g. `feature/propagate-main-20260617`). If a branch with that name already exists from an earlier run today, suffix `-2`, `-3`, ...
- Per-run analysis: `.artifacts/propagate-main-to-2x/run-{slug}/analysis.md`.
- Decisions ledger (persists across runs): `.artifacts/propagate-main-to-2x/decisions.md`.

`.artifacts/` is local-only (git-excluded on `main`; treat it as local-only on `2.x` regardless). The ledger and analysis are **never** staged or committed.

## Workflow

### Step 1: Switch to `2.x` before any other action

This skill is available on both **`main`/1.x** and **`2.x`**, but it always operates on **`2.x`**. Since it can be invoked from either line, the first thing it does is move onto `2.x` (a no-op if you are already there). Nothing else in this skill - no enumeration, analysis, branching, or applying - may run while `main` is checked out.

Fetch the authoritative state of both branches:

```bash
git fetch origin
```

Confirm the working tree is clean. If there are uncommitted changes, STOP and ask the user to commit or stash first - never stash on their behalf:

```bash
git status
```

Switch to `2.x` and bring it level with the remote:

```bash
git checkout 2.x
```

```bash
git pull --ff-only origin 2.x
```

If the fast-forward pull fails, local `2.x` has diverged from `origin/2.x` - STOP and report rather than forcing anything.

Enumeration and analysis still read from `origin/main` and `origin/2.x` (never possibly-stale locals), but the checkout must be on `2.x` so the work branch in Step 6 and every applied commit land on the right line.

### Step 2: Enumerate candidate commits

List every `main` commit that is not already patch-present in `2.x`:

```bash
git cherry -v origin/2.x origin/main
```

- Lines starting with `+` are **candidates** (no equivalent patch found in `2.x`).
- Lines starting with `-` are already present (an identical patch landed in `2.x`); ignore them.

Record the merge base once - the analysis needs it:

```bash
git merge-base origin/2.x origin/main
```

Call the result `MB`. If `git cherry` lists no `+` commits, report "nothing to propagate" and stop.

### Step 3: Drop commits you have already decided

Read the decisions ledger if it exists:

```bash
cat .artifacts/propagate-main-to-2x/decisions.md
```

Build the set of full SHAs already recorded as `applied`, `skipped`, or `deferred-permanent`. Remove those from the candidate list. Commits recorded as `deferred-revisit` stay in the list (you asked to look at them again next time).

A commit that was **adapted** (hand-reapplied with a different patch) will still show as `+` in `git cherry` because its patch-id differs - the ledger is what stops it from reappearing forever. This is why the ledger exists; do not skip this step.

### Step 4: Analyse each candidate (READ-ONLY - do this BEFORE recommending anything)

This is the core of the skill. For every remaining candidate, gather evidence **without modifying the working tree**. Work oldest commit first.

For commit `C` (use the full SHA):

1. Read the change in full:

   ```bash
   git show --stat C
   ```

   ```bash
   git show C
   ```

2. Get the exact paths and their change type (Added / Modified / Deleted / Renamed):

   ```bash
   git show --name-status --format= C
   ```

3. For each touched path `P` (for renames/deletes, check the pre-image path):

   - Does `P` still exist on `2.x`?

     ```bash
     git ls-tree -r --name-only origin/2.x -- P
     ```

     Empty output = the path is gone on `2.x` (removed or renamed).

   - Has `2.x` itself changed `P` since the branches split?

     ```bash
     git log --oneline MB..origin/2.x -- P
     ```

     Any commits here = `2.x` has **diverged** on this path; a clean cherry-pick is unlikely.

   - When you need to judge whether the change is already effectively present, read the `2.x` version of the file:

     ```bash
     git show origin/2.x:P
     ```

4. Classify `C` using the rubric below, and write a one-line, evidence-cited rationale (name the diverged path, the rename, or the line that already exists on `2.x`).

Append every candidate's evidence and classification to `.artifacts/propagate-main-to-2x/run-{slug}/analysis.md` so the reasoning is auditable.

### Step 5: Recommend, present, and let the user select

Present a single table, oldest commit first:

| # | Short SHA | Subject | Touched area | Recommendation | Why |
|---|-----------|---------|--------------|----------------|-----|
| 1 | `f6370725` | Excluded demo dev/test modules from exported config | `config/` | APPLY | Paths unchanged on 2.x |
| 2 | `123e9333` | Added '2.x' to tooling publish trigger | `.github/workflows/` | REVIEW | Branch-name logic; may already be moot on 2.x |

Then ask the user which commits to apply, **pre-selecting everything marked APPLY and ADAPT** and leaving SKIP unticked. Use `AskUserQuestion` (multiSelect) when the list is short enough; for long lists, present the table and ask the user to confirm or amend the pre-selection. The recommendation is a default, never an override of the user's choice.

If the user defers a commit, ask whether it is `deferred-revisit` (show again next run) or `deferred-permanent` (never again) and record accordingly in Step 10.

### Step 6: Create the work branch

You are already on `2.x` from Step 1. Create one work branch per run, based on the authoritative `origin/2.x`:

```bash
git checkout -b feature/propagate-main-{slug} origin/2.x
```

The Step 1 switch to `2.x` and this branch creation are the only branch changes the skill makes, and they are its explicit, user-approved job. Do not create additional branches; everything selected this run lands here.

### Step 7: Apply the selected commits

Apply in `main`'s original order (oldest -> newest) to minimise conflicts. Keep each port as its **own** commit (1:1 with the source) for traceability - do not squash unrelated ports together.

**APPLY commits** - cherry-pick with provenance:

```bash
git cherry-pick -x C
```

`-x` appends a `(cherry picked from commit ...)` line, preserving the link to `main`.

**ADAPT commits, or any APPLY that conflicts** - the change is relevant but `2.x` has moved:

1. If a cherry-pick is already in progress and conflicting, resolve the conflicts by re-implementing the commit's *intent* in `2.x`'s current structure (not by force-fitting `main`'s lines), then:

   ```bash
   git cherry-pick --continue
   ```

2. If the change is structurally different on `2.x` (file renamed/refactored), abort and hand-write the equivalent:

   ```bash
   git cherry-pick --abort
   ```

   Make the edits, then commit in the project's style (past-tense, ends with a period, code refs in single quotes), with a body line naming the source commit, e.g. `Forward-ported from main C.`

After each commit, sanity-check it landed as intended:

```bash
git show --stat HEAD
```

### Step 8: Regenerate snapshots (foreground only)

If any applied commit touched **template** files (anything outside `.vortex/`), the installer fixtures must be regenerated or CI will fail. Run from `.vortex/`:

```bash
cd .vortex
```

```bash
ahoy update-snapshots
```

NEVER background this command. It auto-commits and parallelises; backgrounding leaves a partial branch. Let it finish in the foreground.

After it runs, verify the regenerated fixtures match the change. If `update-snapshots` reports more `✗` than the files it committed, that can be a real dropped fixture write - diff the sibling fixtures and re-run the full `update-snapshots` to recover before trusting the result.

If any applied commit **deleted** a template file, note that `SutTrait.php` assertions may also need updating by hand - `update-snapshots` will not catch a removed file, only the slower CI workflow does. Flag this in the PR description.

Return to the repo root for the remaining steps:

```bash
cd ..
```

### Step 9: Local gates

Run the maintenance lint from `.vortex/` (verify the exact command names against `.vortex/.ahoy.yml`):

```bash
cd .vortex
```

```bash
ahoy lint
```

```bash
cd ..
```

If lint fails, fix it (or `ahoy lint-fix` from `.vortex/`) before opening the PR. The heavy `vortex-test-workflow` validation is **CI's job** and is the final source of truth - do not try to reproduce the whole template-test matrix locally. Opening the PR is what runs it.

### Step 10: Record decisions

Update `.artifacts/propagate-main-to-2x/decisions.md` so future runs skip settled commits. Append one row per commit handled this run (applied, skipped, and deferred alike). See the format below. This file is local-only - do not stage it.

### Step 11: Open the PR

Invoke the `/open-pr` skill (never raw `gh`). The PR targets **`2.x`**, not `main`. The description must include:

1. **Scope** - one sentence: "Forward-ports N commits from `main` onto `2.x`."
2. **Applied** - bullet list, each: source short SHA + subject, and whether it was a clean cherry-pick or an adaptation (and what was adapted and why).
3. **Skipped** - bullet list of candidates not ported and the one-line reason for each (already present, superseded, area removed on `2.x`, etc.), so reviewers see the deliberate exclusions.
4. **Snapshots** - state whether `ahoy update-snapshots` ran and whether any deleted-file `SutTrait.php` follow-up is outstanding.
5. **Gates** - confirm local lint passed; note that CI runs the full template-test workflow.

Do not reference the `.artifacts/` ledger or analysis paths from the PR body - they are local and absent from the branch.

## Classification rubric

For each candidate, the evidence from Step 4 maps to one recommendation:

- **APPLY** - every touched path exists on `2.x` and has **no** `2.x` commits since `MB` (no divergence), and the change is not already present. A clean `git cherry-pick -x` is expected.
- **ADAPT** - the change is relevant, but at least one touched path has diverged on `2.x` (commits in `MB..origin/2.x -- P`) or was renamed. Expect conflicts; reapply the intent in `2.x`'s structure.
- **SKIP** - the change is already effectively present on `2.x` (read the file to confirm), or the subsystem it touches was removed/replaced on `2.x` so the change is moot, or it is inherently `main`-only.
- **REVIEW** - the evidence is inconclusive (e.g. branch-name or version-gated logic, or a change whose relevance depends on a `2.x` decision you cannot infer). Present it and let the user decide; never silently APPLY or SKIP a REVIEW.

When in doubt between APPLY and ADAPT, prefer ADAPT - it forces a conscious look at the diverged area instead of trusting a clean-looking patch.

## Decisions ledger format

`.artifacts/propagate-main-to-2x/decisions.md` is a single append-only table:

```markdown
# Propagate main -> 2.x decisions

| Source SHA | Subject | Decision | Date | Result on 2.x | Rationale |
|------------|---------|----------|------|---------------|-----------|
| f63707253624... | Excluded demo dev/test modules from exported config | applied | 2026-06-17 | a1b2c3d4 | Clean cherry-pick |
| 123e93335d8e... | Added '2.x' to tooling publish trigger | skipped | 2026-06-17 | - | Trigger already covers 2.x on the 2.x branch |
| 6678b9ea4ea6... | Configurable SSH known_hosts | deferred-revisit | 2026-06-17 | - | Wait until 2.x SSH step settles |
```

Decision values: `applied`, `skipped`, `deferred-revisit` (re-surface next run), `deferred-permanent` (never re-surface). Store the **full** source SHA so future `git cherry` runs can be matched against it.

## Red flags

- About to run any step while `main`/1.x is checked out: stop. This skill operates on `2.x` regardless of which line it was invoked from; Step 1 switches to `2.x` first.
- About to cherry-pick before doing the Step 4 analysis: stop. The user asked for analysis-then-recommendation, not blind picking.
- About to commit ports straight onto `2.x`: stop. Everything lands on the `feature/propagate-main-{slug}` work branch and reaches `2.x` only via the PR.
- About to target the PR at `main`: stop. The target is `2.x`.
- About to background `ahoy update-snapshots`: stop. Foreground only - backgrounding leaves a partial branch.
- About to skip snapshot regeneration after touching template files: stop. CI will fail on stale fixtures.
- About to squash several ported commits into one: stop. Keep 1:1 with the source for traceability.
- About to re-surface a commit the ledger marks `skipped` or `applied`: stop. Read the ledger in Step 3 first.
- About to stage anything under `.artifacts/`: stop. The ledger and analysis are local-only.
- About to cherry-pick a merge commit: stop. Vortex squash-merges PRs, so candidates should be single non-merge commits; a merge commit in the list means something is off - investigate before applying.
- About to "fix" a conflict by force-fitting `main`'s exact lines into a refactored `2.x` file: stop. Reapply the intent in `2.x`'s structure (that is what ADAPT means).
- About to create a `gh pr create` directly: stop. All PRs go through `/open-pr`.

## Command rules - CRITICAL

NEVER use compound or composite commands. Every Bash tool call must contain exactly ONE simple command.

**NEVER use:** `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, heredocs.

**ALWAYS:** Make multiple separate Bash tool calls, one command per call. The `cd .vortex` lines above are standalone calls; the working directory persists to the next call.
