---
name: create-vortex-tooling-release-notes
description: Use when preparing release notes for the 'drevops/vortex-tooling' Composer package, published as a read-only mirror of '.vortex/tooling/' from the Vortex monorepo. Builds a tooling-only changelog from the previous release tag (or a commit) up to the current state, enriches each entry from its monorepo pull request and issue, and writes consumer-facing notes. Releases are manually gated and rarely align with Vortex releases, so the range is driven by the previous tooling tag, not by a Vortex tag. Triggers on phrases like 'tooling release notes', 'vortex-tooling release notes', 'release notes for vortex-tooling'.
user-invocable: true
---

# Create Vortex Tooling Release Notes

Generate consumer-facing release notes for the `drevops/vortex-tooling`
Composer package.

`drevops/vortex-tooling` is a **read-only mirror** of the `.vortex/tooling/`
directory in the `drevops/vortex` monorepo. A publishing workflow copies
that directory to the mirror repository on every push to `main`. The
mirror's git history is mostly noise: it has 1 commit per monorepo commit,
including empty commits for monorepo commits that didn't touch the tooling.

Tooling releases are **manually gated** and almost never align with Vortex
template releases. A Vortex tag can't tell you what changed since the last
tooling release. This skill rebuilds that set from the monorepo and turns
it into release notes.

## When to use

- A new `drevops/vortex-tooling` release is about to be cut and needs
  notes.
- An existing tooling release has empty or missing notes that need
  backfilling.
- You want to see what tooling changes have accumulated since the last
  release.

Run it from inside a `drevops/vortex` monorepo clone, with `git` and the
GitHub CLI (`gh`) installed and authenticated. It produces notes only. It
does **not** tag, publish, or change the `drevops/vortex-tooling`
repository.

## How the reconciliation works

Every commit published to `drevops/vortex-tooling` records its origin in
the commit body as `Source: drevops/vortex@<SHA>`, so each release tag
points back to an exact monorepo commit. Resolve the previous tooling
release tag to its source SHA. Then list the monorepo commits from that SHA
to now that changed shipped tooling files.

Only part of `.vortex/tooling/` ships to consumers. `tests/`, `playground/`,
and the dotfiles are `export-ignore`d from the Composer archive. Consumers
receive `src/`, `composer.json`, `README.md`, and `LICENSE`. Filtering the
log to the shipped paths drops the empty mirror commits and every unrelated
monorepo commit in 1 step.

## Inputs

The skill takes a **lower bound** and an optional **upper bound** that
define the range. Each bound is a tooling release tag or a monorepo commit
SHA.

| Bound | Default | Meaning |
|-------|---------|---------|
| Lower (`FROM`) | latest published `drevops/vortex-tooling` release tag | Start of the range - the previous release. |
| Upper (`TO`) | current `HEAD` | End of the range - the release being prepared. |

The range takes 1 of 2 common shapes:

- **Next release** (the usual case): give only the lower bound, or nothing.
  The range is "previous tag to `HEAD`", and you choose the new version in
  Step 4.
- **Backfill** an already-published release: give both bounds as tags, for
  example `1.1.0` and `1.2.0`. The range is "1.1.0 to 1.2.0", and the new
  version is the upper tag.

## Process

### Step 1: Resolve the range bounds to monorepo source SHAs

Resolve the lower bound to `FROM_SHA` and the upper bound to `TO_SHA`. Each
bound follows the same rule:

- **A tag**: read its source SHA from the mirror commit body. This includes
  the latest release tag when no lower bound was given; find it with
  `gh release list -R drevops/vortex-tooling --limit 1`.

  ```bash
  gh api repos/drevops/vortex-tooling/commits/1.2.0 --jq '.commit.message'
  ```

  Parse the `Source: drevops/vortex@<SHA>` line from the output. That
  `<SHA>` is the bound.

- **A commit SHA**: use it directly.
- **No upper bound given**: `TO_SHA` is `HEAD`.

Confirm each resolved SHA exists locally:

```bash
git cat-file -t <SHA>
```

If `git cat-file` fails, the local clone is behind. Run `git fetch origin`
and retry. If the SHA still can't be found, stop with a clear message.

### Step 2: Build the tooling-only changelog

List the monorepo commits between `FROM_SHA` and `TO_SHA` that changed a
shipped tooling file, newest first:

