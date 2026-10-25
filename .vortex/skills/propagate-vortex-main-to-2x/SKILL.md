---
name: propagate-vortex-main-to-2x
description: Forward-port commits that landed on 'main' into the deviated '2.x' development branch. Lists every 'main' commit absent from '2.x', analyses each diff against the current state of '2.x', recommends apply/adapt/skip with a rationale, lets you select, then cherry-picks or hand-reapplies the chosen commits onto one feature branch and opens a single PR via /open-pr. Keeps a decisions ledger so re-runs across the release cycle never re-ask about settled commits. Triggers on 'propagate main to 2.x', 'port main to 2.x', 'forward-port to 2.x', 'bring main commits into 2.x', '/vortex:propagate-vortex-main-to-2x'.
user-invocable: true
---

# Propagate `main` commits to `2.x`

`main` is the current release line, and `2.x` is the next major, in active
development. The 2 branches have deviated. New work keeps landing on
`main`: fixes, dependency bumps, and CI changes. Only some of it belongs on
`2.x`, where the same areas may have been refactored, renamed, or removed.

This skill **forward-ports** selected `main` commits onto `2.x`. It lists
what's missing and **analyses each commit against the current state of
`2.x` before recommending anything**. Then it lets you choose, applies the
chosen commits, and opens 1 PR.

Run it repeatedly over the whole `2.x` development cycle, until `2.x` is
released and becomes `main`. A local decisions ledger remembers what you've
already applied or skipped, so each run surfaces only new, undecided
commits.

The skill is available on both the **1.x line (`main`)** and **`2.x`**,
but it always operates on `2.x`. It can be invoked from either line,
including `main`/1.x, so its first action (Step 1) switches the checkout to
`2.x`. That way it never runs against the wrong line.

The skill doesn't cherry-pick blindly. The analysis comes first, the
recommendation cites evidence, and the selection is yours.

## Direction (do not get this backwards)

