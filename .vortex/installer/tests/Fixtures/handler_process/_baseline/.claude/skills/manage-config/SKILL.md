---
name: manage-config
description: Use when exporting or importing Drupal configuration, previewing what a configuration import or export would change, reading, changing, exporting or importing a single configuration item, working with Config Split, or when configuration fails to import or disappears from `config/default` on export.
---

# Manage configuration

Move configuration between the database and the files in `config/default` without changing anything the developer hasn't seen. Every import and every export starts with a preview.

Run every command from the project root, 1 command per Bash call. `.env` sets the sync directory through `DRUPAL_CONFIG_PATH`, which `web/sites/default/settings.php` reads into `$settings['config_sync_directory']`.

## Rules

- Drush answers its own confirmation prompt with "yes" when no terminal is attached, so `ahoy drush config:import` from an agent shell imports straight away. Preview with `--no` first. Pass `-y` only after the developer approved that exact preview.
- Never run `config:import --source=<dir>` without `--partial`. A full import from a directory deletes every item that the directory doesn't hold.
- Check `git status --short config/` before a full export or import. When the tree holds changes the developer didn't make, work 1 item at a time instead.
- After any change made in the admin UI, export the configuration. This is an `AGENTS.md` rule.
- Run imports, exports, `config:set` and `config:delete` against the local site only, never against a remote site alias such as `@<alias>`.
- Never use `drush php:eval`. Write PHP to a file under `.artifacts/tmp/manage-config/` and run it with `ahoy drush php:script /app/.artifacts/tmp/manage-config/<file>.php`. The project is mounted at `/app` in the `cli` container, so container paths start with `/app`.
- Values set in the per-module files `web/sites/default/includes/modules/settings.<module>.php` are overrides. They never reach the exported files. To change such a value, edit that file instead.

## Check the state

```shell
ahoy drush config:status
```

The command compares the database with the files. It prints `No differences between DB and sync directory.` when both match. Otherwise each item has 1 of 3 states:

| State              | Meaning                                                                                          | A full import                         |
|--------------------|--------------------------------------------------------------------------------------------------|---------------------------------------|
| `Only in DB`       | The database holds the item and the files don't                                                  | Deletes it from the database          |
| `Only in sync dir` | The files hold the item and the database doesn't, or both hold it with different `uuid` values  | Creates it, or deletes and recreates it |
| `Different`        | Both hold the item with different values                                                         | Overwrites the database value         |

Narrow the list with `--state` and `--prefix`, for example `ahoy drush config:status --state=Different --prefix=views.view.`. Add `--format=list` for bare names.

`ahoy drush` hands its arguments to a second shell inside the container, so ordinary quotes are lost. Escape the quotes of a value that holds spaces with a backslash, as the comment above `cli` in `.ahoy.yml` describes: `ahoy drush config:status --state=\"Only in DB\"`.

An item reported as `Only in sync dir` that `ahoy drush config:get <name>` still prints exists on both sides with different `uuid` values. Importing it deletes the entity and creates it again. For a field storage, that deletes the field's data. Export the database version instead, or use the `reconcile-config` skill to decide.

## Import everything

1. Show the change list: `ahoy drush config:import --no`
2. Show the full diff: `ahoy drush config:import --no --diff`
3. When there are changes, both commands decline their own prompt and exit with an error. That's expected after `--no`. Without changes, they print `There are no changes to import.`
4. Present the changes to the developer. Name every deletion, and every item listed as both a create and a delete, because that pair is a recreate.
5. After the developer approves, import: `ahoy drush config:import -y`
6. Confirm with `ahoy drush config:status` that no differences remain.

