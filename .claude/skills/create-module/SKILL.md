---
name: create-module
description: Use when adding a custom Drupal module to this site or extending one with services, hooks, plugins, routes, controllers, forms, default configuration, update hooks or deploy hooks, or when deciding how a custom module gets enabled on every environment and where its code and tests go.
---

# Create or extend a custom module

Build custom code the way the shipped custom modules are built, so it installs, updates and deploys through the same provisioning steps as the rest of the site.

Run every command from the project root through `ahoy`, 1 command per Bash call. Never use `drush php:eval`: write ad-hoc PHP to a file under `.artifacts/tmp/create-module/` and run it with `ahoy drush php:script /app/.artifacts/tmp/create-module/<file>.php`. The project is mounted at `/app` in the `cli` container.

Read `AGENTS.md` first. The shipped custom modules are described on [Module scaffold](https://www.vortextemplate.com/docs/development/modules/module-scaffold), and the hook types on [Deploy hooks](https://www.vortextemplate.com/docs/development/provision#deploy-hooks). Code for each section below is in [references/code-patterns.md](references/code-patterns.md).

The examples use `ys_base` as the module name. Replace it, the `Drupal\ys_base` namespace and the `YsBase` class prefix with the module you're working on.

## Decide where the code goes

List the custom modules first: `ls web/modules/custom`.

- Extend an existing module when the code belongs to the feature that module already owns.
- Create a new module for a new feature with its own configuration, routes or dependencies, so the feature can be enabled, disabled and removed on its own.

[//]: # (#;< CUSTOM_MODULE_BASE)

- `ys_base` is the module for general-purpose code that belongs to no feature module. It ships `ys_base.deploy.php`, whose `ys_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.

[//]: # (#;> CUSTOM_MODULE_BASE)

[//]: # (#;< CUSTOM_MODULE_DEMO)

- `ys_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.

[//]: # (#;> CUSTOM_MODULE_DEMO)

## Name and place the module

- Create the module in `web/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
- Use lowercase letters, digits and underscores, at most 50 characters. Drupal refuses to install a longer name.
- Every custom module shares 1 prefix, `ys` in the template, which the installer replaced with the project's own. Read it from the start of an existing module directory name in `web/modules/custom/`. When none exists, ask the developer.

[//]: # (#;< MIGRATION)

- `ys_migrate` keeps the `ys` prefix on every project, so never take the prefix from it.

[//]: # (#;> MIGRATION)

## Write the info file

Copy the keys from a shipped `.info.yml` file. `core_version_requirement: ^11` matches the Drupal 11 core in `composer.json`, and `package: your_site` groups the custom modules on the Extend page.

- Declare every module whose API the code calls in `dependencies`, as `<project>:<module>`: `drupal:node` for a core module, `search_api:search_api` for a contributed one. Drupal installs the dependencies first and refuses to uninstall them while the module is enabled.
- A custom module lives in the repository, so it needs no Composer requirement. A contributed dependency does: add it with `ahoy composer require drupal/<project>`.

[//]: # (#;< MODULE_DRUPAL_HELPERS)

- Declare `drupal_helpers:drupal_helpers` in every module whose code calls `Helper::`. Provisioning installs Drupal helpers only as a dependency of the demo module, so a module that relies on that stops working once the demo module is removed.

[//]: # (#;> MODULE_DRUPAL_HELPERS)

## Inject services

- Never call `\Drupal::` in a class. Services, hook classes, plugins, controllers and forms receive their dependencies through the constructor. `\Drupal::` is acceptable only in procedural files such as `.module`, `.install`, `.post_update.php` and `.deploy.php`, as the shipped modules use it.
- Register a service in `ys_base.services.yml` with `autowire: true`, and add a class-name alias so other classes can type-hint it.
- Drupal 11 autowires hook classes, controllers, forms and plugins from their constructor type hints, so they need no `create()` method. Type-hint the interface that core aliases to the service, such as `EntityTypeManagerInterface` or `ConfigFactoryInterface`. For a service without an alias, such as a logger channel, add `#[Autowire(service: 'logger.channel.ys_base')]` to the parameter.
- A plugin is autowired only when its class implements `ContainerFactoryPluginInterface`. `PluginBase` supplies the `create()` method that does the wiring.
- Never redeclare a property that `ControllerBase` or `FormBase` already defines, such as `$entityTypeManager` or `$configFactory`, as a promoted constructor parameter. PHP stops with a fatal error. Give the property another name or call the base class accessor.

## Implement hooks

- In a new module, implement hooks as methods of a class in `src/Hook/`, each marked with `#[Hook('<hook name without the hook_ prefix>')]` from `Drupal\Core\Hook\Attribute\Hook`. Core looks for these classes only in `src/Hook/`.
- In an existing module, follow the style it already uses. Procedural hooks live in the `.module` file and are named `ys_base_<hook>()`.
- Some hooks stay procedural: `hook_install()`, `hook_uninstall()`, `hook_schema()` and `hook_update_N()` in the `.install` file, `hook_post_update_NAME()` and Drush's `hook_deploy_NAME()`.
- Rebuild caches after adding or removing a hook: `ahoy drush cr`.

[//]: # (#;< CUSTOM_MODULE_DEMO)

- `ys_demo` implements `hook_theme()` procedurally in `ys_demo.module`.

[//]: # (#;> CUSTOM_MODULE_DEMO)

[//]: # (#;< TOOL_RECTOR)

- `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.

[//]: # (#;> TOOL_RECTOR)

## Add plugins

- Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `web/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
- Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `ys_base_latest_pages`.
- Declare cache metadata on everything a plugin renders. Use the `debug-performance` skill when output stays stale or a page stops caching.

[//]: # (#;< CUSTOM_MODULE_DEMO)

- `CounterBlock` in `ys_demo` still declares its plugin with the `@Block` annotation. Don't copy that part.

[//]: # (#;> CUSTOM_MODULE_DEMO)

## Add routes, controllers and forms

- Define routes in `ys_base.routing.yml`, with names that start with `ys_base.`. Give every route a `requirements` entry, usually `_permission`. Use `_access: 'TRUE'` only for a route that is intentionally public.
- Define the module's own permissions in `ys_base.permissions.yml`.
- Put controllers in `src/Controller/`, extending `ControllerBase` and returning render arrays. Put forms in `src/Form/`, extending `FormBase`, or `ConfigFormBase` for a settings form whose elements save through `#config_target`.
- Change state only from a Form API form or from a route with `_csrf_token: 'TRUE'`.
- Rebuild caches after changing routes, services or plugins: `ahoy drush cr`.
- Use the `secure-code` skill for any code that handles input, access, queries or output.

## Ship configuration with the module

- Put the module's default configuration in `config/install/`, and configuration that depends on another optional module in `config/optional/`. Drupal installs it once, when the module is installed. Existing sites receive later changes only through the exported configuration in `config/default` or through an update hook.
- Describe every configuration item the module defines in `config/schema/ys_base.schema.yml`.
- Values that differ per environment go in a settings file, `web/sites/default/includes/modules/settings.ys_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
- Export configuration changes with the `manage-config` skill.

[//]: # (#;< TOOL_PHPUNIT)

- When you add a settings file, add its assertions to the settings tests in `tests/phpunit/Drupal/`.

[//]: # (#;> TOOL_PHPUNIT)

## Choose an update hook or a deploy hook

| Hook                      | File                      | Run by                                           | Use for                                                                        |
|---------------------------|---------------------------|--------------------------------------------------|--------------------------------------------------------------------------------|
| `hook_update_N()`         | `ys_base.install`         | `drush updatedb`, before the configuration import | Database schema changes and data fixes that don't need the full entity API     |
| `hook_post_update_NAME()` | `ys_base.post_update.php` | `drush updatedb`, after every `hook_update_N()`  | Saving entities or configuration with the full API                             |
| `hook_deploy_NAME()`      | `ys_base.deploy.php`      | `drush deploy:hook`, after the configuration import | Site setup that relies on imported configuration: blocks, menu links, terms, content |

- Number update hooks from `11001` upward, as `ys_base_update_11001()`. Never renumber or reuse a number that has shipped.
- Drupal records the latest update number when it installs a module and marks the module's post-update hooks as run. Neither runs on a new install, so do the same work in `hook_install()` when a fresh install needs it.
- Drush runs each deploy hook once per environment and records its name in the `deploy_hook` key-value store. Every deploy hook of a newly installed module runs on the next `drush deploy:hook`. Never rename a deploy hook that has run anywhere, because the new name runs again.
- Make every deploy hook safe to run on a site where its work already exists: load the block, link or entity before you create it, and return early when it exists. Return a message for Drush to print.
- Skip work by environment with `\Drupal\Core\Site\Settings::get('environment')` and the `ENVIRONMENT_*` constants that `settings.php` defines, such as `ENVIRONMENT_PROD`.
- When an update hook changes configuration, export the same change to `config/default`. Otherwise, the configuration import that follows `updatedb` reverts it. Setting `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE=1` makes provisioning fail when that happens.
- Process large data sets in passes through the `$sandbox` argument and `$sandbox['#finished']`.

[//]: # (#;< MODULE_DRUPAL_HELPERS)

- The Drupal helpers facades keep deploy hooks short, for example `Helper::term()->createTree()` and `Helper::menu()->createTree()`, and `Helper::entity($sandbox)->batchEntity()` batches across passes. See [Drupal helpers](https://www.vortextemplate.com/docs/development/modules/drupal-helpers).

[//]: # (#;> MODULE_DRUPAL_HELPERS)

### How provisioning runs them

`ahoy provision` runs `vendor/bin/vortex-provision` in the `cli` container. After it prepares the database, it runs these steps in order:

1. `drush updatedb --no-cache-clear`, then `drush cache:rebuild`
2. `drush config:import`, only when `config/default` holds `.yml` files
3. `drush cache:rebuild`, then `drush deploy:hook`
4. Database sanitization
5. Every `scripts/provision-*.sh` script, in name order

`scripts/provision-00-enable-demo-modules.sh` installs the custom site modules and runs `drush deploy:hook` again, so a module it installs has its deploy hooks run in the same provision. See [Provision](https://www.vortextemplate.com/docs/development/provision).

To run the hooks on the local site without provisioning again:

1. List the pending update hooks: `ahoy drush updatedb:status`
2. Run them: `ahoy drush updatedb`
3. List the pending deploy hooks: `ahoy drush deploy:hook-status`
4. Run them: `ahoy drush deploy:hook`

`ahoy drush deploy` runs `updatedb`, `config:import`, `cache:rebuild` and `deploy:hook` in 1 command. Use it only after `config/default` holds a full export, and preview the import with the `manage-config` skill first. With an empty `config/default`, the import stops with `The core.extension configuration does not exist.`

To run the whole sequence, ask the developer first, because it replaces the local database when `VORTEX_PROVISION_OVERRIDE_DB=1` is set. Then run `AHOY_CONFIRM_RESPONSE=y ahoy provision`.

## Enable the module on every environment

1. Install it locally: `ahoy drush pm:install ys_base`
2. Confirm it is enabled: `ahoy drush pm:list --type=module --status=enabled`
3. Choose how the other environments install it, from the 2 options below.

- **Through configuration.** Once the project exports its configuration, `config/default/core.extension.yml` lists every enabled module. The configuration import during provisioning installs a newly listed module on every environment, production included, before the deploy hooks run. Export `core.extension` and the module's configuration with the `manage-config` skill. This is the path for any module production needs.
- **Through a provision script.** The template ships `config/default` without configuration, so provisioning skips the import until the project exports it. Until then, modules come from the provision scripts: `scripts/provision-00-enable-demo-modules.sh` runs `drush pm:install` for the shipped modules. That script exits before installing anything in production, so a module installed only there never reaches production. To install a module on every environment before the configuration is exported, add your own `scripts/provision-<NN>-<name>.sh` with the environment checks it needs.

A module that must never reach production, such as a development tool, stays out of the export through `$settings['config_exclude_modules'][]` in its `web/sites/default/includes/modules/settings.<module>.php` file, as the shipped development modules do.

## Add tests

Read `docs/testing.md` for the testing conventions this project agreed on. Put each test where its tool collects it.

[//]: # (#;< TOOL_PHPUNIT)

- Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\ys_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
- Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
- Use the `write-tests` skill to decide what to test and how.

[//]: # (#;< CUSTOM_MODULE_BASE)

- `web/modules/custom/ys_base/tests/src/` provides `YsBaseUnitTestBase`, `YsBaseKernelTestBase`, `YsBaseFunctionalTestBase` and `YsBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\ys_base\<Type>`.

[//]: # (#;> CUSTOM_MODULE_BASE)

[//]: # (#;> TOOL_PHPUNIT)

[//]: # (#;< TOOL_JEST)

- Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.

[//]: # (#;> TOOL_JEST)

[//]: # (#;< TOOL_BEHAT)

- Put end-to-end scenarios in `tests/behat/features/` at the project root, not in the module.

[//]: # (#;> TOOL_BEHAT)

## Before you finish

- Caches are rebuilt with `ahoy drush cr` after the last change to services, hooks, routes or plugins.
- Every changed route, form and block works on the local site. Open them as an administrator with the link from `ahoy login`.
- `ahoy drush watchdog:show --count=20` shows no new errors.
- `ahoy drush updatedb:status` and `ahoy drush deploy:hook-status` list no pending hooks after you ran them.
- Configuration changes are exported, and the developer knows how the module reaches production.
- `ahoy lint` passes.
