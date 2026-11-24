# Renovate configuration

`renovate.json` extends `config:recommended`. Each rule in its `packageRules` list sets a group, a schedule in UTC, a minimum release age and an `automerge` flag. See [Dependency updates](https://www.vortextemplate.com/docs/development/dependency-updates) for every setting.

## Update groups

| Group | Packages | Update types | Schedule | Minimum release age |
|---|---|---|---|---|
| Drupal core | `drupal/core-recommended`, `drupal/core-composer-scaffold`, `drupal/core-project-message` and `drupal/core-dev` | Minor, patch | Daily before 2 AM | None |
| PHP packages except core | Every other package in `composer.json` | Minor, patch | Sunday before 2 AM | 14 days |
| JavaScript packages | The root `package.json` | Minor, patch | Sunday before 2 AM | 14 days |
| Container images | `.docker/`, `docker-compose.yml`, and the images that CI starts with `docker run` | Major, minor, patch | Daily before 3 AM | 14 days |
| CI tools | npm packages that CI runs at a pinned version through `npx --yes` | Major, minor, patch | Daily before 3 AM | 14 days |
| Renovate image | `.circleci/update-dependencies.yml` | Major, minor, patch | Sunday before 3 AM | 14 days |
| GitHub Actions | The actions in `.github/workflows/`, pinned to digests | Major, minor, patch | Daily before 3 AM | 14 days |

Drupal.org records no release dates, so contributed Drupal modules arrive without the 14-day delay. Security fixes skip the delay, the schedule and the limit of 10 open pull requests, when Renovate can read the repository's advisories.

Check each group's `automerge` flag in `renovate.json` before you merge by hand: the installer sets it per group, and a group with `true` merges as soon as the branch protection rules pass.

## Constraints

`rangeStrategy` is `bump`, so Renovate raises the constraints in `composer.json` and `package.json` to each new release, as Composer's `bump-after-update` does. A constraint that ends in `@dev`, `@alpha`, `@beta`, `@RC` or `@stable` gets `update-lockfile` instead, so Renovate changes only the lock file while the new release fits.

## What Renovate leaves to you

These rules are disabled, so update the packages by hand with this skill:

- Major versions of every package in `composer.json`, Drupal core included.
- Major versions of every package in the root `package.json`.
- The `php` constraint in `composer.json`, and the `node` and `npm` versions in `package.json`.
- Every `package.json` outside the project root, such as the custom theme's.
- Indirect dependencies that a security alert flags.
