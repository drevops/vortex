---
name: prepare-vortex-release
description: Prepare the Vortex codebase for a release - run checklist operations (deps, container images, PHP version, cache bumps, docs) and generate release notes at .artifacts/release-VERSION/release-notes.md. Stops at a pushed feature branch; does NOT open the PR. Triggered by phrases like 'prepare vortex release', 'vortex release prep', 'run vortex release checklist'.
user-invocable: true
---

# Vortex Release Preparation

When this skill is triggered, prepare a new release of the Vortex template.
The user provides the target version number, for example `1.37.0`.

## Step 1: Read the release process

Read `.vortex/docs/content/contributing/maintenance/release.mdx` for the
official checklist. Read
`.vortex/docs/content/contributing/maintenance/_release_template.md` for the
release notes template.

## Step 2: Determine the previous release

Run `git tag --sort=-creatordate` to find the most recent tag; check the
first few entries. This tag is the `PREVIOUS_VERSION`. Run
`git log --oneline PREVIOUS_VERSION..HEAD` to get the exact commit range
for this release.

## Step 3: Check pending deprecations

The codebase marks time-limited backward-compatibility shims with a
`@deprecated` tag. The tag names the **Vortex** version that removes the
shim, for example `@deprecated ... will be removed in Vortex 1.40.`. Before
preparing a release, find every deprecation whose target version is at or
below the version being released, and confirm it's resolved.

1. List every deprecation marker and its target version:

   ```bash
   grep -rn "@deprecated" --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git --exclude-dir=.artifacts .
   ```

2. For each marker, read the target removal version (the `Vortex X.Y` it
   names) and compare it with the version being released:
   - **Target version is greater than the release version** - the shim is
     still in its grace period. Leave it in place, and note it in the
     release checklist as a known upcoming removal.
   - **Target version is at or below the release version** - the removal
     is due. The deprecated code, its tests, and any related docs MUST be
     removed before the release.

3. If any deprecation is due, STOP and prompt the user before continuing:
   - List each due deprecation with its file and line.
   - Ask whether to resolve it now (remove the deprecated code path, its
     tests, and any `@deprecated` notes or docs) or to defer the release.
   - Do NOT silently proceed past a due deprecation.

4. After you resolve the due deprecations, or the user explicitly defers
   them, re-run the grep. Confirm nothing at or below the release version
   remains.

Record the outcome (resolved, deferred, or none found) in the release
checklist section of the release notes.

## Step 4: Run release checklist operations

Work through each checklist item from the release process doc:

1. **Dependencies** - Skip Renovate; the user must run it manually. Note
   the item as unchecked.
2. **Container images** - Check the current versions in the CI configs,
   and check whether each is the latest.
   - **CI tool images** - `hadolint`, `dclint`, `gitleaks`, and
     `actionlint` run as `docker run <image>:<tag>` inside
     `.github/workflows/**` and `.circleci/config.yml`. The
     `customManagers` regex in `renovate.json` tracks them, so this step
     verifies; it doesn't bump. Confirm that no open Renovate PR is bumping
     them, and that both CI providers carry the same tag for the same tool.
     Bump by hand only when Renovate hasn't picked a release up. In that
     case, pin the identical tag in `.vortex/tests/lint.dockerfiles.sh` so
     a local run matches CI.
   - An untagged image reference is a release blocker, regardless of
     Renovate. It resolves to `latest`, so an upstream release can break a
     default branch on a commit that changed nothing.
3. **PHP version** - Get the container PHP version with these 2 commands:
   `docker compose run --rm cli php -r "echo PHP_VERSION;"` and
   `docker compose run --rm cli php -r "echo PHP_VERSION_ID;"`. If needed,
   update `composer.json` (`config.platform.php`), `phpstan.neon`
   (`phpVersion`), and `phpcs.xml` (`testVersion`).
