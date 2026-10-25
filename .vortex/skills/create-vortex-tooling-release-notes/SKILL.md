---
name: create-vortex-tooling-release-notes
description: Use when preparing release notes for the 'drevops/vortex-tooling' Composer package, published as a read-only mirror of '.vortex/tooling/' from the Vortex monorepo. Builds a tooling-only changelog from the previous release tag (or a commit) up to the current state, enriches each entry from its monorepo pull request and issue, and writes consumer-facing notes. Releases are manually gated and rarely align with Vortex releases, so the range is driven by the previous tooling tag, not by a Vortex tag. Triggers on phrases like 'tooling release notes', 'vortex-tooling release notes', 'release notes for vortex-tooling'.
user-invocable: true
---

# Create Vortex Tooling Release Notes

Generate consumer-facing release notes for the `drevops/vortex-tooling` Composer package.

`drevops/vortex-tooling` is a **read-only mirror** of the `.vortex/tooling/` directory in the `drevops/vortex` monorepo. A publishing workflow copies that directory to the mirror repository on every push to `main`, so the mirror's git history is mostly noise: it contains one commit per monorepo commit, including empty commits for monorepo commits that did not touch the tooling at all. Tooling releases are **manually gated** and almost never align with Vortex template releases, so "what changed since the last tooling release" cannot be read off a Vortex tag. This skill reconstructs that set reliably from the monorepo and turns it into release notes.

## When to use

- A new `drevops/vortex-tooling` release is about to be cut and needs notes.
- An existing tooling release has empty or missing notes that need backfilling.
- You want to see what tooling changes have accumulated since the last release.

Run it from inside a `drevops/vortex` monorepo clone with `git` and the GitHub CLI (`gh`) available and authenticated. It produces notes only; it does **not** tag, publish, or otherwise modify the `drevops/vortex-tooling` repository.

## How the reconciliation works

Every commit published to `drevops/vortex-tooling` records its origin in the commit body as `Source: drevops/vortex@<SHA>`. Each release tag therefore points back to an exact monorepo commit. That is the anchor: given the previous tooling release tag, resolve it to its source SHA, then list the monorepo commits from that SHA to now that actually changed shipped tooling files.

Only part of `.vortex/tooling/` ships to consumers. `tests/`, `playground/`, and the dotfiles are `export-ignore`d from the Composer archive; `src/`, `composer.json`, `README.md`, and `LICENSE` are what consumers receive. Filtering the log to the shipped paths is what removes both the empty mirror commits and every unrelated monorepo commit in one step.

## Inputs

The skill takes a **lower bound** and an optional **upper bound** that define the range. Each bound is a tooling release tag or a monorepo commit SHA.

| Bound | Default | Meaning |
|-------|---------|---------|
| Lower (`FROM`) | latest published `drevops/vortex-tooling` release tag | Start of the range - the previous release. |
| Upper (`TO`) | current `HEAD` | End of the range - the release being prepared. |

Two common shapes:

- **Next release** (the usual case): give only the lower bound, or nothing. The range is "previous tag to `HEAD`" and you choose the new version in Step 4.
- **Backfill** an already-published release: give both bounds as tags, for example `1.1.0` and `1.2.0`. The range is "1.1.0 to 1.2.0", and the new version is simply the upper tag - nothing to choose.

## Process

### Step 1: Resolve the range bounds to monorepo source SHAs

Resolve the lower bound to `FROM_SHA` and the upper bound to `TO_SHA`. The rule is the same for each bound:

- **A tag** (including the latest release tag when no lower bound was given - find it with `gh release list -R drevops/vortex-tooling --limit 1`): read its source SHA from the mirror commit body.

  ```bash
  gh api repos/drevops/vortex-tooling/commits/1.2.0 --jq '.commit.message'
  ```

  Parse the `Source: drevops/vortex@<SHA>` line from the output; that `<SHA>` is the bound.
- **A commit SHA**: use it directly.
- **No upper bound given**: `TO_SHA` is `HEAD`.

Confirm each resolved SHA exists locally:

