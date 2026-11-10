@@ -37,8 +37,6 @@
 - Declare every module whose API the code calls in `dependencies`, as `<project>:<module>`: `drupal:node` for a core module, `search_api:search_api` for a contributed one. Drupal installs the dependencies first and refuses to uninstall them while the module is enabled.
 - A custom module lives in the repository, so it needs no Composer requirement. A contributed dependency does: add it with `ahoy composer require drupal/<project>`.
 
-- Declare `drupal_helpers:drupal_helpers` in every module whose code calls `Helper::`. Provisioning installs Drupal helpers only as a dependency of the demo module, so a module that relies on that stops working once the demo module is removed.
-
 ## Inject services
 
 - Never call `\Drupal::` in a class. Services, hook classes, plugins, controllers and forms receive their dependencies through the constructor. `\Drupal::` is acceptable only in procedural files such as `.module`, `.install`, `.post_update.php` and `.deploy.php`, as the shipped modules use it.
@@ -99,8 +97,6 @@
 - Skip work by environment with `\Drupal\Core\Site\Settings::get('environment')` and the `ENVIRONMENT_*` constants that `settings.php` defines, such as `ENVIRONMENT_PROD`.
 - When an update hook changes configuration, export the same change to `config/default`. Otherwise, the configuration import that follows `updatedb` reverts it. Setting `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE=1` makes provisioning fail when that happens.
 - Process large data sets in passes through the `$sandbox` argument and `$sandbox['#finished']`.
-
-- The Drupal helpers facades keep deploy hooks short, for example `Helper::term()->createTree()` and `Helper::menu()->createTree()`, and `Helper::entity($sandbox)->batchEntity()` batches across passes. See [Drupal helpers](https://www.vortextemplate.com/docs/development/modules/drupal-helpers).
 
 ### How provisioning runs them
 
