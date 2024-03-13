# Twig CS Fixer

https://github.com/VincentLanglet/Twig-CS-Fixer

> The missing checkstyle for twig!
>
> Twig CS Fixer aims to be what phpcs is to php. It checks your codebase for violations on coding standards.

DrevOps comes with [pre-configured Twig-cs-fixer ruleset](../../../../.twig-cs-fixer.php) for Drupal projects.

## Usage

```shell
vendor/bin/twig-cs-fixer
```
or
```shell
ahoy lint-be
```

## Configuration

See [configuration reference](https://github.com/VincentLanglet/Twig-CS-Fixer/blob/main/docs/configuration.md).

All global configuration takes place in the [`.twig-cs-fixer.php`](../../../../.twig-cs-fixer.php) file.

Targets include custom modules and themes.

Adding or removing targets:
```php  hl_lines="12"
$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\Twig());

$finder = new TwigCsFixer\File\Finder();
$finder->in(__DIR__ . '/web/modules/custom');
$finder->in(__DIR__ . '/web/themes/custom');

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);

return $config;
```



## Ignoring

All errors have an identifier with the syntax: `A.B:C:D` with
- A: The rule short name (mainly made from the class name)
- B: The error identifier (like the error level or a specific name)
- C: The line the error occurs
- D: The position of the token in the line the error occurs

NB: The four parts are optional, all those format are working
- A
- A.B
- A.B:C
- A.B:C:D
- A:C
- A:C:D
- A::D

When you want to disable a rule, you can use of the following syntax:
```twig
{# twig-cs-fixer-disable A.B:C:D #} => Apply to the whole file
{# twig-cs-fixer-disable-line A.B:C:D #} => Apply to the line of the comment
{# twig-cs-fixer-disable-next-line A.B:C:D #} => Apply to the next line of the comment
```

For instance:

```twig
{# twig-cs-fixer-disable #} => Disable every rule for the whole file
{# twig-cs-fixer-disable-line #} => Disable every rule for the current line
{# twig-cs-fixer-disable-next-line #} => Disable every rule for the next line

{# twig-cs-fixer-disable DelimiterSpacing #} => Disable the rule 'DelimiterSpacing' for the whole file
{# twig-cs-fixer-disable DelimiterSpacing:2 #} => Disable the rule 'DelimiterSpacing' for the line 2 of the file
{# twig-cs-fixer-disable-line DelimiterSpacing.After #} => Disable the error 'After' of the rule 'DelimiterSpacing' for the current line
{# twig-cs-fixer-disable-next-line DelimiterSpacing::1 #} => Disable the rule 'DelimiterSpacing' for the next line but only for the token 1
```

You can also disable multiple errors with a single comment, by separating them
with a space or a comma:
```twig
{# twig-cs-fixer-disable DelimiterSpacing OperatorNameSpacing #} => Disable OperatorNameSpacing and OperatorNameSpacing for the whole file
{# twig-cs-fixer-disable-line DelimiterSpacing.Before,OperatorNameSpacing.After #} => Disable DelimiterSpacing.Before and OperatorNameSpacing.After for the current line
```

If you need to know the errors identifier you have/want to ignore, you can run the
linter command with the `--debug` options.

## Ignoring fail in CI

This tool runs in CI by default and fails the build if there are any violations.

Set `DREVOPS_CI_TWIG_CS_FIXER_IGNORE_FAILURE` environment variable to `1` to ignore
failures. The tool will still run and report violations, if any.