```bash
git cat-file -t <SHA>
```

If `git cat-file` fails, the local clone is behind; run `git fetch origin` and retry, and stop with a clear message if it still cannot be found.

### Step 2: Build the tooling-only changelog

List the monorepo commits between `FROM_SHA` and `TO_SHA` that changed a shipped tooling file, newest first:

```bash
git log <FROM_SHA>..<TO_SHA> --no-merges --pretty=format:'%H%x09%s' -- .vortex/tooling/ ':(exclude).vortex/tooling/tests' ':(exclude).vortex/tooling/playground' ':(exclude).vortex/tooling/.gitattributes' ':(exclude).vortex/tooling/.gitignore'
```

The path filter is the heart of the skill: it keeps only commits that touched files consumers actually receive, automatically discarding the empty mirror commits and every non-tooling monorepo commit.

If the command returns nothing, there is nothing to release. Tell the user "No tooling changes shipped since `<lower-bound>`; no release needed" and stop. Do not write an empty notes file.

Each output line is a commit: a full SHA, a tab, then the subject. The subject already carries the entry shape these notes use - an optional `[#ISSUE]` prefix and a trailing `(#PR)` reference, for example:

```
f8e1361b…	[#2643] Hardened the host-side database-download tooling scripts. (#2648)
```

### Step 3: Enrich each entry from its pull request

For each commit, you need enough context to write an accurate paragraph. **The pull requests and issues live in `drevops/vortex`, never in `drevops/vortex-tooling`** (the mirror has none). Follow this order and stop as soon as you have enough:

1. **Use the subject** if it is already specific enough. Skip the fetch entirely in that case.
2. **Fetch the pull request** named by the trailing `(#NNN)`:

   ```bash
   gh pr view 2648 --repo drevops/vortex --json title,body,labels,author
   ```

   Use `author.login` for the `@author` attribution.
3. **Fetch the linked issue** named by a `[#NNN]` prefix when the pull request body is still ambiguous:

   ```bash
   gh issue view 2643 --repo drevops/vortex --json title,body
   ```

4. **Inspect the change itself** only when the descriptions are uninformative. The actual diff is in the monorepo, so read it locally rather than over the API:

   ```bash
   git show <SHA> -- .vortex/tooling/src
   ```

   Read only enough to understand intent. New or renamed `VORTEX_*` environment variables, changed defaults, and added or removed scripts are the signals that matter most.

Batch independent `gh` calls in parallel - issue several Bash tool calls in a single message, targeting 8-10 at a time, so a release with many entries resolves in a few waves rather than one call at a time. Pull request bodies often contain auto-generated review-bot sections; skim past them and rely on the human-written summary.

If `gh` is unavailable or auth fails, fall back to writing conservative paragraphs from the commit subjects alone, and tell the user which entries lack deep context so they can review them.

### Step 4: Determine the version

`PREVIOUS_VERSION` is the lower-bound tag (or the latest release tag when a bare commit was given).

- **Backfill** (the upper bound is an existing tag): `NEW_VERSION` is that upper tag - nothing to choose.
- **Next release** (the upper bound is `HEAD` or a bare commit): suggest `NEW_VERSION` from the nature of the changes, then **confirm it with the user** before writing. The version only labels the notes; no tag is created.

- **Major** (e.g. `2.0.0`): a shipped script was removed or renamed; an environment variable consumers set was removed or renamed; a default behaviour changed in a way that breaks existing usage; the PHP requirement in `composer.json` was raised in a breaking way.
- **Minor** (e.g. `1.3.0`): a new script or a new opt-in capability, flag, or environment variable was added; new behaviour that existing projects are unaffected by.
- **Patch** (e.g. `1.2.1`): a bug fix, a hardening or robustness change, an internal refactor with no contract change, or documentation fixes.

### Step 5: Write and display the notes

Write the file to `.artifacts/release-notes-tooling-<NEW_VERSION>.md` (for example `release-notes-tooling-1.2.1.md`) using the structure and rules below, then display its full contents to the user in a fenced `markdown` code block for review.

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

