# Customizer playground

A small, runnable example that wires the `drevops/customizer` engine to a
made-up "package scaffolder". Use it to see how a configuration, a custom
handler and the interactive TUI fit together, and as a starting point to copy.

## Run it

From the customizer package root:

```bash
composer install

# Interactive panel TUI (arrow keys to move, enter to edit, q to finish).
php playground/run.php

# Pick a theme (dark is the default; light suits light terminals).
php playground/run.php --theme=light

# Non-interactive: pass answers as JSON, print the result.
php playground/run.php --prompts='{"name":"My Widget","features":["docker","tests"]}'

# Print the JSON schema an agent or a form would consume.
php playground/run.php --schema
```

## What it demonstrates

- **[`config.yml`](config.yml)** - a two-panel configuration that uses every
  widget type (`text`, `select`, `multiselect`, `suggest`, `confirm`),
  conditional visibility (`when`), and derived values with str2name transforms
  (`machine`, `pascal`, `lower`).
- **[`Handler/Name.php`](Handler/Name.php)** - a custom handler, auto-discovered
  from the field id (`name` -> `Name`), showing all four hooks: a dynamic
  `default()` from the run context, `validate()`, `transform()` and a
  `process()` side effect.
- **[`run.php`](run.php)** - how to load a config, wire a handler namespace, and
  collect answers either interactively or non-interactively.

## Things to try

- Add a field to `config.yml` and watch it appear in the TUI and the schema.
- Give it an `id` that matches a new handler class under `Handler/` and it is
  discovered automatically - no registration.
- Derive a value: add `derive: { template: '{{name}}', transform: kebab }` to a
  field. Any [str2name](https://github.com/AlexSkrypnyk/str2name) conversion,
  plus `host`, `lower`, `upper` and `initials`, works as a transform.
- Register a theme: call `Theme::register('brand', ['styles' => [...], 'glyphs'
  => [...]])` before constructing the controller, then `--theme=brand`.
