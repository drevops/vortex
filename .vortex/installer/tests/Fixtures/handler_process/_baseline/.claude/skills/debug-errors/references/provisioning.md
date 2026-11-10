# Provisioning failures

`ahoy provision` runs `vendor/bin/vortex-provision` in the `cli` container. The script stops at the first command that fails, so the last `[TASK]` line without a matching `[ OK ]` line names the step, and the output right above `[FAIL]` holds the error. See [Provision](https://www.vortextemplate.com/docs/development/provision) for the whole flow and its variables.

## The steps in order

| Step line                                                     | What runs                                                       |
|---------------------------------------------------------------|-----------------------------------------------------------------|
| `Provisioning site from the database dump file.` or `from the profile.` | The import of `.data/db.sql`, or `drush site:install`  |
| `[TASK] Enabling maintenance mode.`                           | `drush maint:set 1`                                             |
| `Updated site UUID from the configuration`                    | `drush config:set system.site uuid` with the UUID in `config/default/system.site.yml` |
| `[TASK] Running database updates.`                            | `drush updatedb --no-cache-clear`                               |
| `[TASK] Clearing cache after database updates.`               | `drush cache:rebuild`                                           |
| `[TASK] Importing configuration.`                             | `drush config:import`, only when `config/default` holds files   |
| `[TASK] Rebuilding cache.`                                    | `drush cache:rebuild`                                           |
| `[TASK] Running deployment hooks.`                            | `drush deploy:hook`                                             |
| Sanitization lines                                            | `drush sql:sanitize`, then the queries in `scripts/sanitize.sql` |
| `[TASK] Running custom post-install script "<file>".`         | Each `scripts/provision-*.sh` file, in alphabetical order       |
| `[TASK] Disabling maintenance mode.`                          | `drush maint:set 0`                                             |

Before the first step, the summary block lists what the script found. `Existing site found : No` with `DB dump file path : ... (absent)` fails at the import.

## Messages and fixes

| Message                                                                  | Cause                                                                                   | Fix                                                                                                         |
|--------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| `Config directory was not found in the Drupal settings.`                 | `$settings['config_sync_directory']` is empty                                           | Check `DRUPAL_CONFIG_PATH` in `.env`, which `settings.php` reads                                             |
| `Config directory "<path>" does not exist.`                              | The directory that `DRUPAL_CONFIG_PATH` points at is missing                            | Restore `config/default` from Git. It ships with a `.gitkeep` file                                          |
| `Dump file <path> does not exist.` then `Unable to import database from file.` | No dump in `.data/`                                                               | Get a dump, as the `set-up-environment` skill describes, then provision again                              |
| `Dump file <path> is not readable.`                                      | The file permissions block the `cli` container                                          | Fix the permissions on the host. The site content wasn't changed                                           |
| `Could not read site UUID from <path>/system.site.yml.`                  | The exported `system.site.yml` has no `uuid` line                                       | Restore the file from Git, or export it again from a working site                                          |
| An exception after `[TASK] Running database updates.`                    | An update hook threw                                                                    | Read the hook it names, fix it, check with `ahoy drush updatedb:status`, then run `ahoy drush updatedb`     |
| `Configuration was changed by database updates.`                         | `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE=1` and an update hook changed active configuration | Read the listed files and the 2 export directories it prints, then export the changed configuration with the `manage-config` skill |
| An error after `[TASK] Importing configuration.`                         | The configuration import failed                                                         | Use the `manage-config` skill, which covers import failures                                                 |
| An error after `[TASK] Running deployment hooks.`                        | A `hook_deploy_NAME()` threw                                                            | List the pending hooks with `ahoy drush deploy:hook-status`, fix the hook, then run `ahoy drush deploy:hook` |
| A line with `!`, or the output ending, after `[TASK] Running custom post-install script` | That script failed                                                                      | Read the script, fix it, and run it on its own with `ahoy cli ./scripts/provision-<name>.sh`               |
| `Configuration was changed by post-provision operations.`                | `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_PROVISION=1` and a hook or script changed configuration | Compare with `ahoy drush config:status --state=Different`, then export or fix the code that changed it |
| `Drupal status report contains errors.`                                  | `VORTEX_PROVISION_VERIFY_STATUS_REPORT=1` and a requirement has the error severity      | Read them with `ahoy drush core:requirements --severity=2`. List accepted keys in `VORTEX_PROVISION_VERIFY_STATUS_REPORT_EXCLUDE` |
| `Unable to read the Drupal status report.`                               | `drush core:requirements` itself failed                                                 | Run `ahoy drush core:requirements --debug` and read its error                                               |

## Results that look like failures

- `Site content will be preserved.` with `Overwrite existing DB : No`: a site was installed and `VORTEX_PROVISION_OVERRIDE_DB` isn't `1`, so the dump wasn't imported. To import it, provision with `VORTEX_PROVISION_OVERRIDE_DB=1` in front of the command, after asking.
- `Sanitization will be skipped for an existing database.`: sanitization runs only on a fresh database.
- The site still shows the maintenance page after a failure: run `ahoy drush maint:set 0`.

## Rerun with more output

- `VORTEX_DEBUG=1 AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision` prints every command the script runs.
- Add `--debug` to a single Drush command, such as `ahoy drush updatedb --debug`, to see Drush's log messages for that step.
- A custom script reads `VORTEX_PROVISION_OVERRIDE_DB` to tell a fresh database from an existing one. Provisioning exports it as `1` for a fresh database, so put `VORTEX_PROVISION_OVERRIDE_DB=1` in front of `ahoy cli ./scripts/provision-<name>.sh` to rerun the fresh-database path.
