# Fix findings by tool

Each section covers 1 tool: what its configuration checks, what its fixer can and can't fix, how to fix the rest by hand, and how to run it alone on 1 file. Run every command from the project root, 1 command per Bash call.

## PHPCS

`phpcs.xml` applies the `Drupal`, `DrupalPractice`, `PHPCompatibility` and `DrevOps` standards, plus `Generic.PHP.RequireStrictTypes` and a Slevomat sniff that forbids spaces around `|` and `&` in type declarations. `PHPCompatibility` checks against the `testVersion` value, `8.4`. PHPCS checks `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. The `s` argument in the ruleset prints the sniff code after each message, and that code is the name to look up and to ignore. Warnings fail the check too: PHPCS exits with a non-zero code on any warning, and the ruleset reports 3 blank-line sniffs as warnings.

PHPCBF fixes the violations that the report marks with `[x]`: spacing, indentation, and array and comment formatting. It also renames local variables and parameters to snake_case for the `DrevOps.NamingConventions` sniffs, so search for callers that pass a renamed parameter by name.

Fix the rest by hand:

- `Drupal.Commenting.*`: write the missing docblock in the Drupal style, with a 1-line summary and `@param` and `@return` tags.
- `Generic.PHP.RequireStrictTypes.MissingDeclaration`: add `declare(strict_types=1);` after the file docblock, as the custom modules do.
- `DrupalPractice.Objects.GlobalDrupal`: inject the service through the constructor instead of calling `\Drupal::` inside a class.
- `DrevOps.TestingPractices.*`: name each data provider `dataProvider` followed by the test name without its `test` prefix, and place it right after its test.

Check or fix 1 file. A path on the command line replaces the `<file>` list in `phpcs.xml`:

```shell
ahoy cli vendor/bin/phpcs web/modules/custom/<module>/src/<File>.php
ahoy cli vendor/bin/phpcbf web/modules/custom/<module>/src/<File>.php
```

## PHPStan

`phpstan.neon` runs the analysis at level 7, with the Drupal rules from `mglaman/phpstan-drupal`, for PHP __VERSION__ (`phpVersion: __VERSION__`). It analyzes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. Its `ignoreErrors` list skips missing iterable value types in the custom modules, the custom theme and the tests, and possibly undefined variables in the settings includes.

PHPStan has no fixer. Fix each error by hand, using the identifier that PHPStan prints with the error:

- `missingType.iterableValue`: declare the value type in the docblock, such as `@param array<string, mixed> $options` or `@return string[]`.
- Errors about `mixed` or a nullable value: narrow the value with `instanceof`, `is_string()` or a native type declaration. `treatPhpDocTypesAsCertain` is `false`, so a runtime check stays valid when only a docblock declares the type.
- `\Drupal calls should be avoided in classes, use dependency injection instead`: inject the service through the constructor.
- `Ignored error pattern ... was not matched in reported errors`: the code no longer has that error, so remove the stale entry from `ignoreErrors`, or from the baseline as the next paragraph describes.

`ahoy lint-be-baseline` records the current errors in `phpstan-baseline.neon`. Use it only to onboard an existing codebase, and only when the developer asks for it, never to hide a new error. After fixing errors that a baseline records, run it again to shrink the baseline. See [Baseline for an existing codebase](https://www.vortextemplate.com/docs/development/code-quality/phpstan#baseline-for-an-existing-codebase).

Analyze 1 file. A path on the command line replaces the `paths` list:

```shell
ahoy cli vendor/bin/phpstan analyse web/modules/custom/<module>/src/<File>.php
```

## Rector

`rector.php` applies the PHP sets for the `require.php` constraint in `composer.json`, the Drupal deprecation sets for the installed `drupal/core` version, and the `codeQuality`, `codingStyle`, `deadCode`, `privatization` and `typeDeclarations` sets. Its `withSkip()` list skips 12 rules everywhere and 2 more for specific paths, each based on the project's coding standards. It processes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes` and `tests`, but not `scripts`.

Rector is its own fixer. A failed check means that Rector would change the code: it prints a diff for each file and exits with code `2`. Apply the changes, then read them like any other refactoring, because the `deadCode` and `typeDeclarations` sets change code, not only its formatting. Run the tests that cover the changed files. When a change is wrong for this project, skip that rule for that path in `withSkip()`, after the developer approves.

Check or fix 1 file:

```shell
ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php --dry-run
ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php
```

