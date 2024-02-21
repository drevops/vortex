# Twigcs

https://github.com/VincentLanglet/Twig-CS-Fixer

> The missing checkstyle for twig!
>
> Twigcs aims to be what phpcs is to php. It checks your codebase for violations on coding standards.

DrevOps comes with [pre-configured Twigcs ruleset](../../../../.twig-cs-fixer.php) for Drupal projects.

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

Twigcs does not support ignoring a file inline.

Twigcs does not support ignoring of the **code blocks**.

Twigcs does not support ignoring the current and the **next line**.

Twigcs supports ignoring violations for unused variables:

```twig
{% set bar = 1 %}
{# twigcs use-var bar #}
```

## Ignoring fail in CI

This tool runs in CI by default and fails the build if there are any violations.

Set `DREVOPS_CI_TWIGCS_IGNORE_FAILURE` environment variable to `1` to ignore
failures. The tool will still run and report violations, if any.
