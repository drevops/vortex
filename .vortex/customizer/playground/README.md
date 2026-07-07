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

- **[`3-widgets/`](3-widgets)** - a static showcase rendering every widget
  (Text, Select, MultiSelect, Confirm, Suggest) side by side in Unicode and
  textual (ASCII) glyph modes. Widgets pull their glyphs from the theme, so the
  same widget adapts to the terminal: Unicode is auto-detected from the locale
  (prompty-style), ASCII is the fallback.

  ```bash
  php playground/3-widgets/run.php
  ```

## How a config picks a theme

Three ways, lowest friction first:

1. **Name the class in the config** - `theme: '\Your\ThemeClass'`. The class is
   instantiated directly; no registration needed. This is what `2-custom-theme`
   does.
2. **Register a short name** - `Theme::register('ocean', OceanTheme::class)`,
   then `theme: ocean`. Useful to give a class a stable alias.
3. **Built-ins** - `theme: dark` (the default) or `theme: light`.
