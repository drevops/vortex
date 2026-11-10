---
name: fix-lint
description: Use when `ahoy lint` or the lint job in CI fails, when asked to fix lint, coding standard, static analysis or formatting errors, or before committing a change so that it passes the project's linters.
---

# Fix lint

Make `ahoy lint` pass by fixing the code. Change a linter's configuration or add an ignore only for a finding that can't be fixed, and only as [Ignore a rule](#ignore-a-rule) describes.

Run every command from the project root, through `ahoy`, 1 command per Bash call. `ahoy cli` runs its arguments through a second shell inside the `cli` container, so escape the quotes of an argument that holds spaces with a backslash, as `\"`. Each tool's page under [Code quality](https://www.vortextemplate.com/docs/development/code-quality) documents its configuration in full.

## Rules

- Never edit files under `vendor/`, `web/core/` or the `contrib` directories. The linters don't check them, and Composer replaces them.
- Run `git status --short` before running a fixer, so the fixer's changes can be told apart from the developer's own.
- Review every change a fixer makes with `git diff` before reporting.
- Never set a `VORTEX_CI_*_IGNORE_FAILURE` CI variable to get a passing build. Those variables are a one-off bypass that the developer decides on.
- Don't lower a rule's level, remove a rule or exclude a path in a linter configuration without the developer's approval.

## Where each tool runs

`ahoy lint` runs the check commands below in order, and `ahoy lint-fix` runs the fix commands.

- PHPCS runs first in `ahoy lint-be`. Its fixer, PHPCBF, runs last in `ahoy lint-be-fix`.

- PHPStan runs in `ahoy lint-be`. It has no fixer.

- Rector runs last in `ahoy lint-be`, as a dry run. `ahoy lint-be-fix` runs it first and applies its changes.

- Twig CS Fixer runs first in `ahoy lint-fe`, and with `--fix` in `ahoy lint-fe-fix`.

- ESLint runs in `ahoy lint-fe` through the `lint-js` script of the root `package.json`, and in `ahoy lint-fe-fix` through `lint-fix-js`.

- ESLint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.

- Stylelint runs in `ahoy lint-fe` through the `lint-css` script of the root `package.json`, and in `ahoy lint-fe-fix` through `lint-fix-css`.

- Stylelint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.

- Gherkin Lint runs in `ahoy lint-tests`. It has no fixer, so `ahoy lint-fix` leaves feature files alone.

- DCLint runs only in the CI `lint` job. The project doesn't install it.

- Hadolint runs only in the CI `lint` job. The project doesn't install it.

`ahoy lint-sdc` validates the custom theme's components against a provisioned site. `ahoy lint` doesn't run it.

Every Ahoy command runs its steps under `bash -e`, so it stops at the first tool that fails, and the tools after it report nothing until it passes. A fixer that leaves problems it can't fix also exits with a non-zero code, which stops `ahoy lint-fix` before the remaining fixers run. Run `ahoy lint-fix` again after fixing those problems by hand.

The CI `lint` job also runs `composer validate --strict` and `composer normalize --dry-run`, which `ahoy lint` doesn't. Run them as `ahoy composer validate --strict` and `ahoy composer normalize --dry-run`, and fix the formatting of `composer.json` with `ahoy composer normalize`.

## Procedure

1. Record the starting state: `git status --short`
2. Apply the automatic fixes: `ahoy lint-fix`
3. Check: `ahoy lint`. Find the tool that failed: it is the last one that printed output before the command stopped.
4. Fix each remaining finding by hand. Read the failing tool's section in [references/tools.md](references/tools.md): it covers the tool's configuration, what its fixer can't fix, how to fix that by hand and how to run the tool alone on 1 file. Re-run the tool on the changed file until it passes.
5. Repeat steps 2 to 4 until `ahoy lint` exits with code `0`. The Bash tool reports the exit code.
6. Review the result: `git diff`

When the change touches `docker-compose.yml`, also run DCLint as its section in [references/tools.md](references/tools.md) describes.

When the change touches a file in `.docker/`, also run Hadolint as its section in [references/tools.md](references/tools.md) describes.

Report to the developer what the fixers changed, what you fixed by hand and every ignore you added, with its reason.

## Ignore a rule

Fix the finding first. When it is a false positive, or the fix would make the code worse, ask the developer before ignoring it. Then ignore it at the narrowest scope the tool supports: 1 line before 1 file, 1 file before a path in the configuration, and a path before the whole project.

The template's rule for every ignore, in a configuration file or inline: name the exact rule it silences, and record why next to it. The shipped configuration files put a comment above each exclusion that says why. Never add an ignore that silences every rule in a line or a file.

Read [references/ignore-rules.md](references/ignore-rules.md) for each tool's ignore syntax and where its reason goes.