```bash
git log <FROM_SHA>..<TO_SHA> --no-merges --pretty=format:'%H%x09%s' -- .vortex/tooling/ ':(exclude).vortex/tooling/tests' ':(exclude).vortex/tooling/playground' ':(exclude).vortex/tooling/.gitattributes' ':(exclude).vortex/tooling/.gitignore'
```

The path filter keeps only commits that touched files consumers receive.
It drops the empty mirror commits and every non-tooling monorepo commit.

If the command returns nothing, there's nothing to release. Tell the user
"No tooling changes shipped since `<lower-bound>`; no release needed" and
stop. Don't write an empty notes file.

Each output line is a commit: a full SHA, a tab, then the subject. The
subject already has the entry shape these notes use: an optional
`[#ISSUE]` prefix and a trailing `(#PR)` reference, for example:

```text
f8e1361b…  [#2643] Hardened the host-side database-download tooling scripts. (#2648)
```

### Step 3: Enrich each entry from its pull request

Each commit needs enough context for an accurate paragraph. **The pull
requests and issues live in `drevops/vortex`, never in
`drevops/vortex-tooling`** (the mirror has none). Work through this order
and stop as soon as you have enough:

1. **Use the subject** if it's already specific enough. Skip the fetch in
   that case.
2. **Fetch the pull request** named by the trailing `(#NNN)`:

   ```bash
   gh pr view 2648 --repo drevops/vortex --json title,body,labels,author
   ```

   Use `author.login` for the `@author` attribution.
3. **Fetch the linked issue** named by a `[#NNN]` prefix when the pull
   request body is still ambiguous:

   ```bash
   gh issue view 2643 --repo drevops/vortex --json title,body
   ```

4. **Inspect the change itself** only when the descriptions say too little.
   The diff is in the monorepo, so read it locally instead of over the
   API:

   ```bash
   git show <SHA> -- .vortex/tooling/src
   ```

   Read only enough to understand intent. The signals that matter most are
   new or renamed `VORTEX_*` environment variables, changed defaults, and
   added or removed scripts.

Batch independent `gh` calls in parallel: issue several Bash tool calls in
a single message, 8-10 at a time. A release with many entries then resolves
in a few waves instead of 1 call at a time. Pull request bodies often
contain auto-generated review-bot sections; skip them and rely on the
human-written summary.

If `gh` is unavailable or auth fails, write conservative paragraphs from
the commit subjects alone. Tell the user which entries lack deep context so
they can review them.

### Step 4: Determine the version

`PREVIOUS_VERSION` is the lower-bound tag, or the latest release tag when a
bare commit was given.

- **Backfill** (the upper bound is an existing tag): `NEW_VERSION` is that
  upper tag.
- **Next release** (the upper bound is `HEAD` or a bare commit): suggest
  `NEW_VERSION` from the nature of the changes, then **confirm it with the
  user** before writing. The version only labels the notes; no tag is
  created.

Pick the version bump from the changes:

- **Major** (for example `2.0.0`): a shipped script was removed or renamed;
  an environment variable consumers set was removed or renamed; a default
  behavior changed in a way that breaks existing usage; or the PHP
  requirement in `composer.json` was raised in a breaking way.
- **Minor** (for example `1.3.0`): a new script was added, or a new opt-in
  capability, flag, or environment variable that leaves existing projects
  unaffected.
- **Patch** (for example `1.2.1`): a bug fix, a hardening or robustness
  change, an internal refactor with no contract change, or documentation
  fixes.

### Step 5: Write and display the notes

Write the file to `.artifacts/release-notes-tooling-<NEW_VERSION>.md` (for
example `release-notes-tooling-1.2.1.md`), using the following structure
and rules. Then show its full contents to the user in a fenced `markdown`
code block for review.

## Output structure

```markdown
## What's new since PREVIOUS_VERSION

### Breaking changes

- **[#NNN](https://github.com/drevops/vortex/issues/NNN) Original subject. @author (https://github.com/drevops/vortex/pull/NNN)**<br>Paragraph: what used to work, the new behaviour, and the exact migration step consumers must take.

### Highlights

- **[#NNN](https://github.com/drevops/vortex/issues/NNN) Original subject. @author (https://github.com/drevops/vortex/pull/NNN)**<br>Paragraph: why this matters to someone running the scripts - the capability it unlocks or the pain it removes.

### All changes

- **[#NNN](https://github.com/drevops/vortex/issues/NNN) Original subject. @author (https://github.com/drevops/vortex/pull/NNN)**<br>Paragraph (1-3 sentences) explaining what the change does and why it is valuable.

**Full Changelog**: https://github.com/drevops/vortex-tooling/compare/PREVIOUS_VERSION...NEW_VERSION
```

