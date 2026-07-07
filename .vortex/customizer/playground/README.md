# Customizer playground

Runnable, self-contained examples of the `drevops/customizer` engine. Each
numbered directory is independent - copy one as a starting point.

```bash
composer install
```

## Examples

- **[`0-minimal/`](0-minimal)** - the smallest runner: a two-field config, no
  handlers, collected non-interactively.

  ```bash
  php playground/0-minimal/run.php --prompts='{"name":"Ada","colour":"green"}'
  ```

- **[`1-scaffolder/`](1-scaffolder)** - a "package scaffolder" that exercises
  every widget type, conditional visibility (`when`), derived values with
  str2name transforms, and a custom auto-discovered handler
  ([`Name.php`](1-scaffolder/Name.php)). Runs the interactive TUI or collects
  non-interactively.

  ```bash
  php playground/1-scaffolder/run.php                                  # TUI
  php playground/1-scaffolder/run.php --prompts='{"name":"My Widget"}'
  php playground/1-scaffolder/run.php --schema
  ```

- **[`2-custom-theme/`](2-custom-theme)** - a self-contained custom theme class
  ([`OceanTheme.php`](2-custom-theme/OceanTheme.php)) named directly from the
  config, driving the TUI with a banner.

  ```bash
  php playground/2-custom-theme/run.php
  ```

- **[`3-widgets/`](3-widgets)** - interact with each widget on its own, or all
  in turn. Widgets pull their glyphs from the theme, and the mode flags force
  textual (ASCII) or no-colour rendering so you can see either without changing
  your terminal locale (mirrors prompty's `--no-unicode` / `--no-ansi`):

  ```bash
  php playground/3-widgets/widget-select.php               # one widget
  php playground/3-widgets/widgets.php                     # every widget in turn
  php playground/3-widgets/widget-select.php --no-unicode  # textual glyphs
  php playground/3-widgets/widget-select.php --no-ansi     # no colour
  php playground/3-widgets/show.php                        # static, both modes side by side
  ```

  Per-widget files: `widget-text.php`, `widget-select.php`,
  `widget-multiselect.php`, `widget-confirm.php`, `widget-suggest.php`.

## How a config picks a theme

Three ways, lowest friction first:

1. **Name the class in the config** - `theme: '\Your\ThemeClass'`. The class is
   instantiated directly; no registration needed. This is what `2-custom-theme`
   does.
2. **Register a short name** - `Theme::register('ocean', OceanTheme::class)`,
   then `theme: ocean`. Useful to give a class a stable alias.
3. **Built-ins** - `theme: dark` (the default) or `theme: light`.
