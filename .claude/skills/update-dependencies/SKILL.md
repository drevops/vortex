---
name: update-dependencies
description: Use when updating Drupal core, contributed modules or other Composer or npm packages, checking which packages are outdated, adding, re-rolling or removing a patch, acting on a security advisory that a dependency audit reports, or reviewing or fixing an automated dependency update pull request.
---

# Update dependencies

Update packages so that the site still builds, provisions and passes its checks afterwards. Composer manages Drupal core, the contributed modules and the PHP libraries. npm manages the front-end tooling.

Run every command from the project root, through `ahoy`, 1 command per Bash call. `ahoy composer`, `ahoy drush` and `ahoy cli` run inside the `cli` container and hand their arguments to a second shell, so ordinary quotes are lost. Escape the quotes of an argument that holds spaces as `\"`, and write version constraints without spaces. See [Composer](https://www.vortextemplate.com/docs/development/composer) and [Dependency audit](https://www.vortextemplate.com/docs/development/security/dependency-audit).

## Rules

- Start from a clean tree: `git status --short`. Keep each update in its own commit, apart from feature work.
- Never edit files under `vendor/`, `web/core/`, `web/libraries/` or the `contrib` directories. Composer replaces them on the next install. Change them with a patch instead.
- Update to new minor and patch releases by default. Move a package to a new major version only when the developer asks for it.
- Leave the `php` constraint and `config.platform.php` in `composer.json` alone. They follow the PHP version of the container images.
- Drush answers its own confirmation prompts with "yes" when no terminal is attached. Run `updatedb` and the configuration commands against the local site only.
- `ahoy update-vortex` updates the template's own files. This skill doesn't cover it.

## Find what is outdated

- The packages that `composer.json` requires: `ahoy composer outdated --direct`
- Only 1 kind of release: add `--patch-only`, `--minor-only` or `--major-only`.
- 1 vendor: `ahoy composer outdated "drupal/*" --direct`
- Why a version can't be installed: `ahoy composer why-not drupal/core-recommended <version>`
- Which package pulls in an indirect dependency: `ahoy composer why <vendor/package>`

Read the release notes of every package you update. Name to the developer each release that mentions database updates, configuration changes, a new minimum Drupal or PHP version, or a security fix.

## Update Composer packages

Know what the constraints in `composer.json` allow before you update:

- `drupal/core-recommended` and `drupal/core-composer-scaffold` use a `~` constraint, such as `~11.4.12`, which accepts patch releases of that minor version only.
- Most other packages use a `^` constraint, which accepts minor and patch releases.
- A constraint with a stability flag, such as `@alpha` or `@RC`, accepts that pre-release until a stable release exists. `minimum-stability` is `stable` and `prefer-stable` is `true`.
- `bump-after-update` is `true`, so every update rewrites the constraints in `composer.json` to the installed versions. Review that diff.
- `config.policy.advisories.block` is `false`, so a security advisory never blocks an update. Run the audit afterwards.

Preview any update by adding `--dry-run`. Then run the update:

- 1 package and its dependencies: `ahoy composer update <vendor/package> --with-dependencies`
- Drupal core within its constraint: `ahoy composer update "drupal/core-*" --with-dependencies`
- Drupal core to a new minor version: check with `ahoy composer why-not drupal/core-recommended <version>` first, then run `ahoy composer require drupal/core-recommended:~<version> drupal/core-composer-scaffold:~<version> --update-with-all-dependencies`
- A package to a new major version: `ahoy composer require <vendor/package>:^<major> --update-with-dependencies`
- Every package within its constraints: `ahoy composer update`. Prefer targeted updates, which keep the change reviewable.
- The `drevops/vortex-tooling` package uses a `~` constraint too: `ahoy composer update drevops/vortex-tooling` installs its patch releases. Raise its constraint only when the developer asks for it.

`pyrech/composer-changelogs` prints a summary of the changed packages after each update. Use it in your report.

After a core update, review the scaffold files that it changed, such as `web/index.php` and `web/sites/default/default.settings.php`, with `git diff`. The project keeps `web/.htaccess` out of scaffolding, so port changes to `.htaccess` from the core release notes by hand.

After editing `composer.json` by hand, run `ahoy composer validate --strict` and `ahoy composer normalize`. The CI `lint` job checks both.

## Patches

`cweagans/composer-patches` 2.x applies patches with `git apply`:

- The patch list is `extra.patches` in `composer.json`, keyed by package name, with a description as each key and a URL or a local path as each value.
- Local patch files live in `patches/`, such as `patches/<package>-<description>.patch`. The `cli` image copies the directory before it runs `composer install`.
- `patches.lock.json` records each patch with a checksum. Commit it with `composer.lock`. Composer applies the patches from this file whenever it exists, so after any change to `extra.patches`, run `ahoy composer patches-relock`, or the change has no effect.

Add a patch:

1. Add the entry to `extra.patches`.
2. `ahoy composer patches-relock`
3. `ahoy composer patches-repatch`. It deletes every patched package and installs it again, so local edits in those directories are lost.
4. `ahoy composer update --lock`
5. Confirm that the output of step 3 has a `- Patching <vendor/package>` line.

When an update stops with `No available patcher was able to apply patch <url> to <vendor/package>`, read [references/patches.md](references/patches.md). It covers removing a patch whose fix landed upstream and re-rolling a patch for the new version. `ahoy composer patches-doctor` checks that the patching environment works.

## Run database updates and export configuration

1. List the pending updates: `ahoy drush updatedb:status`
2. Apply them: `ahoy drush updatedb`
3. Check the configuration: `ahoy drush config:status`

When step 3 reports differences that weren't there before the update, an update hook changed the active configuration. Export those changes with the `manage-config` skill, and commit them with the update. `ahoy provision` and every deployment run `drush config:import` right after `drush updatedb`, so an update hook's change that isn't exported is overwritten on every environment. With `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE=1` in `.env`, provisioning fails on such a change instead.

## Audit the packages

- Run `ahoy composer audit` after every update. The CI audit workflow runs `composer audit --locked`.
- The policy in `composer.json` doesn't block installs on advisories, fails the audit on any advisory, and reports abandoned packages without failing.
- Fix an advisory by updating the affected package. For an indirect dependency, find its parent with `ahoy composer why <vendor/package>`, then update the parent with `--with-dependencies`, or with `--with-all-dependencies` when a root requirement holds it back.
- Ignore an advisory only after the developer assessed it: add its ID under `config.policy.advisories.ignore-id` in `composer.json`, with the reason as the value, and repeat the reason in the commit message. The audit then lists it under `Found 1 ignored security vulnerability advisory`.
- When the advisory needs a change in custom code, follow the `secure-code` skill.

[//]: # (#;< TOOL_NPM)

## Update the root npm packages

The root `package.json` holds the linters and the test runner for the custom modules. Node.js and npm come from the `cli` container, so leave the `engines` versions alone.

- See what is outdated: `ahoy cli "npm outdated"`. It exits with a non-zero code when any package is outdated.
- Update within the ranges in `package.json`: `ahoy cli "npm update"`. It rewrites `package-lock.json`.
- Move a package beyond its range: `ahoy cli "npm install --save-dev <package>@<version>"`
- Audit: `ahoy cli "npm audit"`. `.npmrc` sets `audit-level=high`. CI applies the allowlist in `package-audit.jsonc` through `audit-ci`, which you run as `ahoy cli "npx --yes audit-ci --high --extra-args=--package-lock-only --config package-audit.jsonc"`.
- Fix an advisory in an indirect dependency by pinning the fixed version in the `overrides` object of `package.json`, then run `ahoy cli "npm install"`. Add an `allowlist` entry to `package-audit.jsonc`, with `notes` and an `expiry` date, only when no fixed version exists and the developer assessed the advisory.
- Confirm that the lock file matches `package.json`: `ahoy fei`. It runs `npm ci`, which fails on a mismatch.

Commit `package.json` and `package-lock.json` together.

[//]: # (#;> TOOL_NPM)

[//]: # (#;< DRUPAL_THEME)

## Update the custom theme's npm packages

The custom theme has its own `package.json`, `package-lock.json`, `.npmrc` and `package-audit.jsonc` in `${WEBROOT}/themes/custom/${DRUPAL_THEME}`. Run the npm commands for it with `--prefix`:

- `ahoy cli "npm outdated --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
- `ahoy cli "npm update --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
- `ahoy cli "npm install --save-dev <package>@<version> --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
- `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`

The theme's `postinstall` script runs `patch-package`, which applies the theme's npm patches from its own `patches/` directory. It exits with code `0` outside CI even when a patch fails, so read the output of `ahoy fei` for patch errors. To re-create a patch, edit the package's files under the theme's `node_modules/`, then run `ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} postinstall -- <package>"`.

After the update, run `ahoy fei` to install from the lock files, and `ahoy fe` to rebuild the theme's assets.

[//]: # (#;> DRUPAL_THEME)

[//]: # (#;< DEPS_UPDATE_PROVIDER)

## Automated update pull requests

Renovate opens update pull requests from branches with the `deps/` prefix, labeled `Dependencies`. Security fixes also carry the `Security` label. Read [references/renovate.md](references/renovate.md) for the groups, schedules and delays in `renovate.json`, and for what Renovate leaves to be updated by hand.

To review or fix a Renovate pull request:

1. With the pull request's `deps/` branch checked out, install its packages: `ahoy composer install`
2. Run [Verify the update](#verify-the-update) on it.
3. Renovate changes only the package files. When the update needs a re-rolled patch or exported configuration, commit that to the same branch.

Renovate doesn't run database updates or export configuration. The CI build provisions the site, which imports the configuration right after the updates run, so an update hook's configuration change is lost without failing the build, unless `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE` is `1`. Check every update that ships update hooks with `ahoy drush config:status`, as [Run database updates and export configuration](#run-database-updates-and-export-configuration) describes.

[//]: # (#;> DEPS_UPDATE_PROVIDER)

[//]: # (#;< DEPS_UPDATE_PROVIDER_CI)

Renovate runs as a self-hosted job in CI, from `.github/workflows/update-dependencies.yml` on GitHub Actions. It needs the `RENOVATE_TOKEN` secret and the `RENOVATE_GIT_AUTHOR` variable, and skips its run with a `Skipping job.` message when either is missing. Its scheduled runs fall outside the schedule windows in `renovate.json`, so they open no pull requests for the update groups. Start a run by hand with the workflow's manual trigger, which ignores the schedules unless its `preserve_schedules` option is selected.

[//]: # (#;< CI_PROVIDER_CIRCLECI)

On CircleCI, the job is in `.circleci/update-dependencies.yml` and also needs `RENOVATE_REPOSITORIES`. Start it by hand with the `run_update_dependencies` pipeline parameter set to `true`.

[//]: # (#;> CI_PROVIDER_CIRCLECI)

[//]: # (#;> DEPS_UPDATE_PROVIDER_CI)

[//]: # (#;< DEPS_UPDATE_PROVIDER_APP)

Renovate runs as the hosted Renovate GitHub app. To raise security fixes, the app needs read access to Dependabot alerts, which the repository owner checks under **Settings > GitHub Apps**.

[//]: # (#;> DEPS_UPDATE_PROVIDER_APP)

## Verify the update

1. Review the changed files: `git status --short`. Expect `composer.json`, `composer.lock`, `patches.lock.json`, the npm manifests and lock files, and exported configuration in `config/`.
2. `ahoy composer validate --strict`
3. `ahoy composer audit`
4. Ask the developer, then provision the way a deployment does: `AHOY_CONFIRM_RESPONSE=y ahoy provision`. It replaces the local database when `VORTEX_PROVISION_OVERRIDE_DB=1` is set. It runs the database updates, the configuration import and the deploy hooks.
5. Confirm that `ahoy drush config:status` prints `No differences between DB and sync directory.`
6. Run `ahoy lint` and fix what it reports.
7. Run every test suite that the project has.

[//]: # (#;< TOOL_PHPUNIT)

- `ahoy test`

[//]: # (#;> TOOL_PHPUNIT)

[//]: # (#;< TOOL_BEHAT)

- `ahoy test-bdd`, which needs the provisioned site

[//]: # (#;> TOOL_BEHAT)

[//]: # (#;< TOOL_JEST)

- `ahoy test-js`

[//]: # (#;> TOOL_JEST)

[//]: # (#;< TOOL_RECTOR)

Rector resolves its Drupal sets from the installed `drupal/core` version, so a core update can bring new findings. After updating the Rector packages, clear its cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`

[//]: # (#;> TOOL_RECTOR)

Rebuild the stack with `ahoy build` only when the update changes the container images and the developer agrees, because it removes the containers and the database. When the stack or provisioning fails, use the `set-up-environment` skill. When the site shows errors after the update, use the `debug-errors` skill.

Report to the developer every package that changed with its old and new version, each patch you added, re-rolled or removed, the configuration you exported, and any advisory that remains.