4. **Composer packages** - Run `composer update -W`, then check that
   `composer.json` was bumped. The project uses the `bump-after-update`
   config.
   - **`drevops/vortex-tooling` version pin (update EVERY spot in
     lockstep).** When `.vortex/tooling/` changed and a new
     `drevops/vortex-tooling` tag is published for this release, the
     pinned version MUST be updated in **all** of these spots at once:
     1. The `require` constraint in root `composer.json`.
     2. The path-repository `versions` override in root `composer.json`.
     3. The hardcoded `versions` override in `scripts/vortex-tooling.sh`,
        inside the `#;< VORTEX_DEV` throwaway path-repo config.
     4. The `versions` override in
        `.vortex/tests/phpunit/Traits/SutTrait.php`, the test-harness
        `.tooling-source` path repo.

     Missing a spot breaks resolution and fails CI. The dev and CI
     `require-tooling` bootstrap becomes unresolvable, and the
     workflow-test SUT Docker build hits a higher-priority path repo that
     no longer matches the `~<new>` constraint.

     **After bumping, grep the whole repo for the previous tooling
     version**, for example
     `grep -rn "vortex-tooling.*<old-version>" --exclude-dir=vendor`. This
     grep is the authoritative check, since new pin spots get added over
     time.
5. **Theme dependencies** - Run `npm update` in
   `web/themes/custom/your_site_theme/`. Use npm, NOT yarn.
6. **CI runner PHP** - Make sure the CI runner PHP matches
   `config.platform.php` (see item 3). Check the `cimg/php` tag in
   `.circleci/config.yml` and the `setup-php` `php-version` in the GitHub
   Actions workflows.
7. **Cache version** - The cache key has the form `v<YY>.<M>.<minor>`
   (CalVer). Update it in `.circleci/config.yml`,
   `.circleci/vortex-test-common.yml`, and
   `.github/workflows/build-test-deploy.yml`:
   - The `v<YY>.<M>.<minor>` prefix tracks the latest `uselagoon/*`
     container image tag, for example `v26.4.0` for
     `uselagoon/mysql-8.4:26.4.0`. Lagoon versions encode `YY.M.minor`
     (year, calendar month, minor), so this part follows the current
     month's release. Bump it whenever the Lagoon containers were bumped
     during this release cycle.
   - Example: Lagoon containers went `26.2.0` → `26.4.0`:
     `v26.2.0` → `v26.4.0`.
   - To force a cache reset without a Lagoon bump, bump the last segment,
     for example `v26.4.0` → `v26.4.1`.
8. **Documentation** - Run `cd .vortex && ahoy update-docs`. WARNING: this
   command may revert cache version changes in CI configs. Re-apply the
   cache version increment after running it.
9. **Demo videos** - Regenerate every terminal demo video shown in the
   docs with a single command, in a single temp workspace.

   ```bash
   cd .vortex
   ahoy update-videos
   ```

   - The command runs `ahoy build` exactly once per invocation, in a
     throwaway temp dir. When `build` is in the requested set (the
     default), the build is recorded. Otherwise it runs silently, so the
     remaining commands have a built project to work against.
   - To re-record a subset, pass the names:
     `ahoy update-videos cli-install build lint`. Allowed names:
     `cli-install`, `build`, `provision`, `lint`, `test`, `test-bdd`,
     `info`, `doctor`, and `doctor-info`. The default is all of them.
   - This step is heavy: ~15-20 minutes wall-clock for the full set, and
     it requires Docker. `ahoy update-videos cli-install` is fast and
     doesn't need Docker.
   - The command does NOT auto-commit. Review the artifact diff under
     `.vortex/docs/static/img/` and stage it manually.
   - A recording whose output reports an error, a warning, or a failure
     isn't rendered, and the command stops and names the offending lines.
     Read them in `.artifacts/videos/<name>.txt`, fix the command that
     emits them, and record again. No flag renders the video anyway.

## Step 5: Generate release notes

The release-prep flow uses a single staging directory per release:
`.artifacts/release-<full-version>/`, for example
`.artifacts/release-1.38.0/`. Everything for that release lives inside this
folder, so reviewers have 1 place to look.

Create the directory if it doesn't exist:

