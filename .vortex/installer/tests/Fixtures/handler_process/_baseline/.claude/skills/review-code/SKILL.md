---
name: review-code
description: Use when asked to review code, review the current branch, check changes before opening a pull request, or review a GitHub pull request from its URL or number.
---

# Review code

Review a change the way a senior developer on this project would: against
the project's own rules first, then the team checklist, then general
engineering judgment.

The review is read-only. Never edit the project's files, check out
branches, push, or post to GitHub. Writing to the git-ignored `.artifacts/`
directory, such as the documentation cache `AGENTS.md` describes, is
allowed. Print the report in the conversation and stop.

Run every command from the project root.

## 1. Get the diff

**Pull request** (a URL, or a number such as `123`). Drop a leading `#`
from a number first, because the shell reads `#` as the start of a
comment.

1. `gh pr view <pr> --json number,title,body,url,state,headRefOid`
2. `gh pr diff <pr>`

Pass the full URL as `<pr>` whenever the developer gave one, so `gh` reads
the right repository. To read a changed file in full at the PR head, take
`<owner>/<repo>` from the PR URL and run
`gh api -H "Accept: application/vnd.github.raw" "repos/<owner>/<repo>/contents/<path>?ref=<headRefOid>"`.
When `git cat-file -e <headRefOid>` succeeds, `git show <headRefOid>:<path>`
reads the same content locally. Never read a PR file from the working
tree, which can hold uncommitted edits.

When `state` is `MERGED` or `CLOSED`, say so at the top of the report,
leave out the verdict, and present the findings as follow-ups.

When the pull request belongs to another repository, the rules in step 2
come from that repository: read its `AGENTS.md`, `CLAUDE.md` and
contributing docs with `gh api`, and skip `checklist.md`.

**Local branch** (no argument):

1. Find the base: the branch the developer names, otherwise the default
   branch from `git symbolic-ref --short refs/remotes/origin/HEAD`
   without its `origin/` prefix. When that command fails, use
   `gh repo view --json defaultBranchRef --jq .defaultBranchRef.name`.
2. `git fetch origin <base>`
3. `git merge-base origin/<base> HEAD` and copy the hash it prints.
4. `git diff <hash>` - this includes uncommitted changes.
5. `git status --short` - read untracked files as new files.
6. `git log --oneline origin/<base>..HEAD` for the intent of the change.

When the base does not resolve, or both the diff and the untracked files
are empty, reply with 1 line that says which and stop. When the developer expected changes, also run
`git status --short --ignored` and name any ignored files that match.

## 2. Load the rules

Apply them in this order. An earlier source wins over a later one.

1. `AGENTS.md`, any `AGENTS.md` or `CLAUDE.md` in a directory the change
   touches, and the files in `docs/` - the decisions this project made.
   When they allow something the checklist would flag, do not flag it.
2. `checklist.md` in this directory - the team's review checklist.
3. General judgment: correctness, security, data loss, error handling,
   and missing tests.

Skip anything the project's linters already enforce: `ahoy lint` and any
other lint command that `AGENTS.md` lists. Flag a change that disables or
bypasses a linter.

## 3. Review

- Read each changed file in full, not only the diff hunks. Open the
  callers, services, routes and config that the change relies on.
- Spot-check generated files, such as lock files and test fixtures,
  instead of reading them in full.
- Check every finding against the code before reporting it. Drop it when
  the code already handles the case.
- A claim that cannot be checked from the code goes in **Questions**, not
  in a severity section.

## 4. Report

Use this format and leave out empty sections:

```markdown
## Review: <PR title, or branch name>

**Verdict:** Ready to merge | Ready after fixes | Needs work

### Critical
- `path/to/file.php:__VERSION__` - What is wrong. Why it matters. How to fix it.

### Important
- ...

### Minor
- ...

### Questions
- Things the author needs to confirm.
```

Point each finding at `path:line`. Use the path alone when the finding is
about something missing, such as a test.

| Severity  | Use for                                                                                  |
|-----------|------------------------------------------------------------------------------------------|
| Critical  | Security holes, data loss, broken behavior, a change that fails the build or deployment |
| Important | Bugs in edge cases, missing tests for changed behavior, checklist violations             |
| Minor     | Readability and maintainability. Report at most 5.                                       |

| Verdict           | When                                                         |
|-------------------|--------------------------------------------------------------|
| Needs work        | Any Critical finding, or a question that blocks the merge    |
| Ready after fixes | Important findings and no Critical ones                      |
| Ready to merge    | Only Minor findings, non-blocking questions, or no findings |

When there are findings, end by noting that fixing them or posting them to
the pull request is a separate request. The review itself does neither.

## Customizing

- Change what the review checks: edit `checklist.md`.
- Change a project rule: edit `AGENTS.md` or `docs/`. The review reads
  them on every run.
- Change the procedure or the report format: edit this file.