- **Breaking changes**: include only if at least one entry is breaking; omit the heading entirely otherwise. An entry is breaking when consumers upgrading may have to change their own setup, or when observable behaviour changes automatically on upgrade. The tooling's public surface is the **script contract**: script names under `src/`, the environment variables they read, their arguments, their observable side effects, and the `composer.json` platform requirement. An opt-out flag makes a breaking change recoverable, not non-breaking - still list it and explain the escape hatch in the migration step.
- **Highlights**: pick the 5-7 most consumer-relevant entries, favouring new scripts and new capabilities over internal hardening, refactors, and doc fixes. Omit the heading if the release is too small to have meaningful highlights. Every highlight must also appear under All changes - it is a curated view, not a separate set.
- **All changes**: list every entry from Step 2, newest first, preserving the commit subject text verbatim. Do not reword, reorder, merge, or drop entries.

### Entries

- **No hand-wrapping.** Every bullet, including its paragraph, is a **single line** in the file regardless of length. GitHub renders the markdown - let the browser wrap. (This is the opposite of how this SKILL file itself is written; the deliverable is renderer-bound, the skill is source.) Tables and code fences keep their structure.
- Format exactly: wrap the original subject in `**...**`, immediately follow it with `<br>`, then the paragraph on the same line - no blank line between them, no indentation.
- **Render every issue and pull-request reference as a full `drevops/vortex` URL**, never a bare `#NNN` - the notes are published to the `drevops/vortex-tooling` mirror, where a bare `#NNN` mis-links to that repository's own numbering. Transform the commit subject so the leading `[#NNN]` issue reference becomes the markdown link `[#NNN](https://github.com/drevops/vortex/issues/NNN)`, and the trailing `(#NNN)` pull-request reference becomes the bare URL `(https://github.com/drevops/vortex/pull/NNN)`. When a commit has no `[#NNN]` issue prefix, start with the subject text; when it has no `(#NNN)` pull-request reference (a direct push), omit the trailing reference and enrich from the commit and its diff. Keep the `@author` handle (a global GitHub mention resolves correctly anywhere) and the subject's punctuation and capitalisation.
- Paragraphs are 1-3 sentences of plain prose. For fixes: name what was broken, the user-visible symptom, and the corrected behaviour. For features: name what you can now do that you could not before, and the environment variable or flag that controls it with its default. Never invent details the source does not support.
- Entries authored by dependency bots (`@renovate[bot]`, `@dependabot[bot]`) or whose subject begins with `Update dependency`, `Update all dependencies`, or `Bump ` stay as bare list items with no bold and no paragraph. These are rare here, since the package only requires PHP.
- Do not insert blank lines between consecutive list items.

### Footer

The `**Full Changelog**` line points at the **mirror** repository, `drevops/vortex-tooling`, using `PREVIOUS_VERSION...NEW_VERSION`. The link resolves once the `NEW_VERSION` tag is later created on that repository.

## Validation checklist

Before displaying the output, verify:

- File saved to `.artifacts/release-notes-tooling-<NEW_VERSION>.md`.
- First line is `## What's new since <PREVIOUS_VERSION>`.
- `### Breaking changes` appears only if there is at least one breaking entry.
- `### All changes` lists every commit from Step 2, verbatim, newest first.
- Every non-bot entry is `**subject**<br>paragraph` on a single line, no blank line, no indentation.
- Every reference is a full `https://github.com/drevops/vortex/...` URL - the leading issue as `[#NNN](.../issues/NNN)`, the trailing pull request as `(.../pull/NNN)`. No bare `#NNN` remains.
- Pull request and issue lookups used `--repo drevops/vortex`.
- The `**Full Changelog**` URL targets `drevops/vortex-tooling`.
- No invented details beyond the commit subject, linked issue, pull request body, or diff.

## Command rules - CRITICAL

Every Bash tool call must contain exactly ONE simple command. No `&&`, `||`, `;`, `|`, command substitution `$(...)`, herestrings, or heredocs. When you need several commands, make several separate Bash calls. This applies to every `git` and `gh` invocation above.