Rector caches its results. After changing `rector.php`, updating the Rector packages or switching to a branch with a different configuration, clear the cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`

## Twig CS Fixer

`.twig-cs-fixer.php` applies the `TwigCsFixer` standard with a 2-space indent to the Twig templates in `web/modules/custom` and the custom theme. `allowNonFixableRules()` turns on the rules that have no automatic fix, such as snake_case variable names. The configuration registers Drupal's `trans` tag parser, so `{% trans %}` blocks parse.

`lint --fix` fixes indentation, whitespace, spacing around delimiters and operators, quotes and trailing commas. Fix the non-fixable rules by hand. For a variable name, rename it where the template sets it with `{% set %}`, or in the theme hook's variables and the preprocess function that fills them.

Check or fix 1 template. A path on the command line replaces the directories in the configuration. `--debug` prints each error's identifier, which an ignore needs:

```shell
ahoy cli vendor/bin/twig-cs-fixer lint web/modules/custom/<module>/templates/<name>.html.twig
ahoy cli vendor/bin/twig-cs-fixer lint --fix web/modules/custom/<module>/templates/<name>.html.twig
ahoy cli vendor/bin/twig-cs-fixer lint --debug web/modules/custom/<module>/templates/<name>.html.twig
```

Results are cached in `.twig-cs-fixer.cache`, which Git ignores. Add `--no-cache` when the results look stale.

## ESLint

The root `eslint.config.mjs` mirrors Drupal core's configuration: the recommended presets of `@eslint/js`, `eslint-plugin-import-x`, `eslint-plugin-jsdoc`, `eslint-plugin-prettier` and `eslint-plugin-yml`, and every `eslint-plugin-no-jquery` rule. `no-console`, `prettier/prettier`, `operator-linebreak` and `yml/indent` are errors. `.prettierrc.json` sets a `printWidth` of 160, single quotes, trailing commas everywhere, and 80 columns for documentation blocks. The root `lint-js` script checks the JavaScript and YAML in `web/modules/custom` with `--max-warnings=0`, so warnings such as `no-unused-vars` fail it too.

`--fix` applies the Prettier formatting and the fixable rules. Fix the rest by hand:

- `no-console`: remove the call. Console calls print to every visitor's browser.
- `no-jquery/*`: replace the jQuery call with the DOM API, and attach behaviors with `once()`.
- `no-unused-vars`: remove the variable or use it.
- `jsdoc/*`: complete the documentation block.
- `no-undef` for a library global: the configuration declares `Drupal`, `drupalSettings`, `once`, `jQuery` and other Drupal globals. Add a new global to `languageOptions.globals` only after the developer approves.

Check or fix 1 file:

```shell
ahoy cli "npx eslint --max-warnings=0 web/modules/custom/<module>/js/<name>.js"
ahoy cli "npx eslint --fix web/modules/custom/<module>/js/<name>.js"
```

The custom theme has its own `eslint.config.mjs` and `.prettierrc.json` with the same rules. Its `lint-js` script checks `js/**/*.js` without the minified files, and omits `--max-warnings=0`, so warnings don't fail it. Check or fix the theme's JavaScript:

```shell
ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js"
ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js-fix"
```

## Stylelint

The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `web/modules/custom/**/*.css`.

The root `lint` script runs `lint-css` after `lint-js`, so a JavaScript failure hides the stylesheet results. Run Stylelint alone to see them.

`--fix` sorts declarations alphabetically and fixes the other fixable rules. Fix the rest by hand, such as unknown properties, invalid values and duplicate selectors.

Check or fix 1 file:

```shell
ahoy cli "npx stylelint web/modules/custom/<module>/css/<name>.css"
ahoy cli "npx stylelint --fix web/modules/custom/<module>/css/<name>.css"
```

The custom theme has its own `.stylelintrc.json`. It extends `stylelint-config-standard` and `stylelint-config-standard-scss`, skips the property order rule, and adds 2 rules that need a manual fix:

- `scss/at-extend-no-missing-placeholder`: turn the extended selector into a `%placeholder`.
- `scss/dollar-variable-pattern`: rename the variable to lowercase words separated by hyphens, such as `$base-font-size`.

Its scripts are `lint-css` and `lint-css-fix`, named unlike the root `lint-fix-css`. They check `scss/**/*.scss`:

```shell
ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-css"
ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-css-fix"
```

## Gherkin Lint

`ahoy lint-tests` runs `vendor/bin/gherkinlint lint tests/behat/features`. `gherkinlint.json` sets the indentation to 2 spaces per level and puts doc strings at level 3. It turns off `keyword-order`, `no-homogenous-tags` and `scenario-size`, and every other rule stays on. List the rules with `ahoy cli vendor/bin/gherkinlint rules`.

There is no fixer. Fix the feature file by hand:

- Indentation: `Feature:` at 0 spaces, `Background:`, `Scenario:`, `Scenario Outline:` and `Rule:` at 2, steps and `Examples:` at 4, and table rows and the opening `"""` of a doc string at 6.
- Duplicate feature or scenario names, empty scenarios, duplicate tags and trailing spaces: rename, fill or remove them.

Check 1 file:

```shell
ahoy cli vendor/bin/gherkinlint lint tests/behat/features/<name>.feature
```

## DCLint

The CI `lint` job runs the `zavoloklom/dclint` image on the project root, with the configuration in `.dclintrc`. Errors fail the job. Warnings, such as service keys out of order or unquoted ports, appear in the log without failing it. `.dclintrc` turns off 4 rules and sets `service-keys-order` to a warning with 8 groups of keys. Before linting, CI deletes the lines of `docker-compose.yml` that contain `###` and removes the `##` comment markers, so it checks the CI variant of the file.

The tool isn't in the `cli` container. Run it with Docker from the project root, using the image tag that the DCLint step in the CI configuration pins. `--fix` fixes the service key order and the port quotes:

```shell
docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> .
docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> --fix .
```

## Hadolint

The CI `lint` job pipes every file under `.docker/` that is named `Dockerfile` or ends in `.dockerfile` into the `hadolint/hadolint` image, with the default rules. Findings at the `info` severity or higher fail the job, and `style` findings appear in the log without failing it. The project has no Hadolint configuration file.

Hadolint has no fixer. Fix each finding by hand, such as pinning package versions or splitting a `RUN` instruction that ShellCheck flags. The template ignores the version pinning rules where the package set tracks the pinned base image.

The tool isn't in the `cli` container. Lint 1 Dockerfile per call with Docker, using the image tag that the Hadolint step in the CI configuration pins:

```shell
docker run --rm -i hadolint/hadolint:<tag> < .docker/<name>.dockerfile
```