- **Source** = `main` (where the commits are now).
- **Target** = `2.x` (where they're going).
- Bringing changes from the stable line into the newer development branch
  is a **forward-port**. The opposite direction (`2.x` -> `main`) is a
  backport, and it's **out of scope** for this skill.

## When to use

- "propagate main to 2.x", "port main to 2.x", "forward-port to 2.x",
  "bring main commits into 2.x".
- Periodically while `2.x` is the development branch, to keep it from
  drifting too far behind `main`.

## When NOT to use

- Moving commits the other way (`2.x` -> `main`).
- A wholesale rebase of `2.x` onto `main`. This skill is selective and
  per-commit by design. For a full rebase, do that directly.
- Porting your own in-flight work between feature branches.

## Prerequisites

- `git` with network access to `origin`.
- `gh` CLI authenticated, for the `/open-pr` handoff.
- A clean working tree on the current branch. If the tree isn't clean, the
  skill tells you to stash or commit first.
- Docker, **if** any selected commit touches template files and snapshots
  must be regenerated (Step 8).

## Naming convention

Compute a run slug once from the date:

```bash
date +"%Y%m%d"
```

- Work branch: `feature/propagate-main-{slug}`, for example
  `feature/propagate-main-20260617`. If a branch with that name already
  exists from an earlier run today, add the suffix `-2`, `-3`, and so on.
- Per-run analysis:
  `.artifacts/propagate-main-to-2x/run-{slug}/analysis.md`.
- Decisions ledger, which persists across runs:
  `.artifacts/propagate-main-to-2x/decisions.md`.

`.artifacts/` is local-only. It's git-excluded on `main`; treat it as
local-only on `2.x` too. The ledger and analysis are **never** staged or
committed.

## Workflow

### Step 1: Switch to `2.x` before any other action

This skill is available on both **`main`/1.x** and **`2.x`**, but it
always operates on **`2.x`**. It can be invoked from either line, so its
first action moves onto `2.x`. If you're already there, the switch does
nothing. Nothing else in this skill may run while `main` is checked out:
no enumeration, analysis, branching, or applying.

Fetch the authoritative state of both branches:

```bash
git fetch origin
```

Confirm the working tree is clean. If there are uncommitted changes, STOP
and ask the user to commit or stash first. Never stash on their behalf:

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

If the fast-forward pull fails, local `2.x` has diverged from `origin/2.x`.
STOP and report instead of forcing anything.

Enumeration and analysis still read from `origin/main` and `origin/2.x`,
never from locals that might be stale. The checkout must be on `2.x` so the
work branch in Step 6 and every applied commit land on the right line.

### Step 2: Enumerate candidate commits

List every `main` commit that isn't already patch-present in `2.x`:

```bash
git cherry -v origin/2.x origin/main
```

- Lines starting with `+` are **candidates**: no equivalent patch was
  found in `2.x`.
- Lines starting with `-` are already present, because an identical patch
  landed in `2.x`. Ignore them.

Record the merge base once, since the analysis needs it:

```bash
git merge-base origin/2.x origin/main
```

Call the result `MB`. If `git cherry` lists no `+` commits, report
"nothing to propagate" and stop.

### Step 3: Drop commits you have already decided

Read the decisions ledger if it exists:

```bash
cat .artifacts/propagate-main-to-2x/decisions.md
```

Build the set of full SHAs already recorded as `applied`, `skipped`, or
`deferred-permanent`, and remove those from the candidate list. Commits
recorded as `deferred-revisit` stay in the list, because you asked to look
at them again next time.

An **adapted** commit, hand-reapplied with a different patch, still shows
as `+` in `git cherry`, because its patch-id differs. The ledger stops it
from reappearing forever, so don't skip this step.

### Step 4: Analyse each candidate (READ-ONLY - do this BEFORE recommending anything)

For every remaining candidate, gather evidence **without modifying the
working tree**. Work oldest commit first.

For commit `C` (use the full SHA):

1. Read the change in full:

   ```bash
   git show --stat C
   ```

   ```bash
   git show C
   ```

2. Get the exact paths and their change type (Added, Modified, Deleted, or
   Renamed):

   ```bash
   git show --name-status --format= C
   ```

3. Check each touched path `P`. For renames and deletes, check the
   pre-image path.

   - Does `P` still exist on `2.x`?

     ```bash
     git ls-tree -r --name-only origin/2.x -- P
     ```

     Empty output means the path is gone on `2.x` (removed or renamed).

   - Has `2.x` itself changed `P` since the branches split?

     ```bash
     git log --oneline MB..origin/2.x -- P
     ```

     Any commits here mean `2.x` has **diverged** on this path, so a clean
     cherry-pick is unlikely.

   - To judge whether the change is already effectively present, read the
     `2.x` version of the file:

     ```bash
     git show origin/2.x:P
     ```

4. Classify `C` with the [classification rubric](#classification-rubric).
   Write a 1-line rationale that cites the evidence: the diverged path,
   the rename, or the line that already exists on `2.x`.

Append every candidate's evidence and classification to
`.artifacts/propagate-main-to-2x/run-{slug}/analysis.md`, so the reasoning
can be audited.

### Step 5: Recommend, present, and let the user select

Present a single table, oldest commit first:

| # | Short SHA | Subject | Touched area | Recommendation | Why |
|---|-----------|---------|--------------|----------------|-----|
| 1 | `f6370725` | Excluded demo dev/test modules from exported config | `config/` | APPLY | Paths unchanged on 2.x |
| 2 | `123e9333` | Added '2.x' to tooling publish trigger | `.github/workflows/` | REVIEW | Branch-name logic; may already be moot on 2.x |

Then ask the user which commits to apply. **Pre-select everything marked
APPLY and ADAPT**, and leave SKIP unticked. Use `AskUserQuestion`
(multiSelect) when the list is short enough. For long lists, present the
table and ask the user to confirm or amend the pre-selection. The
recommendation is a default; the user's choice always wins.

If the user defers a commit, ask whether it's `deferred-revisit` (show
again next run) or `deferred-permanent` (never again). Record the answer
in Step 10.

### Step 6: Create the work branch

You're already on `2.x` from Step 1. Create 1 work branch per run, based on
the authoritative `origin/2.x`:

```bash
git checkout -b feature/propagate-main-{slug} origin/2.x
```

The Step 1 switch to `2.x` and this branch creation are the only branch
changes the skill makes. They're its explicit, user-approved job. Don't
create additional branches; everything selected this run lands here.

### Step 7: Apply the selected commits

Apply in `main`'s original order (oldest -> newest) to minimize conflicts.
Keep each port as its **own** commit, 1:1 with the source, for
traceability. Don't squash unrelated ports together.

**APPLY commits** - cherry-pick with provenance:

```bash
git cherry-pick -x C
```

`-x` appends a `(cherry picked from commit ...)` line, which keeps the link
to `main`.

**ADAPT commits, or any APPLY that conflicts** - the change is relevant,
but `2.x` has moved:

1. If a cherry-pick is in progress and conflicting, resolve the conflicts
   by re-implementing the commit's *intent* in `2.x`'s current structure.
   Don't force-fit `main`'s lines. Then continue:

   ```bash
   git cherry-pick --continue
   ```

2. If the change is structurally different on `2.x` (a file was renamed or
   refactored), abort and hand-write the equivalent:

   ```bash
   git cherry-pick --abort
   ```

   Make the edits, then commit in the project's style: past tense, ending
   with a period, code refs in single quotes. Add a body line naming the
   source commit, for example `Forward-ported from main C.`

**Fixture-heavy commits** are commits that also touch
`.vortex/cli/tests/Fixtures/`, as most template changes do. `2.x` has
already regenerated those fixtures for its own work. The commit's fixture
hunks conflict on a cherry-pick even when the source applies cleanly.
Never hand-merge fixture hunks. Apply the **source files only**, and let
Step 8 regenerate the fixtures:

- For a clean (un-diverged) source file, take it straight from the source
  commit instead of cherry-picking the whole commit:

  ```bash
  git checkout C -- path/to/source/file
  ```

  This skips the cherry-pick and its fixture conflicts. Confirm with
  `git status` that only source paths are staged, then commit.

- If a `git cherry-pick` is in progress and stopped on fixture conflicts,
  discard the fixture changes, keep the applied source, and continue:

  ```bash
  git checkout HEAD -- .vortex/cli/tests/Fixtures
  ```

The port commit must carry **source only**. Step 8's `update-snapshots`
rebuilds every cascaded fixture in 1 pass. Any fixture delta inside a port
commit is wrong and conflicts with the regeneration.

After each commit, check that it landed as intended:

```bash
git show --stat HEAD
```

### Step 8: Regenerate snapshots (foreground only)

If any applied commit touched **template** files (anything outside
`.vortex/`), the CLI fixtures must be regenerated, or CI fails. Run from
`.vortex/`:

```bash
cd .vortex
```

```bash
ahoy update-snapshots
```

NEVER background this command. It auto-commits and runs in parallel, so a
background run leaves a partial branch. Let it finish in the foreground.

After it runs, verify that the regenerated fixtures match the change. If
`update-snapshots` reports more `✗` than the files it committed, a fixture
write might have been dropped. Diff the sibling fixtures and re-run the
full `update-snapshots` to recover before trusting the result.

If any applied commit **deleted** a template file, `SutTrait.php`
assertions may also need a manual update. `update-snapshots` doesn't catch
a removed file; only the slower CI workflow does. Flag this in the PR
description.

Return to the repo root for the remaining steps:

```bash
cd ..
```

### Step 9: Local gates

Run the maintenance lint from `.vortex/`. Verify the exact command names
against `.vortex/.ahoy.yml`:

```bash
cd .vortex
```

```bash
ahoy lint
```

```bash
cd ..
```

If lint fails, fix it, or run `ahoy lint-fix` from `.vortex/`, before
opening the PR. The heavy `vortex-test-workflow` validation is **CI's
job** and the final source of truth. Don't try to reproduce the whole
template-test matrix locally; opening the PR runs it.

### Step 10: Record decisions

Update `.artifacts/propagate-main-to-2x/decisions.md` so future runs skip
settled commits. Append 1 row per commit handled this run: applied,
skipped, and deferred alike. See the
[decisions ledger format](#decisions-ledger-format). This file is
local-only, so don't stage it.

### Step 11: Open the PR

Invoke the `/open-pr` skill, never raw `gh`. The PR targets **`2.x`**, not
`main`. The description must include:

1. **Scope** - 1 sentence: "Forward-ports N commits from `main` onto
   `2.x`."
2. **Applied** - a bullet list. Each bullet has the source short SHA and
   subject, and says whether it was a clean cherry-pick or an adaptation.
   For an adaptation, say what was adapted and why.
3. **Skipped** - a bullet list of candidates not ported, with a 1-line
   reason for each: already present, superseded, area removed on `2.x`,
   and so on. Reviewers then see the deliberate exclusions.
4. **Snapshots** - state whether `ahoy update-snapshots` ran, and whether
   any deleted-file `SutTrait.php` follow-up is outstanding.
5. **Gates** - confirm local lint passed, and note that CI runs the full
   template-test workflow.

Don't reference the `.artifacts/` ledger or analysis paths in the PR body.
They're local and absent from the branch.

## Classification rubric

For each candidate, the evidence from Step 4 maps to 1 recommendation:

- **APPLY** - every touched path exists on `2.x` with **no** `2.x` commits
  since `MB` (no divergence), and the change isn't already present. A
  clean `git cherry-pick -x` is expected.
- **ADAPT** - the change is relevant, but at least 1 touched path has
  diverged on `2.x` (commits in `MB..origin/2.x -- P`) or was renamed.
  Expect conflicts, and reapply the intent in `2.x`'s structure.
- **SKIP** - 1 of these holds:
  - The change is already effectively present on `2.x`. Read the file to
    confirm.
  - The subsystem it touches was removed or replaced on `2.x`, so the
    change is moot.
  - The change is inherently `main`-only.
- **REVIEW** - the evidence is inconclusive. Examples are branch-name or
  version-gated logic, or a change whose relevance depends on a `2.x`
  decision you can't infer. Present it and let the user decide. Never
  silently APPLY or SKIP a REVIEW.

When in doubt between APPLY and ADAPT, prefer ADAPT. It forces a conscious
look at the diverged area instead of trusting a clean-looking patch.

## Decisions ledger format

`.artifacts/propagate-main-to-2x/decisions.md` is a single append-only
table:

```markdown
# Propagate main -> 2.x decisions

| Source SHA | Subject | Decision | Date | Result on 2.x | Rationale |
|------------|---------|----------|------|---------------|-----------|
| f63707253624... | Excluded demo dev/test modules from exported config | applied | 2026-06-17 | a1b2c3d4 | Clean cherry-pick |
| 123e93335d8e... | Added '2.x' to tooling publish trigger | skipped | 2026-06-17 | - | Trigger already covers 2.x on the 2.x branch |
| 6678b9ea4ea6... | Configurable SSH known_hosts | deferred-revisit | 2026-06-17 | - | Wait until 2.x SSH step settles |
```

Decision values are `applied`, `skipped`, `deferred-revisit` (re-surface
next run), and `deferred-permanent` (never re-surface). Store the **full**
source SHA so future `git cherry` runs can be matched against it.

## Red flags

Stop when you're about to do any of these:

- Run any step while `main`/1.x is checked out. This skill operates on
  `2.x` whichever line it was invoked from; Step 1 switches to `2.x`
  first.
- Cherry-pick before doing the Step 4 analysis. The user asked for
  analysis, then recommendation, never blind picking.
- Commit ports straight onto `2.x`. Everything lands on the
  `feature/propagate-main-{slug}` work branch and reaches `2.x` only
  through the PR.
- Target the PR at `main`. The target is `2.x`.
- Background `ahoy update-snapshots`. It runs in the foreground only; a
  background run leaves a partial branch.
- Skip snapshot regeneration after touching template files. CI fails on
  stale fixtures.
- Squash several ported commits into 1. Keep them 1:1 with the source for
  traceability.
- Bring a commit's `.vortex/cli/tests/Fixtures/` hunks into a port commit.
  Apply source only; Step 8's `update-snapshots` regenerates fixtures.
- Re-surface a commit the ledger marks `skipped` or `applied`. Read the
  ledger in Step 3 first.
- Stage anything under `.artifacts/`. The ledger and analysis are
  local-only.
- Cherry-pick a merge commit. Vortex squash-merges PRs, so candidates
  should be single non-merge commits. A merge commit in the list means
  something is off, so investigate before applying.
- "Fix" a conflict by force-fitting `main`'s exact lines into a refactored
  `2.x` file. Reapply the intent in `2.x`'s structure; that's what ADAPT
  means.
- Run `gh pr create` directly. All PRs go through `/open-pr`.

## Command rules - CRITICAL

NEVER use compound or composite commands. Every Bash tool call must
contain exactly ONE simple command.

**NEVER use:** `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, heredocs.

**ALWAYS:** Make multiple separate Bash tool calls, 1 command per call. The
`cd .vortex` lines in this skill are standalone calls; the working
directory persists to the next call.