```bash
mkdir -p .artifacts/release-<full-version>
```

Then copy the release template into the directory and fill it in at:

```text
.artifacts/release-<full-version>/release-notes.md
```

This file is the **raw release body**, with no frontmatter. It's the
source for the GitHub release body when the tag is published. The parent
`release-vortex` workflow produces marketing copies (with Obsidian
frontmatter and similar) as separate files in the same folder. Do NOT add
frontmatter here.

**Do NOT create `.artifacts/release-<full-version>.md` at the top level.**
Everything lives inside the folder. If a stale top-level
`.artifacts/release-VERSION.md` from an earlier run is present, delete it.

### Gathering change details

For each non-trivial commit in the range:

- Use `git show --stat HASH` to see the files changed.
- Use `git show HASH -- path/to/key/file` to understand the actual change.
- Use `gh pr list --state merged --limit 50 --json number,title,author` to
  get PR numbers and authors.

### Release notes format

Follow this exact format. The canonical example is
`.artifacts/release-1.38.0/release-notes.md` from the most recent release.

```markdown
## VERSION - Short Title

Summary paragraph (1-3 sentences).

---

## 🔍 Highlights

- **Highlight Title**
  Description paragraph.

---

## 💥 Breaking changes

- Description of breaking change.

---

## What's new since PREVIOUS_VERSION

### 🌀 Template

- ✨ **New**

  - **[#ISSUE] Title from commit/PR. @author (#PR_NUMBER)**
     **What it does:** ...
     **How to use it:** ...

  - **[#ISSUE] Next entry. @author (#PR_NUMBER)**
     **What it does:** ...
     **How to use it:** ...

- 🛠 **Changed**

  - ...

- 🐞 **Fixed**

  - ...

- ⬆️ **Updated**

  - **Title. @author (#PR_NUMBER)**

  - **Title. @[renovate[bot]](https://github.com/apps/renovate) (#PR_NUMBER)**

---

### 🎛 CLI

(same sub-sections)

---

### 📖 Documentation

(same sub-sections)

---

## 📋 Release checklist

- [x] or [ ] each item with current status notes in parentheses

---

**Full Changelog**: https://github.com/drevops/vortex/compare/PREVIOUS_VERSION...NEW_VERSION

@username, @renovate[bot] and [renovate[bot]](https://github.com/apps/renovate)
```

### Entry format rules

Each changelog entry follows this pattern. **The title line, the
`What it does:` label, and the `How to use it:` label must all be wrapped
in markdown bold (`**...**`).** Don't bold the body text after the labels.

```markdown
 - **[#ISSUE] PR/commit title. @author (#PR_NUMBER)**
    **What it does:** <one or two sentences>.
    **How to use it:** <variable name(s), default value, or upgrade impact>.
```

Concrete examples:

