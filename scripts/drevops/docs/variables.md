# Variables

## Rules

1. All DrevOps variables MUST start with `DREVOPS_`.<br/>
   This is to clearly distinguish between DrevOps and 3rd party variables.
2. All DrevOps action-specific variables MUST have a prefix. If action has a
   specific service provider attached - a second prefix MUST be added.<br/>
   For example, to specify API key for NewRelic deployment notification call,
   the variable name is `DREVOPS_NOTIFY_NEWRELIC_API_KEY`, where `NOTIFY` is an
   "action" and `NEWRELIC` is a "service provider".
3. Project-base variables SHOULD start with `DRUPAL_` prefix and have a second
   prefix of the module name.<br/>
   This is to clearly distinguish between DrevOps, 3rd party services variables
   and Drupal variables.<br/>
   For example, to specify a user for Drupal's Shield module configuration,
   use `DRUPAL_SHIELD_USER`, where `DRUPAL` is a prefix and `SHIELD` is a module
   name.
4. Variables SHOULD NOT be exported into the global scope unless absolutely
   necessary.<br/>
   Therefore, values in `.env` SHOULD have default values set, but SHOULD be
   commented out. This provides visibility, but prevent global scope exposure.

## Override order (bottom values win):

- default value in container
- default value in `docker-compose.yml`
- value in `.env` (last value wins)
- value in `.env.local` (last value wins)
- value from environment

## Variables list

### `DREVOPS_APP`

Path to the root of the project inside of the container.

Default value: `app`

### `DREVOPS_BUILD_VERBOSE`

Starting containers will suppress STDOUT output, but will still show any STDERR output. Set this to `1` to allow STDOUT output.

Default value: `<NOT SET>`

### `DREVOPS_COMMIT`