When the code also brings database updates, run the whole sequence the way a deployment does, with `ahoy provision`: database updates first, then the configuration import, cache rebuilds and deploy hooks. It replaces the local database when `VORTEX_PROVISION_OVERRIDE_DB=1` is set, which `.env.local.example` sets. Ask before running it, then run `AHOY_CONFIRM_RESPONSE=y ahoy provision`, because `ahoy` waits for a typed answer otherwise. See [Provision](https://www.vortextemplate.com/docs/development/provision).

## Export everything

1. Confirm the tree is clean: `git status --short config/`
2. Preview the export: `ahoy drush config:export --no --diff`
3. Present the diff. After the developer approves, export: `ahoy drush config:export -y`
4. Review the result: `git status --short config/` and `git diff --stat config/`
5. Restore any file the developer didn't mean to change with `git restore <path>`, and delete any new file they didn't mean to add.

## Work with a single item

### Read it

- The whole item: `ahoy drush config:get system.site`
- 1 key: `ahoy drush config:get system.site page.front`
- The value the site uses, with overrides applied: `ahoy drush config:get system.site name --include-overridden`

### Change it

- Simple configuration, such as `system.site`: `ahoy drush config:set system.site page.front /home`
- A list: `ahoy drush config:set --input-format=yaml <name> <key> [value1,value2]` (`--input-format=yaml` parses the value as YAML)
- A value with spaces or YAML punctuation: copy the item's file into the partial import directory, edit the copy there and import it, as [Import it](#import-it) describes.

Read the item with `config:get` before you set it. When the name doesn't exist, `config:set` asks whether to create a new item, and the prompt answers "yes" on its own, so a typo creates stray configuration.

Change configuration entities, such as views, fields, blocks and image styles, in the admin UI or through the entity API. `config:set` writes the stored data directly and skips the `dependencies` calculation that an entity save performs.

Export the item afterwards, as the next section describes.

### Export it

Don't write `config:get` output into `config/default`. Drush prints multi-line strings in a different YAML style than Drupal's exporter and adds a second newline at the end, so the file churns in the diff. Write the item with Drupal's own exporter instead:

1. Copy the script from [references/export-items.md](references/export-items.md) to `.artifacts/tmp/manage-config/export-items.php`.
2. Run it with the item names: `ahoy drush php:script /app/.artifacts/tmp/manage-config/export-items.php system.site views.view.frontpage`
3. Review each file: `git diff -- config/default/system.site.yml`

The script reads the same export storage that `config:export` writes from. Modules listed in `$settings['config_exclude_modules']` stay out of `core.extension.yml`, the same as in a full export. Still review the diff of `core.extension.yml`: it should change only the modules you meant to change.

### Import it

A partial import updates and creates items from a directory and never deletes anything. It needs the core `config` module.

1. Start from an empty directory: `rm -rf .artifacts/tmp/manage-config/partial`
2. `mkdir -p .artifacts/tmp/manage-config/partial`
3. Copy each file to import: `cp config/default/system.site.yml .artifacts/tmp/manage-config/partial/`
4. Preview: `ahoy drush config:import --partial --source=/app/.artifacts/tmp/manage-config/partial --no --diff`
5. After the developer approves, import: `ahoy drush config:import --partial --source=/app/.artifacts/tmp/manage-config/partial -y`
6. Confirm: `ahoy drush config:status --prefix=system.site`

A partial import applies no configuration transformation, so the rules that the full import applies, such as excluded modules, don't run for it.

### Delete it

Run `ahoy drush config:delete <name>` only after the developer approved it, and only for an item that has no files or content depending on it. Delete configuration entities in the admin UI, which also removes what depends on them.

## Config Split

`web/sites/default/includes/modules/settings.config_split.php` turns on 1 split per environment type, through a `$config['config_split.config_split.<name>']['status'] = TRUE` override:

| Environment type | Active split | Split directory |
|------------------|--------------|-----------------|
| `local`          | `local`      | `config/local`  |
| `ci`             | `ci`         | `config/ci`     |
| `dev`            | `dev`        | `config/dev`    |
| `stage`          | `stage`      | `config/stage`  |
| `prod`           | none         |                 |

`settings.php` detects the environment type from the hosting platform, and the `ENVIRONMENT_TYPE` variable overrides it. See [Environment type detection](https://www.vortextemplate.com/docs/development/settings#environment-type-detection).

The template ships these 4 directories empty and defines no splits. A split takes effect only once a split with the matching machine name exists, defined in `config/default/config_split.config_split.<name>.yml`. See [Config Split](https://www.vortextemplate.com/docs/development/modules/contributed-modules#config-split).

The override only ever turns a split on. A split whose own file says `status: true` is active in every environment, production included, so keep `status: false` in each definition file.

### See which split is active

- The status the site uses: `ahoy drush config:get config_split.config_split.local status --include-overridden`
- The status stored in the file: `ahoy drush config:get config_split.config_split.local status`
- A state override that someone set: `ahoy drush config-split:status-override local`

### How the lists behave

- `complete_list`, `module` and `theme`: the items leave `config/default` on export and live only in the split directory, such as `config/local/<name>.yml`. Use these lists for configuration that exists only in that environment, such as development modules.
- `partial_list`: the item stays in `config/default`, and the split directory holds only the difference, in `config_split.patch.<name>.yml`. Use it for configuration that exists everywhere with different values.

### Export and import with splits

- A full `config:export` writes the active split's directory after it writes `config/default`. The single-item export script writes only `config/default`, so export split-owned items with a full export from a clean tree.
- A full `config:import` applies the active split. A partial import doesn't apply any split.
- To change a split's lists, change the split in the admin UI, or partial-import its edited definition file. Then run a full export, because the split directories are written during export.
- `ahoy provision` runs `drush config:import` a second time when the module is enabled, because the first import doesn't apply new split configuration.

The module's Drush commands are `config-split:export <split>`, `config-split:import <split>`, `config-split:activate <split>`, `config-split:deactivate <split>` and `config-split:status-override <split> [active|inactive|default]`. Confirm what the installed version offers with `ahoy drush list --filter=config-split`.

## Troubleshooting

Read [references/troubleshooting.md](references/troubleshooting.md) when an import fails, when items vanish from `config/default` on export, or when `config:status` keeps reporting differences after an import.