## Formatting rules

### Sections

- **Breaking changes**: include this section only if at least 1 entry is
  breaking; otherwise omit the heading. An entry is breaking when
  consumers who upgrade may have to change their own setup, or when
  observable behavior changes automatically on upgrade.
  - The tooling's public surface is the **script contract**: script names
    under `src/`, the environment variables they read, their arguments,
    their observable side effects, and the `composer.json` platform
    requirement.
  - An opt-out flag makes a breaking change recoverable, but it's still
    breaking. List it and explain the opt-out in the migration step.
- **Highlights**: pick the 5-7 entries that matter most to consumers.
  Favor new scripts and new capabilities over internal hardening,
  refactors, and doc fixes. Omit the heading if the release is too small
  for meaningful highlights. Every highlight also appears under All
  changes: highlights are a curated view of the same entries.
- **All changes**: list every entry from Step 2, newest first, with the
  commit subject text verbatim. Don't reword, reorder, merge, or drop
  entries.

### Entries

- **No hand-wrapping.** Every bullet, including its paragraph, is a
  **single line** in the file, regardless of length. GitHub renders the
  markdown and wraps it in the browser. This SKILL file is wrapped because
  it's source; the deliverable goes to a renderer. Tables and code fences
  keep their structure.
- Format exactly: wrap the original subject in `**...**`, follow it
  immediately with `<br>`, then put the paragraph on the same line. No
  blank line between them and no indentation.
- **Render every issue and pull-request reference as a full
  `drevops/vortex` URL**, never a bare `#NNN`. The notes are published to
  the `drevops/vortex-tooling` mirror, where a bare `#NNN` links to that
  repository's own numbering. Transform the commit subject as follows:
  - The leading `[#NNN]` issue reference becomes the markdown link
    `[#NNN](https://github.com/drevops/vortex/issues/NNN)`.
  - The trailing `(#NNN)` pull-request reference becomes the bare URL
    `(https://github.com/drevops/vortex/pull/NNN)`.
  - When a commit has no `[#NNN]` issue prefix, start with the subject
    text.
  - When a commit has no `(#NNN)` pull-request reference (a direct push),
    omit the trailing reference and enrich from the commit and its diff.
  - Keep the `@author` handle, since a global GitHub mention resolves
    anywhere. Keep the subject's punctuation and capitalization.
- Paragraphs are 1-3 sentences of plain prose. For fixes, name what was
  broken, the user-visible symptom, and the corrected behavior. For
  features, name what you can do now that you couldn't before, and the
  environment variable or flag that controls it, with its default. Never
  invent details the source doesn't support.
- Some entries stay as bare list items with no bold and no paragraph:
  entries authored by dependency bots (`@renovate[bot]`,
  `@dependabot[bot]`), and entries whose subject begins with
  `Update dependency`, `Update all dependencies`, or `Bump` followed by a
  space. These are rare here, since the package only requires PHP.
- Don't insert blank lines between consecutive list items.

### Footer

The `**Full Changelog**` line points at the **mirror** repository,
`drevops/vortex-tooling`, using `PREVIOUS_VERSION...NEW_VERSION`. The link
resolves after the `NEW_VERSION` tag is created on that repository.

## Validation checklist

Before displaying the output, verify:

- The file is saved to `.artifacts/release-notes-tooling-<NEW_VERSION>.md`.
- The first line is `## What's new since <PREVIOUS_VERSION>`.
- `### Breaking changes` appears only if there's at least 1 breaking entry.
- `### All changes` lists every commit from Step 2, verbatim, newest first.
- Every non-bot entry is `**subject**<br>paragraph` on a single line, with
  no blank line and no indentation.
- Every reference is a full `https://github.com/drevops/vortex/...` URL:
  the leading issue as `[#NNN](.../issues/NNN)` and the trailing pull
  request as `(.../pull/NNN)`. No bare `#NNN` remains.
- Pull request and issue lookups used `--repo drevops/vortex`.
- The `**Full Changelog**` URL targets `drevops/vortex-tooling`.
- No details go beyond the commit subject, linked issue, pull request body,
  or diff.

## Command rules - CRITICAL

Every Bash tool call must contain exactly ONE simple command. No `&&`,
`||`, `;`, `|`, command substitution `$(...)`, herestrings, or heredocs.
When you need several commands, make several separate Bash calls. This
applies to every `git` and `gh` invocation in this skill.