Allows to pin DrevOps to a specific commit when updating with `ahoy update`. If this is not set, the latest release of DrevOps for specified [`$DREVOPS_DRUPAL_VERSION`](#drevops_drupal_version) will be used.

Default value: `<NOT SET>`

### `DREVOPS_COMPOSER_VALIDATE_LOCK`

Validate composer lock file.

Default value: `1`

### `DREVOPS_DB_DIR`

Directory with database dump data (file or Docker image archive).

Default value: `data`

### `DREVOPS_DB_DOCKER_IMAGE`

Database-in-Docker-image database storage. Allows to store database in Docker image for local development and in CI. This allows to avoid waiting for long database imports for large databases when bulding sites. Note that the source database coming from the production environment can still be imported as a dump file if [`$DREVOPS_DB_DOWNLOAD_SOURCE`](#drevops_db_download_source)!=docker_registry or can be using previsous version of the image if [`$DREVOPS_DB_DOWNLOAD_SOURCE`](#drevops_db_download_source)=docker_registry. Database image name in format <org>/<image_name>:<label>. Use drevops/drevops-mariadb-drupal-data as a starting Docker image for your Database-in-Docker-image database. @see https://github.com/drevops/mariadb-drupal-data IMPORATANT! Providing a value for this variable switches the database storage mechanism and other underlying operations to use database-in-Docker-image for development and CI, so be cautios when making this change (i.e. the workflow is controlled from a single variable, which means that "with great power comes great responsibility").

Default value: `<NOT SET>`

### `DREVOPS_DB_DOWNLOAD_CURL_URL`

Database dump file source: CURL. Provide a URL to the DB dump file with optional HTTP Basic Authentication creadentials embedded into URL value.

Default value: `<NOT SET>`

### `DREVOPS_DB_DOWNLOAD_FTP_FILE`

Default value: `db.sql`

### `DREVOPS_DB_DOWNLOAD_FTP_HOST`

Database dump file source: FTP. Note that for CI, these variables should be set through UI.

Default value: `<NOT SET>`

### `DREVOPS_DB_DOWNLOAD_FTP_PASS`

Default value: `<NOT SET>`

### `DREVOPS_DB_DOWNLOAD_FTP_PORT`

Default value: `21`

### `DREVOPS_DB_DOWNLOAD_FTP_USER`

Default value: `<NOT SET>`

### `DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT`

Lagoon environment to download DB from.

Default value: `master`

### `DREVOPS_DB_DOWNLOAD_SOURCE`

Where the database is downloaded from: - "url" - directly from URL as a file using CURL. - "ftp" - directly from FTP as a file using CURL. - "acquia" - from latest Acquia backup via Cloud API as a file. - "lagoon" - from Laggon master enveronment as a file. - "docker_registry" - from the docker registry as a docker image. - "none" - not downloaded, site is freshly installed for every build. Note that "docker_registry" works only for database-in-Docker-image database storage (when [`$DREVOPS_DB_DOCKER_IMAGE`](#drevops_db_docker_image) variable has a value).

Default value: `curl`

### `DREVOPS_DB_EXPORT_BEFORE_IMPORT`

Set to `1` in order to enable DB exporting before importing. Useful to backup DB during development.

Default value: `<NOT SET>`

### `DREVOPS_DB_FILE`

Database dump file name. Note that Docker image archive will use the same file name, but with '.tar' extension.

Default value: `db.sql`

### `DREVOPS_DEBUG`

Set to `1` to print debug information in DrevOps scripts.

Default value: `<NOT SET>`

### `DREVOPS_DEPLOY_PROCEED`

Flag to proceed with deployment. Set to "`1`" once the deployment configuration is configured in CI and is ready. @see scripts/drevops/deploy-<type>.sh for more variables.

Default value: `<NOT SET>`

### `DREVOPS_DEPLOY_TYPE`

The type of deployemt. Combination of comma-separated values to support multiple deployments: "code", "docker", "webhook", "lagoon".

Default value: `<NOT SET>`

### `DREVOPS_DOCKER_REGISTRY`

Docker registry.

Default value: `docker.io`

### `DREVOPS_DOCKER_REGISTRY_TOKEN`

Default value: `<NOT SET>`

### `DREVOPS_DOCKER_REGISTRY_USERNAME`

Docker registry credentials to read and write Docker images. Note that for CI, these variables should be set through UI.

Default value: `<NOT SET>`

### `DREVOPS_DRUPAL_ADMIN_EMAIL`

Drupal admin email. Leave empty to not reset.

Default value: `webmaster@your-site-url`

### `DREVOPS_DRUPAL_BUILD_WITH_MAINTENANCE_MODE`

Set to `1` to put the site into a maintenance mode during deployment.

Default value: `1`

### `DREVOPS_DRUPAL_DB_SANITIZE_EMAIL`

Sanitization email pattern.

Default value: `uid@your-site-url`

### `DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL`

Replace username with email. Useful on sites where username is email.

Default value: `1`

### `DREVOPS_DRUPAL_FORCE_FRESH_INSTALL`

Set to `1` to force fresh install even if the site exists. Useful for profile-based deployments into existing environments.

Default value: `<NOT SET>`

### `DREVOPS_DRUPAL_PROFILE`

Drupal profile name.

Default value: `your_site_profile`

### `DREVOPS_DRUPAL_SITE_EMAIL`

Drupal site name.

Default value: `webmaster@your-site-url`

### `DREVOPS_DRUPAL_SITE_NAME`

Drupal site name.

Default value: `YOURSITE`

### `DREVOPS_DRUPAL_THEME`

Drupal theme name.

Default value: `your_site_theme`

### `DREVOPS_DRUPAL_UNBLOCK_ADMIN`

Unblock admin account during deployment.

Default value: `1`

### `DREVOPS_DRUPAL_VERSION`

Drupal version.

Default value: `9`

### `DREVOPS_LAGOON_INTEGRATION_COMPLETE`

Set this to `1` once Lagoon integration is complete. This will provide access to Lagoon environments from the CLI container.

Default value: `<NOT SET>`

### `DREVOPS_LAGOON_PRODUCTION_BRANCH`

Dedicated branch to identify production environment. See settings.php for more details.

Default value: `master`

### `DREVOPS_LINT_BE_ALLOW_FAILURE`

Flag to allow BE code linting failures.

Default value: `<NOT SET>`

### `DREVOPS_LINT_FE_ALLOW_FAILURE`

Flag to allow FE code linting failures.

Default value: `<NOT SET>`

### `DREVOPS_LINT_PHPCS_TARGETS`

Comma-separated list of PHPCS targets (no spaces).

Default value: `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests`

### `DREVOPS_LINT_PHPLINT_EXTENSIONS`

PHP Parallel Lint extensions as a comma-separated list of extensions with no preceding dot or space.

Default value: `php, inc, module, theme, install`

### `DREVOPS_LINT_PHPLINT_TARGETS`

PHP Parallel Lint targets as a comma-separated list of extensions with no preceding dot or space.

Default value: `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests`

### `DREVOPS_LOCALDEV_URL`

Local development URL (no trailing slashes).

Default value: `your-site.docker.amazee.io`

### `DREVOPS_MARIADB_HOST`

Database connection details. Note that these are not used in production.

Default value: `mariadb`

### `DREVOPS_MARIADB_PASSWORD`

Default value: `drupal`

### `DREVOPS_MARIADB_PORT`

Default value: `3306`

### `DREVOPS_MARIADB_USER`

Default value: `drupal`

### `DREVOPS_PROJECT`

Project name.

Default value: `your_site`

### `DREVOPS_TEST_BDD_ALLOW_FAILURE`

Flag to allow BDD tests to fail.

Default value: `<NOT SET>`

### `DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE`

Flag to allow Functional tests to fail.

Default value: `<NOT SET>`

### `DREVOPS_TEST_KERNEL_ALLOW_FAILURE`

Flag to allow Kernel tests to fail.

Default value: `<NOT SET>`

### `DREVOPS_TEST_UNIT_ALLOW_FAILURE`

Flag to allow Unit tests to fail.

Default value: `<NOT SET>`

