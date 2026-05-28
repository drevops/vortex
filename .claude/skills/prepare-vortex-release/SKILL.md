---
name: prepare-vortex-release
description: Prepare the Vortex codebase for a release - run checklist operations (deps, container images, PHP version, cache bumps, docs) and generate release notes at .artifacts/release-VERSION/release-notes.md. Stops at a pushed feature branch; does NOT open the PR. Triggered by phrases like 'prepare vortex release', 'vortex release prep', 'run vortex release checklist'.
user-invocable: true
---

# Vortex Release Preparation

When this skill is triggered, prepare a new release of the Vortex template.
The user will provide the target version number (e.g., `1.37.0`).

## Step 1: Read the release process

Read `.vortex/docs/content/contributing/maintenance/release.mdx` for the
official checklist and `.vortex/docs/content/contributing/maintenance/_release_template.md`
for the release notes template.

## Step 2: Determine the previous release

Run `git tag --sort=-creatordate` to find the most recent tag (check the first few entries).
This is the `PREVIOUS_VERSION`. Use `git log --oneline PREVIOUS_VERSION..HEAD`
to get the exact commit range for this release.

## Step 3: Run release checklist operations

Work through each checklist item from the release process doc:

1. **Dependencies** - Skip Renovate (user must run manually). Note as unchecked.
2. **Container images** - Check current versions in CI configs, verify if latest.
3. **PHP version** - Run `docker compose run --rm cli php -r "echo PHP_VERSION;"` and
   `docker compose run --rm cli php -r "echo PHP_VERSION_ID;"` to get the container
   PHP version. Update `composer.json` (`config.platform.php`), `phpstan.neon`
   (`phpVersion`), and `phpcs.xml` (`testVersion`) if needed.
4. **Composer packages** - Run `composer update -W` then check composer.json was bumped
   (the project uses `bump-after-update` config).
5. **Theme dependencies** - Run `yarn upgrade` in `web/themes/custom/your_site_theme/`.
   Use yarn, NOT npm.
6. **CI runner** - Check if `drevops/ci-runner` is at the latest version.
7. **Cache version** - The cache key has the form `v<YY>.<M>.<minor>-db<DRUPAL_MAJOR>`.
   Update both parts as follows in `.circleci/config.yml`,
   `.circleci/vortex-test-common.yml`, and `.github/workflows/build-test-deploy.yml`:
   - **`v<YY>.<M>.<minor>` prefix** - tracks the latest `uselagoon/*` container
     image tag (e.g. `v26.4.0` for `uselagoon/mysql-8.4:26.4.0`). Lagoon
     versions encode `YY.M.minor` (year, calendar month, minor), so this part
     effectively follows the current month's release. Bump it whenever the
     Lagoon containers were bumped during this release cycle.
   - **`db<N>` suffix** - tracks the *Drupal core major version* (e.g. `db11`
     for Drupal 11). It does NOT increment per release. Only change it when
     the project moves between Drupal majors (e.g. `db10` → `db11`).
   - Examples:
     - Lagoon containers went `26.2.0` → `26.4.0`, still on Drupal 11:
       `v26.2.0-db11` → `v26.4.0-db11`.
     - Lagoon stayed at `26.4.0`, project moved to Drupal 12:
       `v26.4.0-db11` → `v26.4.0-db12`.
   - Do NOT increment the `db` suffix to "force a cache reset". That is the
     job of changing the `v...` prefix. The `db<N>` suffix is a Drupal-major
     marker, not a counter.
8. **Documentation** - Run `cd .vortex && ahoy update-docs`. WARNING: this may
   revert cache version changes in CI configs - re-apply the cache version
   increment after running this command.
9. **Demo videos** - Regenerate every terminal demo video shown in the docs
   via a single command, in a single temp workspace.

   ```bash
   cd .vortex
   ahoy update-videos
   ```

   - Runs `ahoy build` exactly once per invocation in a throwaway temp dir.
     When `build` is in the requested set (default), it is recorded;
     otherwise it runs silently so the remaining commands have a built
     project to work against.
   - To re-record a subset, pass the names:
     `ahoy update-videos installer build lint`. Allowed names: `installer`,
     `build`, `provision`, `lint`, `test`, `test-bdd`. Default is all six.
   - Heavy step: ~15-20 minutes wall-clock when running all six; requires
     Docker. `ahoy update-videos installer` is fast (no Docker).
   - The command does NOT auto-commit; review the artifact diff under
     `.vortex/docs/static/img/` and stage manually.

## Step 4: Generate release notes

The release-prep flow uses a single staging directory per release: `.artifacts/release-<full-version>/` (e.g. `.artifacts/release-1.38.0/`). Everything for that release lives inside this folder so reviewers have one place to look.

Create the directory if it does not already exist:

```bash
mkdir -p .artifacts/release-<full-version>
```

Then copy the release template into the directory and fill it in at:

```text
.artifacts/release-<full-version>/release-notes.md
```

This file is the **raw release body** (no frontmatter). It is the source for the GitHub release body when the tag is published. Marketing copies (with Obsidian frontmatter, etc.) are produced as separate files in the same folder by the parent `release-vortex` workflow - do NOT add frontmatter here.

