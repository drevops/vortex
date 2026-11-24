# Ignore rules

Each section shows how 1 tool ignores a rule, from the narrowest scope to the widest, and where the reason goes. Ask the developer before adding any of them, and name every ignore you added in the report.

[//]: # (#;< TOOL_PHPCS)

## PHPCS

Use the sniff code that the report prints after the message. PHPCS reads the text after ` -- ` in an annotation as a note, so the reason goes there.

- The next line: `// phpcs:ignore Drupal.Commenting.FunctionComment.Missing -- <reason>` on the line before it. At the end of a line, the same comment applies to that line.
- A block: `// phpcs:disable <sniff code> -- <reason>` before it and `// phpcs:enable <sniff code>` after it.
- A whole file: `// phpcs:disable <sniff code> -- <reason>` in the file header. Never use `// phpcs:ignoreFile`, which silences every sniff.
- Some files or the whole project: a `<rule ref="...">` entry in `phpcs.xml` with `<exclude-pattern>` lines, and an XML comment above it that says why, as the shipped entries have:

```xml
<!-- <reason>. -->
<rule ref="DrupalPractice.General.ClassName.ClassPrefix">
    <exclude-pattern>*\/path\/to\/dir\/*\.php</exclude-pattern>
</rule>
```

[//]: # (#;> TOOL_PHPCS)

[//]: # (#;< TOOL_PHPSTAN)

## PHPStan

PHPStan ignores 1 line at a time and has no block ignore. Use the identifier that PHPStan prints with the error, and put the reason in parentheses after it.

- The next line: `// @phpstan-ignore variable.undefined (<reason>)` on the line before it. At the end of a line, the same comment applies to that line. Don't use `@phpstan-ignore-next-line`, which silences every error on the line.
- Some files or the whole project: an `ignoreErrors` entry in `phpstan.neon`, with a comment that says why, a `message` regular expression or an `identifier`, and `paths` to limit it, as the shipped entries have:

```yaml
parameters:
  ignoreErrors:
    - # <reason>.
      identifier: missingType.iterableValue
      paths:
        - web/modules/custom/<module>/src/<File>.php
```

To leave a file out of the analysis, add it to `excludePaths`. The baseline, `phpstan-baseline.neon`, is for onboarding an existing codebase, not for ignoring a new error.

[//]: # (#;> TOOL_PHPSTAN)

[//]: # (#;< TOOL_RECTOR)

## Rector

Rector has no inline ignore. Skip a rule for specific paths in `withSkip()` in `rector.php`, with a comment above it that says why. Skip a rule for the whole project only when the project's coding standards reject it, as the shipped list does.

```php
->withSkip([
  // <reason>.
  SimplifyIfReturnBoolRector::class => [
    __DIR__ . '/web/modules/custom/<module>/src/<File>.php',
  ],
])
```

[//]: # (#;> TOOL_RECTOR)

[//]: # (#;< TOOL_TWIG_CS_FIXER)

## Twig CS Fixer

Run `ahoy cli vendor/bin/twig-cs-fixer lint --debug <path>` to get the error identifier, such as `VariableName.Error`. The directives take no reason, so write the reason in a Twig comment on the line above the directive.

- The next line: `{# twig-cs-fixer-disable-next-line VariableName.Error #}` on the line before it.
- 1 line: `{# twig-cs-fixer-disable-line VariableName.Error #}` at the end of it.
- A whole file: `{# twig-cs-fixer-disable VariableName.Error #}` in the file header. Without an identifier, the comment silences every rule, so always name one.
- A directory: `$finder->exclude('<directory>');` in `.twig-cs-fixer.php`, with a comment above it that says why.

[//]: # (#;> TOOL_TWIG_CS_FIXER)

[//]: # (#;< TOOL_ESLINT)

## ESLint

ESLint reads the text after ` -- ` in a directive as its description, so the reason goes there.

- The next line: `// eslint-disable-next-line no-console -- <reason>`
- 1 line: `// eslint-disable-line no-console -- <reason>` at the end of it.
- A block: `/* eslint-disable no-console -- <reason> */` before it and `/* eslint-enable no-console */` after it.
- A whole file: `/* eslint-disable no-console -- <reason> */` in the file header. Never use `/* eslint-disable */` without a rule name.
- Paths: the `ignores` list in `eslint.config.mjs`, with a comment that says why.

[//]: # (#;> TOOL_ESLINT)

[//]: # (#;< TOOL_STYLELINT)

## Stylelint

Stylelint reads the text after ` -- ` in a directive as its description, so the reason goes there.

- The next line: `/* stylelint-disable-next-line declaration-no-important -- <reason> */`
- 1 line: `/* stylelint-disable-line declaration-no-important -- <reason> */` at the end of it.
- A block: `/* stylelint-disable declaration-no-important -- <reason> */` before it and `/* stylelint-enable declaration-no-important */` after it.
- Files: the `ignoreFiles` key of the configuration file. Never use `/* stylelint-disable */` without a rule name.

[//]: # (#;> TOOL_STYLELINT)

[//]: # (#;< TOOL_BEHAT)

## Gherkin Lint

Gherkin Lint has no line or block ignore. A `# @gherkinlint-disable-rule <rule>, <rule>` comment before the `Feature:` line turns the listed rules off for the whole file. Write the reason in a separate comment line above it. To turn a rule off for every file, set `enabled` to `false` for it in `gherkinlint.json`.

[//]: # (#;> TOOL_BEHAT)

[//]: # (#;< TOOL_DCLINT)

## DCLint

Put the rule name after `disable-line`, and the reason after a second `#`:

- 1 line: `# dclint disable-line <rule> # <reason>` on its own line right before the line, or at the end of it.
- A whole file: `# dclint disable <rule>` as the first line of the file. Without a rule name, the comment turns off every rule.
- The whole project: set the rule to `0` in `.dclintrc`.

[//]: # (#;> TOOL_DCLINT)

[//]: # (#;< TOOL_HADOLINT)

## Hadolint

Put the rule codes after `ignore=`, and the reason after a second `#`, as the Dockerfiles in `.docker/` do:

```Dockerfile
# hadolint ignore=DL3018 # the package set tracks the pinned base image
RUN apk add --no-cache tzdata
```

- The next instruction: an `ignore=` directive on the line before it.
- A whole file: `# hadolint global ignore=<codes>` in the file header, with the reason in a comment line above it.

[//]: # (#;> TOOL_HADOLINT)