```markdown
- **[#2394] Added Jest for JavaScript unit testing. @username (#2418)**
   **What it does:** Adds Jest as the JavaScript unit test runner, with coverage thresholds and CI integration matching the PHP test setup.
   **How to use it:** Run `ahoy test-js` locally; CI runs it automatically. Place tests next to the JS source under `web/modules/custom/*/tests/`.

- **Update PHP - All packages except core - Minor and patch. @[renovate[bot]](https://github.com/apps/renovate) (#2474)**
```

Renovate entries in the Updated section bold the title only. They have no
`What it does` or `How to use it` body.

Key rules:

- **Blank lines are mandatory.** Put a blank line after every group header
  (`- ✨ **New**`, `- 🛠 **Changed**`, `- 🐞 **Fixed**`,
  `- ⬆️ **Updated**`). Put one between every pair of entries in a group,
  including single-line entries in the Updated section. Without them, the
  notes render as an unbroken wall of text that nobody reads. The
  `What it does:` and `How to use it:` lines of an entry stay tight against
  their title line, with no blank line between them. The blank lines go
  between entries, not inside one.
- The first line is the PR title with the issue reference, author
  attribution, and PR number, wrapped in `**...**`.
- The body of any non-trivial entry must contain **2 pieces of
  information**:
  1. **What the change does**: the behavior, the new feature, or the bug
     that was fixed.
  2. **How to start using it** *or* **what effect it has on existing
     projects**.
     - If the feature is opt-in or configurable: name the **environment
       variable, setting, or flag** that turns it on, with its default
       value. For example `VORTEX_PROVISION_CONFIG_IMPORT_REPEAT=1`,
       default `0`.
     - If the feature is opt-out: name the disable variable and its
       default.
     - If the feature applies automatically: state explicitly that no
       action is needed, and what changes for existing projects.
     - If the change is a fix: explain who was affected and whether their
       setup needs anything, for example "automatically applied on next
       provision; no configuration needed".
     - For breaking changes: list the migration steps explicitly.
- Read the actual diff to find the variable names. Do NOT guess. If
  `git show <hash>` shows new `VORTEX_*`, `DRUPAL_*`, or `*_SKIP`
  variables in `.vortex/tooling/src/vortex-*` or
  `web/sites/default/**/settings.*.php`, surface those variables.
  Cross-reference `.vortex/docs/content/development/variables.mdx` for
  canonical naming.
- Short fix entries that are purely internal (refactoring, test
  infrastructure, doc typos) may omit the "how to use" line.
- Renovate bot entries in the Updated section do NOT need description
  paragraphs.
- The author is `@GithubUsername` for humans and
  `@[renovate[bot]](https://github.com/apps/renovate)` for the bot.
- The PR number goes in parentheses: `(#2349)`.
- The issue number goes in brackets at the start: `[#2340]`, only if the
  commit message has one.
- **Verify package names against `composer.json`** before naming
  third-party libraries, for example `drevops/behat-screenshot`, NOT
  `bex/behat-screenshot`. Run a quick `grep` on the entry's package
  references.

### Highlights selection rules

The `## 🔍 Highlights` section is the part most readers read. It must
surface what Vortex users benefit from: the people running Drupal projects
on top of the template. Choose 5-8 items, in this priority order:

1. **Security or hardening** that applies automatically to every project,
   for example `.htaccess` blocks or default-on settings.
2. **New testing or CI capabilities** that are practical to adopt: Jest, a
   new test job, or a new lint.
3. **New default modules or packages** that change what ships out of the
   box.
4. **New deployment or operational levers**: manual triggers or
   per-channel routing.
5. **Configurable behavior changes** that improve reliability, for example
   an opt-in repeat config import or fallback behavior fixes.
6. **Renovate or dependency-management improvements** that change how
   updates land.
7. **Runtime or platform bumps** when they're breaking: a PHP major.minor
   or a container major bump.

DO NOT highlight:

- CLI-only conveniences, such as CLI flags, unless they unblock a category
  of users.
- Internal CI tweaks with no consumer-facing effect.
- Pure refactors.
- Single-vendor integrations that most users won't reach for, for example
  an optional Codecov prompt.

Before you put a CLI flag or an optional integration in highlights, ask:
"Does the average Vortex consumer project benefit from this on day 1?". If
the answer is no, move it to its category section.

### Categorisation

- **Template**: changes to the project template files (scripts, configs,
  CI, modules, theme).
- **CLI**: changes to `.vortex/cli/src/` code and CLI-specific tests.
- **Documentation**: changes to `.vortex/docs/` content.

Within each category:

- **New**: new features, capabilities, or additions.
- **Changed**: refactors, behavior changes, and improvements
  (non-breaking).
- **Fixed**: bug fixes.
- **Updated**: dependency version bumps. Group Renovate bot updates here.

### Important

- Many commits touch CLI **fixtures**, because template changes cascade
  into them. List a change in the CLI section only if it modifies actual
  CLI **source code** (`.vortex/cli/src/`) or CLI-specific test logic.
- Check for commits that touch both template and CLI source. List the
  template change under Template and the CLI-specific change under CLI.

## Command rules - CRITICAL

NEVER use compound commands. Every Bash call must contain exactly ONE
simple command. No `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, or heredocs.