**Do NOT create `.artifacts/release-<full-version>.md` at the top level.** The previous convention of writing a single top-level `.artifacts/release-VERSION.md` has been retired. Everything lives inside the folder. If a stale top-level file is present from an earlier run, delete it.

### Gathering change details

For each non-trivial commit in the range:
- Use `git show --stat HASH` to see files changed
- Use `git show HASH -- path/to/key/file` to understand the actual change
- Use `gh pr list --state merged --limit 50 --json number,title,author` to get
  PR numbers and authors

### Release notes format

Follow this exact format (see `.artifacts/release-1.38.0/release-notes.md` from the most recent release as the canonical example):

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
 - [#ISSUE] Title from commit/PR. @author (#PR_NUMBER)
    Description paragraph explaining what changed and why it matters to users.

- 🛠 **Changed**
 - ...

- 🐞 **Fixed**
 - ...

- ⬆️ **Updated**
 - Title. @author (#PR_NUMBER)
 - Title. @[renovate[bot]](https://github.com/apps/renovate) (#PR_NUMBER)

---

### 🎛 Installer

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

Each changelog entry follows this pattern. **The title line, the `What it does:` label, and the `How to use it:` label must all be wrapped in markdown bold (`**...**`).** Do not bold the body text after the labels.

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

(Renovate Updated-section entries: bold the title only - they have no `What it does` / `How to use it` body.)

Key rules:
- The first line is the PR title with issue reference, author attribution, and PR number, wrapped in `**...**`.
- The body must contain **two pieces of information** for any non-trivial entry:
  1. **What the change does** (the behaviour, the new feature, the bug that was fixed).
  2. **How to start using it** *or* **what effect it has on existing projects**.
     - If the feature is opt-in or configurable: name the **environment variable / setting / flag** that turns it on, with default value (e.g. `VORTEX_PROVISION_CONFIG_IMPORT_REPEAT=1`, default `0`).
     - If the feature is opt-out: name the disable variable and its default.
     - If the feature applies automatically: state explicitly that no action is needed and what changes for existing projects.
     - If the change is a fix: explain who was affected and whether their setup needs anything (e.g. "automatically applied on next provision; no configuration needed").
     - For breaking changes: list the migration steps explicitly.
- Read the actual diff to find the variable names. Do NOT guess. If `git show <hash>` shows new `VORTEX_*`, `DRUPAL_*`, or `*_SKIP` variables in `scripts/vortex/*.sh` or `web/sites/default/**/settings.*.php`, those are the variables to surface. Cross-reference `.vortex/docs/content/development/variables.mdx` for canonical naming.
- Short fix entries that are purely internal (refactoring, test infrastructure, doc typos) may omit the "how to use" line.
- Renovate bot entries in the Updated section do NOT need description paragraphs.
- Author is `@GithubUsername` for humans, `@[renovate[bot]](https://github.com/apps/renovate)` for the bot.
- PR number in parentheses: `(#2349)`.
- Issue number in brackets at start: `[#2340]` (only if the commit message has one).
- **Verify package names against `composer.json`** before naming third-party libraries (e.g. `drevops/behat-screenshot`, NOT `bex/behat-screenshot`). Run a quick `grep` check on the entry's package references.

### Highlights selection rules

The `## 🔍 Highlights` section is the part most readers will actually read. It must surface what users of Vortex (the people running Drupal projects on top of the template) actually benefit from. Choose 5-8 items, prioritising in this order:

1. **Security / hardening** that applies automatically to every project (e.g. `.htaccess` blocks, default-on settings).
2. **New testing or CI capabilities** that are practical to adopt (Jest, new test job, new lint).
3. **New default modules / packages** that change what ships out of the box.
4. **New deployment / operational levers** (manual triggers, per-channel routing).
5. **Configurable behaviour changes** that improve reliability (e.g. opt-in repeat config import, fallback behaviour fixes).
6. **Renovate / dependency-management improvements** that change how updates land.
7. **Runtime / platform bumps** when they are breaking (PHP major.minor, container major bumps).

DO NOT highlight:
- Installer-only conveniences (e.g. installer flags) unless they unblock a category of users.
- Internal CI tweaks that have no consumer-facing effect.
- Pure refactors.
- Single-vendor integrations that most users won't reach for (e.g. an optional Codecov prompt).

If you are tempted to put an installer flag or an optional integration in highlights, ask: "Does the average Vortex consumer project benefit from this on day 1?". If no, move it down to its respective category section.

### Categorisation

- **Template**: Changes to the project template files (scripts, configs, CI, modules, theme)
- **Installer**: Changes to `.vortex/installer/src/` code and installer-specific tests
- **Documentation**: Changes to `.vortex/docs/` content

Within each category:
- **New**: New features, capabilities, or additions
- **Changed**: Refactors, behaviour changes, improvements (non-breaking)
- **Fixed**: Bug fixes
- **Updated**: Dependency version bumps (group Renovate bot updates here)

### Important

- Many commits touch installer **fixtures** because template changes cascade into them.
  Only list changes in the Installer section if they modify actual installer **source code**
  (`.vortex/installer/src/`) or installer-specific test logic.
- Check for commits that touch both template and installer source - list the template
  change under Template and the installer-specific change under Installer.

## Command rules - CRITICAL

NEVER use compound commands. Every Bash call must contain exactly ONE simple command.
No `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, or heredocs.
