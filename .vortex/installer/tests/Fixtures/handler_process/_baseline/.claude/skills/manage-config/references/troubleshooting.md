# Configuration troubleshooting

Match the symptom, confirm the cause with the check, then apply the fix. Every check is read-only.

## Items vanish from `config/default` on export

A full export writes only what the export storage holds, and deletes files for everything else.

- An excluded module owns the item. Modules in `$settings['config_exclude_modules']` stay out of the export, and so does their configuration. Check: `grep -rn config_exclude_modules web/sites/default/includes/modules`. The per-module files add the development modules there on purpose, because provisioning installs them and a configuration import never does. Don't export their configuration into `config/default`.
- The item never reached the database. Someone added the file without importing it, and a full export then removes it. Check: `ahoy drush config:get <name>`. Fix: restore the file with `git restore <path>`, then import it with a partial import.
- A configuration entity was deleted in the admin UI. The export deletes its file, which is the correct result when the deletion was intended.

- A split's `complete_list`, `module` or `theme` list holds the item, or holds the module that provides it. The export moves the item into the split's directory, such as `config/local`, and deletes it from `config/default`. Check: `grep -n -A20 complete_list config/default/config_split.config_split.local.yml`, and the same for the `module` list and for each split. Fix: when the item must exist in every environment with different values, move it from `complete_list` to `partial_list`, then run a full export. `config/default/<name>.yml` comes back, and the split directory gets `config_split.patch.<name>.yml`.
- The split is active by accident. A definition file that says `status: true` is active in every environment. Check: `ahoy drush config:get config_split.config_split.<split> status`. Fix: set the stored status to `false`, and let `web/sites/default/includes/modules/settings.config_split.php` turn the split on for its environment.

## An import fails

Drush prints the validation errors from Drupal and imports nothing. Read the first error and match it:

- `Site UUID in source storage does not match the target storage.` The database comes from another installation than the exported files. `ahoy provision` sets the site UUID from `config/default/system.site.yml` before it imports. To fix it without provisioning, read the `uuid` value from that file and run `ahoy drush config:set system.site uuid <uuid>`.
- `Configuration <name> depends on the <module> module that will not be installed after import.` `core.extension.yml` doesn't list the module. Add the module under `module:` in `config/default/core.extension.yml`, keeping the existing order, or remove the item that depends on it.
- `Configuration <name> depends on the <dependency> configuration that will not exist after import.` A file the item needs is missing. Export the missing item from the database, or remove the dependent item.
- `Unable to uninstall the <module> module because: <reason>.` The import tries to uninstall a module that still has content or data. Removing that data is a content change, not a configuration change. Ask the developer how to remove it, for example with a deploy hook that runs before the next import.
- A PHP error or a schema error. Run the import again with `-v` for the stack trace, and check whether a module in the change list is missing from the codebase: `ahoy composer show <vendor>/<package>`.

## A recreate would destroy data

The change list from `ahoy drush config:import --no` shows the same item as both a create and a delete. The `uuid` in the file differs from the one in the database, so the import deletes the entity and creates it again. For a field storage, the field's data goes with it.

Check: compare the `uuid` line of `config/default/<name>.yml` with the output of `ahoy drush config:get <name> uuid`. Fix: keep the database's `uuid` by exporting the item from the database, as the export script does. Use the `reconcile-config` skill when the database comes from a deployed environment.

## Differences come back after every import

`ahoy drush config:status` reports `Different` right after a clean import.

- An update hook, a deploy hook or a provision script changes the item after the import. Find the code that saves it, and export the item once the code has run. `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_PROVISION=1` makes provisioning fail when this happens. See [Customize the flow](https://www.vortextemplate.com/docs/development/provision#customize-the-flow).
- The file was edited by hand, or written with `config:set` for a configuration entity, so its `dependencies` don't match what an entity save calculates. Save the entity in the admin UI, then export the item.
- Only `_core` differs. The `_core.default_config_hash` value records the default configuration that the item was installed from, and it differs between sites that were set up in different ways. It changes no behavior. Export the item to stop the report, or leave it.

## The site shows a value the file doesn't hold

`config:status` reports no difference, yet the page or the admin form shows another value. A settings override changes the value at runtime without touching the stored configuration.

Check: compare `ahoy drush config:get <name> <key>` with `ahoy drush config:get <name> <key> --include-overridden`, then search the overrides: `grep -rn "config\['<name>'\]" web/sites/default`. Fix: change the override file, not the configuration. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
