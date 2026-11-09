# Decision rules

The rules in `SKILL.md` run in order, and the first match decides the recommendation. This page explains how to recognize each case and when to recommend something else. The developer makes the final call every time.

## How `config:status` labels an item

`drush config:status` compares the active database with the code, read through the import transformation that a deployment applies.

| State              | Database                              | Code | The next deployment's import               |
|--------------------|---------------------------------------|------|--------------------------------------------|
| `Only in DB`       | Yes                                   | No   | Deletes the item                           |
| `Only in sync dir` | No, or yes with a different `uuid`    | Yes  | Creates the item, or deletes and recreates it |
| `Different`        | Yes                                   | Yes  | Overwrites the database value              |

Drush labels a `uuid` mismatch as `Only in sync dir`, not `Different`, because Drupal plans it as a delete followed by a create. So check every `Only in sync dir` item with `config:get` before you treat it as missing from the database.

## Rule 1: an excluded module or a configuration split owns the item

Recognize it:

- The item's name starts with a module listed in `$settings['config_exclude_modules']`. Check with `grep -rn config_exclude_modules web/sites/default/includes/modules`. The development modules are listed there, so provisioning installs them in some environments and the configuration never carries them.
- `find config -name "*<name>.yml"` finds the file in a directory other than `config/default`, such as `config/stage`, or as a patch file whose name ends in `.<name>.yml`.

Recommend Skip. The difference exists on purpose, and a file in `config/default` would push it to every environment. When the developer still wants to change the item, handle it with the `manage-config` skill, outside this walk.

## Rule 2: the `uuid` differs

Recognize it: an `Only in sync dir` item that `config:get` still prints, or a diff whose `uuid:` lines differ.

Recommend Export. The importer deletes the entity and creates a new one with the code's `uuid`. For a field storage, that deletes every stored value of the field. For other entity types, the delete can take dependent configuration and data stored against the entity with it. The environment's entity is the one that holds data, so its `uuid` has to win.

Exception: the developer recreated the item on purpose in code, and the environment's copy holds no data. Then Import is safe, but say what it deletes.

## Rule 3: only `_core` differs

Recognize it: the diff touches only the `_core:` block, usually `default_config_hash`.

Recommend Skip. The key records the default configuration the item was installed from. It differs between sites that were set up in different ways and changes no behavior. Exporting it only adds noise to the change.

## Rule 4: the branch changed the file

Recognize it: the file appears in the `git diff --name-only` list from step 1.

Recommend Import. The branch carries an intended change that hasn't reached the environment yet.

Exception: the diff also shows edits made on the environment in other keys of the same item. Importing overwrites them. Present both sides, and when both changes must survive, ask the developer to make the environment's edits again on the local site, then export the item.

## Rule 5: `Only in DB`

Recognize it: the database holds the item and `config/` doesn't.

Recommend Export. Someone created it on the environment, in the admin UI, through an update hook or by installing a module, and the code never caught up.

Exceptions, where Import fits better:

- The item is a leftover of a feature that the code removed on purpose, such as a view of a deleted content type.
- The item belongs to a module that the code no longer enables.

An Import here deletes the item from the environment on the next deployment, together with the configuration that depends on it. Always ask twice before you record it.

## Rule 6: `Only in sync dir`, and the database lacks the item

Recognize it: `config:get` fails with `Config <name> does not exist`.

Recommend Import. A deployment creates the item.

Exception: the file exists on the default branch and was deployed before. Then someone deleted the item on the environment after the deployment. Present both sides: Import restores it on the next deployment, and Export deletes the file to keep it gone.

## Rule 7: environment-specific values

Recognize it: the values that differ are hostnames, URLs of other services, API keys, email addresses or other settings that must differ per environment.

Recommend Skip. Neither side should hold these values for every environment. Tell the developer to move them into a per-module override file, `web/sites/default/includes/modules/settings.<module>.php`, with a condition on the environment type. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides). Never copy a secret from the database into `config/`.

## Rule 8: edits made on the site

Recognize it: the database holds changes a site builder or editor would make, such as view displays, block placements, labels, help text or permission changes.

Recommend Export. The edit is newer than the code, and the next deployment would undo it.

Exception: the developer knows the edit was a mistake or a temporary fix. Then Import restores the code's value.

## Rule 9: anything else

Recommend Skip. Show both sides plainly, say what each decision would do on the next deployment, and wait for the developer.

## Items that need extra care

- `core.extension`: the list of enabled modules and themes. Export it only with the export script, which leaves out excluded modules, then check that the diff changes only the modules you expect. An Import that removes a module uninstalls it on the environment.
- `system.site`: provisioning sets the site `uuid` from `config/default/system.site.yml` before each import, so the environment and the file normally agree. A `uuid` difference on this item means the database comes from another installation. Stop and report it instead of exporting, and never change the `uuid` line by hand.
- Field storage items (`field.storage.*`): any recreate or delete loses data. Recommend Export unless the developer confirms the data can go.
