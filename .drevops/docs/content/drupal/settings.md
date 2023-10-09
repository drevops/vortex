# Settings

The `settings.php` file provides the primary configuration for a Drupal site,
including its database connection, file paths, and various other settings.

DrevOps ships with own streamlined version of
the [`settings.php`](../../../../web/sites/default/settings.php) and
[`services.yml`](../../../../web/sites/default/services.yml) files. There are
also [Settings unit tests](#Testing-settings-with-unit-tests) that ensure that
the settings apply correctly per environment.

The default Drupal scaffold's [`default.settings.php`](../../../../web/sites/default/default.settings.php)
and [`default.services.yml`](../../../../web/sites/default/default.services.yml)
files are also provided if you choose to use them instead.

The [`settings.php`](../../../../web/sites/default/settings.php) file is divided
into several sections:

1. [Environment constants definitions](#1-Environment-constants-definitions)
2. [Site-specific settings](#2-Site-specific-settings)
3. [Environment detection](#3-Environment-detection)
4. [Per-environment overrides](#4-Per-environment-overrides)
5. [Inclusion of generated Settings](#5-Inclusion-of-per-module-settings)
6. [Inclusion of local settings](#6-Inclusion-of-local-settings)

### 1. Environment constants definitions

Constants for various environments are defined here. These can be used to alter
site behavior based on the active environment.

Available constants include:

- `ENVIRONMENT_LOCAL`
- `ENVIRONMENT_CI`
- `ENVIRONMENT_PROD`
- `ENVIRONMENT_TEST`
- `ENVIRONMENT_DEV`

These are later used to set `$settings['environment']` variable, which can be
used in the modules and outside scripts to target code execution to specific
environments.

!!! Example

    ```shell
    environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
    if echo "${environment}" | grep -q -e ci -e local; then
      # Do something only in ci, or local environments.
    fi
    ```

!!! note

    The `DREVOPS_PROVISION_ENVIRONMENT` shell variable can be utilized within
    post-provision custom scripts, allowing targeted code execution based on
    specific environments. Refer to [Provision](provision.md) for additional
    information.

### 2. Site-specific settings

This section is used for configuring core site settings such as defining paths,
ensuring security with trusted host patterns, setting performance optimizations
like aggregating CSS and JS files, and specifying essential directories for
Drupal's functionality.

These settings are identical for all environments.

Use per-module settings files in the `/includes` directory to override
per-module settings, including per-environment overrides.

### 3. Environment detection

This section uses known per-platform mechanisms to determine in which
environment the site currently runs. The result is stored in the
`$settings['environment']` variable.

By default, `$settings['environment']` is set to `ENVIRONMENT_LOCAL`.
This value is then overridden by the environment detection logic.

!!! note

    In any hosting environment, the default value of `$settings['environment']`
    changes to `ENVIRONMENT_DEV`, while further refinements designate more
    advanced environments.


#### Environment detection override

It is also possible to force specific environment by setting
`DREVOPS_ENVIRONMENT` environment variable. In this case, the environment
detection will take place and will load any additional per-platform settings,
but the final value of `$settings['environment']` will be set to the value of
`DREVOPS_ENVIRONMENT` variable.

This is useful in cases where a certain behaviour is required for a specific
environment, but the environment detection logic does not provide it. Or as a
temporary override during testing.

### 4. Per-environment overrides

Configurations in this section alter the site's behavior based on the
environment. Out-of-the-box, DrevOps provides overrides for CI and Local
environments.

You can add additional overrides for other environments as needed.

### 5. Inclusion of per-module settings

This section includes any additional module-specific settings from the
`/includes` directory.

DrevOps ships with settings overrides for several popular contributed modules
used in almost every project.

The per environment overrides for modules should be also placed into files
in the `/includes` directory.

### 6. Inclusion of local settings

At the end of the `settings.php`, there is an option to include additional local
settings. This allows developers to override some settings for their local
environment without affecting the main configuration. Developers can
copy `default.settings.local.php` and `default.services.local.yml`
to `settings.local.php` and `services.local.yml`, respectively, to utilize this
functionality.

## Testing settings with unit tests

DrevOps provides a [set of unit tests](../../../../tests/phpunit/Drupal) that
ensure that the settings apply correctly per environment. These tests are
supposed to be maintained within your project, ensuring that settings activated
by specific environments and environment variables are applied accurately.

After installing DrevOps, run `vendor/bin/phpunit --group=drupal_settings` to
run the tests for the settings provided by DrevOps.

You may simply remove these tests if you do not want to maintain them.
