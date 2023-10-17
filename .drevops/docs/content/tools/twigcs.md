# Twigcs

https://github.com/friendsoftwig/twigcs

> The missing checkstyle for twig!
>
> Twigcs aims to be what phpcs is to php. It checks your codebase for violations on coding standards.

DrevOps comes with [pre-configured Twigcs ruleset](../../../../.twig_cs.php) for Drupal projects.

## Usage

```shell
vendor/bin/twigcs
```
or
```shell
ahoy lint-be
```

## Configuration

See [configuration reference](https://github.com/friendsoftwig/twigcs#file-based-configuration).

All global configuration takes place in the [`.twig_cs.php`](../../../../.twig_cs.php) file.

Targets include custom modules and themes.

Adding or removing targets:
```php  hl_lines="6"
return Twigcs\Config\Config::create()
  ->setName('custom-config')
  ->setSeverity('error')
  ->setReporter('console')
  ->setRuleSet(Twigcs\Ruleset\Official::class)
  ->addFinder(Twigcs\Finder\TemplateFinder::create()->in(__DIR__ . '/web/themes/custom/mytheme'));
```

## Ignoring

Twigcs does not support ignoring a file inline.

Twigcs does not support ignoring of the **code blocks**.

Twigcs does not support ignoring the current and the **next line**.

Twigcs supports ignoring violations for unused variables:

```twig
{# twigcs use-var bar #}
{% set bar = 1 %}
```

## Ignoring fail in CI

This tool runs in CI by default and fails the build if there are any violations.

Set `DREVOPS_CI_TWIGCS_IGNORE_FAILURE` environment variable to `1` to ignore
failures. The tool will still run and report violations, if any.
