@@ -11,7 +11,7 @@
 
 Read `AGENTS.md` first. The shipped custom modules are described on [Module scaffold](https://www.vortextemplate.com/docs/development/modules/module-scaffold), and the hook types on [Deploy hooks](https://www.vortextemplate.com/docs/development/provision#deploy-hooks). Code for each section below is in [references/code-patterns.md](references/code-patterns.md).
 
-The examples use `sw_base` as the module name. Replace it, the `Drupal\sw_base` namespace and the `SwBase` class prefix with the module you're working on.
+The examples use `the_force_base` as the module name. Replace it, the `Drupal\the_force_base` namespace and the `TheForceBase` class prefix with the module you're working on.
 
 ## Decide where the code goes
 
@@ -20,9 +20,9 @@
 - Extend an existing module when the code belongs to the feature that module already owns.
 - Create a new module for a new feature with its own configuration, routes or dependencies, so the feature can be enabled, disabled and removed on its own.
 
-- `sw_base` is the module for general-purpose code that belongs to no feature module. It ships `sw_base.deploy.php`, whose `sw_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
+- `the_force_base` is the module for general-purpose code that belongs to no feature module. It ships `the_force_base.deploy.php`, whose `the_force_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
 
-- `sw_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
+- `the_force_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
 
 ## Name and place the module
 
@@ -32,7 +32,7 @@
 
 ## Write the info file
 
-Copy the keys from a shipped `.info.yml` file. `core_version_requirement: ^11` matches the Drupal 11 core in `composer.json`, and `package: star_wars` groups the custom modules on the Extend page.
+Copy the keys from a shipped `.info.yml` file. `core_version_requirement: ^11` matches the Drupal 11 core in `composer.json`, and `package: the_new_hope` groups the custom modules on the Extend page.
 
 - Declare every module whose API the code calls in `dependencies`, as `<project>:<module>`: `drupal:node` for a core module, `search_api:search_api` for a contributed one. Drupal installs the dependencies first and refuses to uninstall them while the module is enabled.
 - A custom module lives in the repository, so it needs no Composer requirement. A contributed dependency does: add it with `ahoy composer require drupal/<project>`.
@@ -42,8 +42,8 @@
 ## Inject services
 
 - Never call `\Drupal::` in a class. Services, hook classes, plugins, controllers and forms receive their dependencies through the constructor. `\Drupal::` is acceptable only in procedural files such as `.module`, `.install`, `.post_update.php` and `.deploy.php`, as the shipped modules use it.
-- Register a service in `sw_base.services.yml` with `autowire: true`, and add a class-name alias so other classes can type-hint it.
-- Drupal 11 autowires hook classes, controllers, forms and plugins from their constructor type hints, so they need no `create()` method. Type-hint the interface that core aliases to the service, such as `EntityTypeManagerInterface` or `ConfigFactoryInterface`. For a service without an alias, such as a logger channel, add `#[Autowire(service: 'logger.channel.sw_base')]` to the parameter.
+- Register a service in `the_force_base.services.yml` with `autowire: true`, and add a class-name alias so other classes can type-hint it.
+- Drupal 11 autowires hook classes, controllers, forms and plugins from their constructor type hints, so they need no `create()` method. Type-hint the interface that core aliases to the service, such as `EntityTypeManagerInterface` or `ConfigFactoryInterface`. For a service without an alias, such as a logger channel, add `#[Autowire(service: 'logger.channel.the_force_base')]` to the parameter.
 - A plugin is autowired only when its class implements `ContainerFactoryPluginInterface`. `PluginBase` supplies the `create()` method that does the wiring.
 - Never redeclare a property that `ControllerBase` or `FormBase` already defines, such as `$entityTypeManager` or `$configFactory`, as a promoted constructor parameter. PHP stops with a fatal error. Give the property another name or call the base class accessor.
 
@@ -50,11 +50,11 @@
 ## Implement hooks
 
 - In a new module, implement hooks as methods of a class in `src/Hook/`, each marked with `#[Hook('<hook name without the hook_ prefix>')]` from `Drupal\Core\Hook\Attribute\Hook`. Core looks for these classes only in `src/Hook/`.
-- In an existing module, follow the style it already uses. Procedural hooks live in the `.module` file and are named `sw_base_<hook>()`.
+- In an existing module, follow the style it already uses. Procedural hooks live in the `.module` file and are named `the_force_base_<hook>()`.
 - Some hooks stay procedural: `hook_install()`, `hook_uninstall()`, `hook_schema()` and `hook_update_N()` in the `.install` file, `hook_post_update_NAME()` and Drush's `hook_deploy_NAME()`.
 - Rebuild caches after adding or removing a hook: `ahoy drush cr`.
 
-- `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
+- `the_force_demo` implements `hook_theme()` procedurally in `the_force_demo.module`.
 
 - `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
 
@@ -61,15 +61,15 @@
 ## Add plugins
 
 - Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `web/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
-- Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `sw_base_latest_pages`.
+- Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `the_force_base_latest_pages`.
 - Declare cache metadata on everything a plugin renders. Use the `debug-performance` skill when output stays stale or a page stops caching.
 
-- `CounterBlock` in `sw_demo` still declares its plugin with the `@Block` annotation. Don't copy that part.
+- `CounterBlock` in `the_force_demo` still declares its plugin with the `@Block` annotation. Don't copy that part.
 
 ## Add routes, controllers and forms
 
-- Define routes in `sw_base.routing.yml`, with names that start with `sw_base.`. Give every route a `requirements` entry, usually `_permission`. Use `_access: 'TRUE'` only for a route that is intentionally public.
-- Define the module's own permissions in `sw_base.permissions.yml`.
+- Define routes in `the_force_base.routing.yml`, with names that start with `the_force_base.`. Give every route a `requirements` entry, usually `_permission`. Use `_access: 'TRUE'` only for a route that is intentionally public.
+- Define the module's own permissions in `the_force_base.permissions.yml`.
 - Put controllers in `src/Controller/`, extending `ControllerBase` and returning render arrays. Put forms in `src/Form/`, extending `FormBase`, or `ConfigFormBase` for a settings form whose elements save through `#config_target`.
 - Change state only from a Form API form or from a route with `_csrf_token: 'TRUE'`.
 - Rebuild caches after changing routes, services or plugins: `ahoy drush cr`.
@@ -78,8 +78,8 @@
 ## Ship configuration with the module
 
 - Put the module's default configuration in `config/install/`, and configuration that depends on another optional module in `config/optional/`. Drupal installs it once, when the module is installed. Existing sites receive later changes only through the exported configuration in `config/default` or through an update hook.
-- Describe every configuration item the module defines in `config/schema/sw_base.schema.yml`.
-- Values that differ per environment go in a settings file, `web/sites/default/includes/modules/settings.sw_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
+- Describe every configuration item the module defines in `config/schema/the_force_base.schema.yml`.
+- Values that differ per environment go in a settings file, `web/sites/default/includes/modules/settings.the_force_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
 - Export configuration changes with the `manage-config` skill.
 
 - When you add a settings file, add its assertions to the settings tests in `tests/phpunit/Drupal/`.
@@ -88,11 +88,11 @@
 
 | Hook                      | File                      | Run by                                           | Use for                                                                        |
 |---------------------------|---------------------------|--------------------------------------------------|--------------------------------------------------------------------------------|
-| `hook_update_N()`         | `sw_base.install`         | `drush updatedb`, before the configuration import | Database schema changes and data fixes that don't need the full entity API     |
-| `hook_post_update_NAME()` | `sw_base.post_update.php` | `drush updatedb`, after every `hook_update_N()`  | Saving entities or configuration with the full API                             |
-| `hook_deploy_NAME()`      | `sw_base.deploy.php`      | `drush deploy:hook`, after the configuration import | Site setup that relies on imported configuration: blocks, menu links, terms, content |
+| `hook_update_N()`         | `the_force_base.install`         | `drush updatedb`, before the configuration import | Database schema changes and data fixes that don't need the full entity API     |
+| `hook_post_update_NAME()` | `the_force_base.post_update.php` | `drush updatedb`, after every `hook_update_N()`  | Saving entities or configuration with the full API                             |
+| `hook_deploy_NAME()`      | `the_force_base.deploy.php`      | `drush deploy:hook`, after the configuration import | Site setup that relies on imported configuration: blocks, menu links, terms, content |
 
-- Number update hooks from `11001` upward, as `sw_base_update_11001()`. Never renumber or reuse a number that has shipped.
+- Number update hooks from `11001` upward, as `the_force_base_update_11001()`. Never renumber or reuse a number that has shipped.
 - Drupal records the latest update number when it installs a module and marks the module's post-update hooks as run. Neither runs on a new install, so do the same work in `hook_install()` when a fresh install needs it.
 - Drush runs each deploy hook once per environment and records its name in the `deploy_hook` key-value store. Every deploy hook of a newly installed module runs on the next `drush deploy:hook`. Never rename a deploy hook that has run anywhere, because the new name runs again.
 - Make every deploy hook safe to run on a site where its work already exists: load the block, link or entity before you create it, and return early when it exists. Return a message for Drush to print.
@@ -127,7 +127,7 @@
 
 ## Enable the module on every environment
 
-1. Install it locally: `ahoy drush pm:install sw_base`
+1. Install it locally: `ahoy drush pm:install the_force_base`
 2. Confirm it is enabled: `ahoy drush pm:list --type=module --status=enabled`
 3. Choose how the other environments install it, from the 2 options below.
 
@@ -140,11 +140,11 @@
 
 Read `docs/testing.md` for the testing conventions this project agreed on. Put each test where its tool collects it.
 
-- Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\sw_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
+- Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\the_force_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
 - Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
 - Use the `write-tests` skill to decide what to test and how.
 
-- `web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
+- `web/modules/custom/the_force_base/tests/src/` provides `TheForceBaseUnitTestBase`, `TheForceBaseKernelTestBase`, `TheForceBaseFunctionalTestBase` and `TheForceBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\the_force_base\<Type>`.
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
