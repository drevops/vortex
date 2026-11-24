@@ -105,44 +105,6 @@
 
 Run `ahoy drush config:delete <name>` only after the developer approved it, and only for an item that has no files or content depending on it. Delete configuration entities in the admin UI, which also removes what depends on them.
 
-## Config Split
-
-`web/sites/default/includes/modules/settings.config_split.php` turns on 1 split per environment type, through a `$config['config_split.config_split.<name>']['status'] = TRUE` override:
-
-| Environment type | Active split | Split directory |
-|------------------|--------------|-----------------|
-| `local`          | `local`      | `config/local`  |
-| `ci`             | `ci`         | `config/ci`     |
-| `dev`            | `dev`        | `config/dev`    |
-| `stage`          | `stage`      | `config/stage`  |
-| `prod`           | none         |                 |
-
-`settings.php` detects the environment type from the hosting platform, and the `ENVIRONMENT_TYPE` variable overrides it. See [Environment type detection](https://www.vortextemplate.com/docs/development/settings#environment-type-detection).
-
-The template ships these 4 directories empty and defines no splits. A split takes effect only once a split with the matching machine name exists, defined in `config/default/config_split.config_split.<name>.yml`. See [Config Split](https://www.vortextemplate.com/docs/development/modules/contributed-modules#config-split).
-
-The override only ever turns a split on. A split whose own file says `status: true` is active in every environment, production included, so keep `status: false` in each definition file.
-
-### See which split is active
-
-- The status the site uses: `ahoy drush config:get config_split.config_split.local status --include-overridden`
-- The status stored in the file: `ahoy drush config:get config_split.config_split.local status`
-- A state override that someone set: `ahoy drush config-split:status-override local`
-
-### How the lists behave
-
-- `complete_list`, `module` and `theme`: the items leave `config/default` on export and live only in the split directory, such as `config/local/<name>.yml`. Use these lists for configuration that exists only in that environment, such as development modules.
-- `partial_list`: the item stays in `config/default`, and the split directory holds only the difference, in `config_split.patch.<name>.yml`. Use it for configuration that exists everywhere with different values.
-
-### Export and import with splits
-
-- A full `config:export` writes the active split's directory after it writes `config/default`. The single-item export script writes only `config/default`, so export split-owned items with a full export from a clean tree.
-- A full `config:import` applies the active split. A partial import doesn't apply any split.
-- To change a split's lists, change the split in the admin UI, or partial-import its edited definition file. Then run a full export, because the split directories are written during export.
-- `ahoy provision` runs `drush config:import` a second time when the module is enabled, because the first import doesn't apply new split configuration.
-
-The module's Drush commands are `config-split:export <split>`, `config-split:import <split>`, `config-split:activate <split>`, `config-split:deactivate <split>` and `config-split:status-override <split> [active|inactive|default]`. Confirm what the installed version offers with `ahoy drush list --filter=config-split`.
-
 ## Troubleshooting
 
 Read [references/troubleshooting.md](references/troubleshooting.md) when an import fails, when items vanish from `config/default` on export, or when `config:status` keeps reporting differences after an import.
