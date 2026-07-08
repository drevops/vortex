<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=tui&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="tui logo"></a>
</p>

<h1 align="center">Tui</h1>

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

A dependency-light PHP engine for building **panel-based terminal forms**: interactive, keyboard-driven questionnaires that collect answers and hand them to your code. Describe the questions in PHP with a fluent builder, add a handler class wherever a question needs real behaviour, and the engine renders a scrollable, themeable TUI - or runs headless from a JSON payload.

It powers the [Vortex](https://www.vortextemplate.com) project installer, but knows nothing about Vortex: the engine is generic, the project-specific questions and handlers live in the consumer, and **applying the collected answers is the consumer's job, not the TUI's**.

## Features

- **Builder-driven** - panels and fields are declared in PHP with a fluent builder; the common cases need no code.
- **Every widget type** - text, single-select, multi-select (type-to-filter), autocomplete-with-fallback, and yes/no.
- **Interactive or headless** - drive the panel TUI by keyboard, or collect answers non-interactively from a JSON payload (and emit a JSON schema for agents and forms).
- **Derived values** - compute one field from others with [str2name](https://github.com/AlexSkrypnyk/str2name) transforms; chains settle to a fixpoint.
- **Conditional fields** - show or hide fields with `when` rules; a fix-up pass reconciles dependent answers.
- **Discovery** - detect sensible defaults from the target directory (`.env` keys, JSON paths, path existence, directory scans).
- **Declared behaviour** - validation, transforms and dynamic defaults as closures on the field; per-field handler classes remain as a fallback.
- **Themeable** - the whole visual representation (colours, glyphs, layout) is a theme class; ships with dark and light, and a form can name a custom theme class directly.

## Installation

    composer require drevops/tui

## Quick start

Declare a form with the fluent `Form` builder, then drive it with the `Tui` facade - one class that wires the engine, resolver, schema tools and TUI for you:

```php
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Tui;

$form = Form::create('My form')
  ->panel('general', 'General', function (PanelBuilder $p): void {
    $p->text('name', 'Your name')->required();
  });

$tui = new Tui($form, ['App\\Handler']);

// Interactive panel TUI on a terminal, headless otherwise.
$answers = $tui->run();

// Or call a mode directly:
echo $tui->collect('{"name":"Ada"}')->toJson();  // headless: JSON + environment
$answers = $tui->interact();                     // interactive panel TUI
```

It also exposes `schema()`, `agentHelp()` and `validate()`, and - when you want finer control - the internals via `config()`, `engine()` and `registry()`. See [`playground/`](playground) for complete, runnable examples.

## Configuration

A form is a tree of panels, each holding fields, built fluently. Rules are named-argument spec objects, so the IDE completes them and a typo fails at declaration time:

```php
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Derive\Derive;

$form = Form::create('My form')
  ->panel('general', 'General', function (PanelBuilder $p): void {
    // text | select | multiselect | suggest | confirm
    $p->text('name', 'Project name')->required();

    // Compute one field from others.
    $p->text('machine_name', 'Machine name')->derive(new Derive('{{name}}', transform: 'machine'));

    $p->select('profile', 'Profile')
      ->default('standard')
      ->options(['standard' => 'Standard', 'custom' => 'Custom']);

    // Shown only when the condition holds; compose with Condition::all()/any()/not().
    $p->text('profile_custom', 'Custom profile')->when(new Condition('profile', eq: 'custom'));
  });
```

Each field builder chains `->description()`, `->default()`, `->required()`, `->options()`, `->when(new Condition(...))` (conditional visibility, operators `eq` / `ne` / `in` / `contains`), `->derive(new Derive(...))` (computed value) and `->discover(...)` (detect from the directory via `new Dotenv('KEY')`, `new JsonValue('composer.json', 'name')`, `new PathExists('path')`, `new Scan('dir', type: 'dir')`, or a custom `fn (Context $c): mixed` closure). A `Derive` transform is any str2name conversion (`machine`, `kebab`, `pascal`, ...) plus `host`, `lower`, `upper` and `initials` - an unknown name throws when the form is declared.

Form-level methods tune the interactive TUI: `->theme()` names a theme (see [Themes](#themes)), `->banner()` sets a start banner, and the panel shows **Submit** and **Cancel** buttons by default - `->buttons(FALSE)` hides them.

Headless collection also reads per-question environment overrides named `<PREFIX><FIELD_ID>` (the uppercased field id). `->envPrefix('MYAPP_')` declares that namespace on the form, a `new Tui($config, [], 'MYAPP_')` constructor argument overrides it, and without either the prefix is `TUI_`.

## Field behaviour

Behaviour beyond a static value is declared on the field itself - a dynamic default, validation and a value transform as closures, right in the form:

```php
use DrevOps\Tui\Handler\Context;

$p->text('name', 'Project name')
  ->default(fn (Context $c): string => basename($c->directory))
  ->validate(fn (mixed $v): ?string => is_string($v) && trim($v) !== '' ? NULL : 'A name is required.')
  ->transform(fn (mixed $v): mixed => is_string($v) ? trim($v) : $v);
```

Reusable validators and transformers live as public static methods on a consumer class. Reference one explicitly with a first-class callable - `->validate(Webroot::validate(...))` - or let the engine discover it: registering a namespace (`new Tui($form, ['App\\Handler'])`) resolves the class by field id (`machine_name` -> `MachineName`) and uses its static `validate()`/`transform()` whenever the field declares none. The field declaration always wins.

The TUI only collects: it presents answers and never applies them. **Applying answers - writing files, renaming directories - is the consumer's job.** A consumer that processes answers defines its own processor interface, keeping the form for collection and the processors for side effects - one class per field can carry both its `process()` and its reusable static behaviour (this is exactly what the Vortex CLI does).

## Themes

A theme is a self-contained class that owns the entire visual representation - the palette, the glyphs (marker, scroll indicators, separators) and how every row is composed. Two are built in:

```php
use DrevOps\Tui\Theme\AbstractTheme;

AbstractTheme::create('dark');   // the default
AbstractTheme::create('light');  // for light terminals
```

A custom theme subclasses a built-in theme (e.g. `DarkTheme`) or `AbstractTheme`, and defines its palette (override `defineStyles()`/`defineGlyphs()` or any `render*` method for more):

```php
use DrevOps\Tui\Theme\DarkTheme;

class OceanTheme extends DarkTheme {
  protected function defineStyles(): array {
    return ['title' => '1;96', 'value' => '96', 'marker' => '1;96', /* ... */];
  }
}
```

Lowest friction: a form names the class directly, with no registration:

```php
$form = Form::create('My form')->theme('\App\OceanTheme')/* ... */;
```

Or register a short alias and use it by name: `AbstractTheme::register('ocean', OceanTheme::class)`, then `->theme('ocean')`.

## Playground

Runnable, self-contained examples are in [`playground/`](playground): a minimal form, a full "package scaffolder", a custom-theme demo, per-widget demos, nested panels with fix-ups, and update-mode discovery. Each is independent - copy one as a starting point.

## Architecture

Diagrams of the engine, the collection lifecycle and the panel TUI are in [`docs/architecture/`](docs/architecture).

## Maintenance

    composer install
    composer lint
    composer test

## Updating

To pull the latest infrastructure from the template into this project, ask Claude Code to "update scaffold" - see [`AGENTS.md`](AGENTS.md) for details.

---
