<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=customizer&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="customizer logo"></a>
</p>

<h1 align="center">Customizer</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/drevops/tui.svg)](https://github.com/drevops/tui/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/drevops/tui.svg)](https://github.com/drevops/tui/pulls)
[![Test PHP](https://github.com/drevops/tui/actions/workflows/test-php.yml/badge.svg)](https://github.com/drevops/tui/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/drevops/tui/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/drevops/tui)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/tui)
![LICENSE](https://img.shields.io/github/license/drevops/tui)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

A dependency-light PHP engine for building **panel-based terminal customizers**: interactive, keyboard-driven questionnaires that collect answers and hand them to your code. Describe the questions in YAML, add a handler class wherever a question needs real behaviour, and the engine renders a scrollable, themeable TUI - or runs headless from a JSON payload.

It powers the [Vortex](https://www.vortextemplate.com) project installer, but knows nothing about Vortex: the engine is generic, and the project-specific questions and handlers live in the consumer.

## Features

- **Config-driven** - panels and fields are declared in YAML; the common cases need no code.
- **Every widget type** - text, single-select, multi-select (type-to-filter), autocomplete-with-fallback, and yes/no.
- **Interactive or headless** - drive the panel TUI by keyboard, or collect answers non-interactively from a JSON payload (and emit a JSON schema for agents and forms).
- **Derived values** - compute one field from others with [str2name](https://github.com/AlexSkrypnyk/str2name) transforms; chains settle to a fixpoint.
- **Conditional fields** - show or hide fields with `when` rules; a fix-up pass reconciles dependent answers.
- **Discovery** - detect sensible defaults from the target directory (`.env` keys, JSON paths, path existence, directory scans).
- **Handlers** - attach validation, transforms, dynamic defaults and side effects by dropping in a class named after the field id.
- **Themeable** - the whole visual representation (colours, glyphs, layout) is a theme class; ships with dark and light, and a config can name a custom theme class directly.

## Installation

    composer require drevops/tui

## Quick start

The `Customizer` facade is the one class you need - it wires the config loader, engine, resolver, schema tools and TUI for you:

```php
use DrevOps\Tui\Customizer;

$customizer = Customizer::fromFiles(['config.yml'], ['App\\Handler']);

// Headless: collect answers from a JSON payload (and the environment).
echo $customizer->collect('{"name":"Ada"}')->toJson();

// Interactive: drive the panel TUI instead.
$answers = $customizer->run();
```

It also exposes `schema()`, `agentHelp()` and `validate()`, and - when you want finer control - the internals via `config()`, `engine()` and `registry()`. See [`playground/`](playground) for complete, runnable examples.

## Configuration

A config is a tree of panels, each holding fields:

```yaml
title: 'My customizer'
panels:
  - id: general
    title: 'General'
    fields:
      - id: name
        label: 'Project name'
        type: text            # text | select | multiselect | suggest | confirm
        required: true
      - id: machine_name
        label: 'Machine name'
        derive:               # compute from other fields
          template: '{{name}}'
          transform: machine
      - id: profile
        label: 'Profile'
        type: select
        default: standard
        options:
          - { value: standard, label: 'Standard' }
          - { value: custom, label: 'Custom' }
      - id: profile_custom
        label: 'Custom profile'
        when:                 # shown only when the condition holds
          field: profile
          eq: custom
```

Field keys: `id`, `label`, `description`, `type`, `default`, `required`, `options`, `when` (conditional visibility), `derive` (computed value) and `discover` (detect from the directory). A `derive` transform is any str2name conversion (`machine`, `kebab`, `pascal`, ...) plus `host`, `lower`, `upper` and `initials`.

Top-level keys tune the interactive TUI: `theme` names a theme (see [Themes](#themes)), and the panel shows **Submit** and **Cancel** buttons by default - set `buttons: false` to hide them.

## Handlers

A field needs a handler only when it requires behaviour beyond a static value. Handlers are auto-discovered: field id `machine_name` resolves to class `MachineName` in a registered namespace.

```php
namespace App\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;

class Name extends AbstractHandler {

  public function default(Field $field, Context $context): mixed {
    // A dynamic default, computed from the run context.
    return basename($context->directory);
  }

  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && trim($value) !== '' ? NULL : 'A name is required.';
  }

  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  public function process(Field $field, mixed $value, Context $context): void {
    // Apply the answer - write files, rename directories, and so on.
  }

}
```

## Themes

A theme is a self-contained class that owns the entire visual representation - the palette, the glyphs (marker, scroll indicators, separators) and how every row is composed. Two are built in:

```php
use DrevOps\Tui\Render\Theme;

Theme::create('dark');   // the default
Theme::create('light');  // for light terminals
```

A custom theme subclasses `Theme` and defines its palette (override `defineGlyphs()` or any `render*` method for more):

```php
class OceanTheme extends Theme {
  protected function defineStyles(): array {
    return ['title' => '1;96', 'value' => '96', 'marker' => '1;96', /* ... */];
  }
}
```

Lowest friction: a config names the class directly, with no registration:

```yaml
theme: '\App\OceanTheme'
```

Or register a short alias and use it by name: `Theme::register('ocean', OceanTheme::class)`.

## Playground

Runnable, self-contained examples are in [`playground/`](playground): a minimal config, a full "package scaffolder" exercising every feature, and a custom-theme demo. Each is independent - copy one as a starting point.

## Architecture

Diagrams of the engine, the collection lifecycle and the panel TUI are in [`docs/architecture/`](docs/architecture).

## Maintenance

    composer install
    composer lint
    composer test

## Updating

To pull the latest infrastructure from the template into this project, ask Claude Code to "update scaffold" - see [`AGENTS.md`](AGENTS.md) for details.

---
