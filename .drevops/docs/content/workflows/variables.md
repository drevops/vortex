# Variables

Environment variables allow to configure workflows.

## Override order (bottom values win):

- default value in container taken from image
- default value in `docker-compose.yml`
- value in `.env` (last value wins)
- value in `.env.local` (last value wins)
- value from environment

## Variables list

### `AHOY_CONFIRM_RESPONSE`

Set to `y` to suppress Ahoy prompts.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `AHOY_CONFIRM_WAIT_SKIP`

When Ahoy prompts are suppressed ([`$AHOY_CONFIRM_RESPONSE`](#AHOY_CONFIRM_RESPONSE) is `1`), the command<br />will wait for `3` seconds before proceeding.<br />Set this variable to "`1`" to skip the wait.

Default value: `1`

Defined in: `.env.local.default`

### `COMPOSE_PROJECT_NAME`

Docker Compose project name.

Sets the project name for a Docker Compose project. Influences container and<br />network names.

Defaults to the name of the project directory.

Default value: `UNDEFINED`

Defined in: `ENVIRONMENT`

### `DOCKER_PASS`

The password (token) to log into the Docker registry.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DOCKER_REGISTRY`

Docker registry name.

Provide port, if required as `<server_name>:<port>`.

Default value: `docker.io`

Defined in: `.env`

### `DOCKER_USER`

The username to log into the Docker registry.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_ACQUIA_APP_NAME`

Acquia application name to download the database from.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_CI_ARTIFACTS`

Directory to store test artifacts in CI.

Default value: `/tmp/artifacts`

Defined in: `CI config`

### `DREVOPS_CI_BEHAT_IGNORE_FAILURE`

Ignore Behat test failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_BEHAT_PROFILE`

Test Behat profile to use in CI. If not set, the `default` profile will be used.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_NPM_LINT_IGNORE_FAILURE`

Ignore NPM linters failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_PHPCS_IGNORE_FAILURE`

Ignore PHPCS failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_PHPMD_IGNORE_FAILURE`

Ignore PHPMD failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_PHPSTAN_IGNORE_FAILURE`

Ignore PHPStan failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_PHPUNIT_IGNORE_FAILURE`

Ignore PHPUnit test failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_RECTOR_IGNORE_FAILURE`

Ignore Rector failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CI_TEST_RESULTS`

Directory to store test results in CI.

Default value: `/tmp/tests`

Defined in: `CI config`

### `DREVOPS_CI_TWIGCS_IGNORE_FAILURE`

Ignore Twigcs failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_COMPOSER_VERBOSE`

Print output from Composer install.

Default value: `1`

Defined in: `.env`

### `DREVOPS_DB_DIR`

Database dump directory.

Default value: `./.data`

Defined in: `.env`

### `DREVOPS_DB_DOCKER_IMAGE`

Name of the database docker image to use.

See https://github.com/drevops/mariadb-drupal-data to seed your DB image.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_DB_DOCKER_IMAGE_BASE`

Name of the database fall-back docker image to use.

If the image specified in [`$DREVOPS_DB_DOCKER_IMAGE`](#DREVOPS_DB_DOCKER_IMAGE) does not exist and base<br />image was provided - it will be used as a "clean slate" for the database.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_DB_NAME`

Acquia database name to download the database from.

Default value: `your_site`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_ENV`

Acquia environment to download the database from.

Default value: `prod`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_CURL_URL`

Database dump file sourced from CURL, with optional HTTP Basic Authentication<br />credentials embedded into the value.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_FORCE`

Always override existing downloaded DB dump.

Default value: `1`

Defined in: `.env.local.default`, `.env.local.default`

### `DREVOPS_DB_DOWNLOAD_FTP_FILE`

Database dump FTP file name.

Default value: `db.sql`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_FTP_HOST`

Database dump FTP host.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_FTP_PASS`

Database dump FTP password.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_DB_DOWNLOAD_FTP_PORT`

Database dump FTP port.

Default value: `21`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_FTP_USER`

Database dump FTP user.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_DB_DOWNLOAD_LAGOON_BRANCH`

Lagoon environment to download the database from.

Default value: `main`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_SOURCE`

Database can be sourced from one of the following locations:

- `url` - directly from URL as a file using CURL.
- `ftp` - directly from FTP as a file using CURL.
- `acquia` - from the latest Acquia backup via Cloud API as a file.
- `lagoon` - from Lagoon main environment as a file.
- `docker_registry` - from the docker registry as a docker image.
- `none` - not downloaded, site is freshly installed for every build.

Note that "docker_registry" works only for database-in-Docker-image<br />database storage (when [`$DREVOPS_DB_DOCKER_IMAGE`](#DREVOPS_DB_DOCKER_IMAGE) variable has a value).

Default value: `curl`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE`

SSH key file used to access Lagoon environment to download the database.<br />Create an SSH key and add it to your account in the Lagoon Dashboard.

Default value: `HOME/.ssh/id_rsa`

Defined in: `.env.local.default`

### `DREVOPS_DB_FILE`

Database dump file name.

Default value: `db.sql`

Defined in: `.env`

### `DREVOPS_DEBUG`

Set to `1` to print debug information in DrevOps scripts.

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `DREVOPS_DEPLOY_TYPES`

The type of deployment.

Combination of comma-separated values to support multiple deployment targets:<br />`artifact`,`docker`, `webhook`, `lagoon`.

See https://docs.drevops.com/workflows/deploy

Default value: `artifact`

Defined in: `.env`

### `DREVOPS_DOCKER_VERBOSE`

Set to `1` to print debug information from Docker build.

Default value: `UNDEFINED`

Defined in: `.env.local.default`, `.env`

### `DREVOPS_EXPORT_CODE_DIR`

Directory to store exported code.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED`

Proceed with Docker image deployment after it was exported.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_LAGOON_PRODUCTION_BRANCH`

Dedicated branch to identify the production environment.

Default value: `main`

Defined in: `.env`

### `DREVOPS_LOCALDEV_URL`

Local development URL.<br />Override only if you need to use a different URL than the default.

Default value: `<current_dir>.docker.amazee.io`

Defined in: `.env.local.default`

### `DREVOPS_NOTIFY_CHANNELS`

The channels of the notifications.

Can be a combination of comma-separated values: email,newrelic,github,jira

Default value: `email`

Defined in: `.env`

### `DREVOPS_NOTIFY_EMAIL_FROM`

Email to send notifications from.

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DREVOPS_NOTIFY_EMAIL_RECIPIENTS`

Email address(es) to send notifications to.

Multiple names can be specified as a comma-separated list of email addresses<br />with optional names in the format "email|name".<br />Example: "to1@example.com|Jane Doe, to2@example.com|John Doe"

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DREVOPS_NPM_VERBOSE`

Print output from NPM install.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PROJECT`

Project name.

Drives internal naming within the codebase.<br />Does not affect the names of containers and development URL - those depend on<br />the project directory and can be overridden with [`$COMPOSE_PROJECT_NAME`](#COMPOSE_PROJECT_NAME).

Default value: `your_site`

Defined in: `.env`

### `DREVOPS_PROVISION_ACQUIA_SKIP`

Skip Drupal site provisioning in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_PROVISION_OVERRIDE_DB`

Overwrite existing database if it exists.

Usually set to `0` in deployed environments and can be temporary set to `1` for<br />a specific deployment.<br />Set this to `1` in .env.local to override when developing locally.

Default value: `UNDEFINED`

Defined in: `.env`, `.env.local.default`

### `DREVOPS_PROVISION_SANITIZE_DB_EMAIL`

Sanitization email pattern. Sanitization is enabled by default in all<br />non-production environments.<br />@see https://docs.drevops.com/workflows/build#sanitization

Default value: `user_%uid@your-site-url.example`

Defined in: `.env`

### `DREVOPS_PROVISION_SANITIZE_DB_PASSWORD`

Password replacement used for sanitised database.

Default value: `<RANDOM STRING>`

Defined in: `.env`

### `DREVOPS_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL`

Replace username with email after database sanitization. Useful when email<br />is used as username.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PROVISION_SANITIZE_DB_SKIP`

Skip database sanitization.

Database sanitization is enabled by default in all non-production<br />environments and is always skipped in the production environment.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PROVISION_USE_MAINTENANCE_MODE`

Put the site into a maintenance mode during site provisioning.

Default value: `1`

Defined in: `.env`

### `DREVOPS_PROVISION_USE_PROFILE`

Set to `1` to install a site from profile instead of the database file dump.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PURGE_CACHE_ACQUIA_SKIP`

Skip purging of edge cache in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SKIP`

Skip copying of database between Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SKIP`

Skip copying of files between Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TZ`

The timezone for the containers.

Default value: `Australia/Melbourne`

Defined in: `.env`

### `DREVOPS_WEBROOT`

Name of the webroot directory with Drupal codebase.

Default value: `web`

Defined in: `.env`

### `DRUPAL_ADMIN_EMAIL`

Drupal admin email. May need to be reset if database was sanitized.

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DRUPAL_CLAMAV_ENABLED`

Enable ClamAV integration.

Default value: `1`

Defined in: `.env`

### `DRUPAL_CLAMAV_MODE`

ClamAV mode.

Run ClamAV in either daemon mode by setting it to `0` (or 'daemon') or in<br />executable mode by setting it to `1`.

Default value: `daemon`

Defined in: `.env`

### `DRUPAL_ENVIRONMENT`

Override detected Drupal environment type.

Used in the application to override the automatically detected environment type.

Default value: `UNDEFINED`

Defined in: `ENVIRONMENT`

### `DRUPAL_PROFILE`

Drupal profile name (used only when installing from profile).

Default value: `your_site_profile`

Defined in: `.env`

### `DRUPAL_REDIS_ENABLED`

Enable Redis integration.<br />See settings.redis.php for details.

Default value: `UNDEFINED`

Defined in: `.env`

### `DRUPAL_SHIELD_PRINT`

Shield print message.

Default value: `Restricted access.`

Defined in: `.env`

### `DRUPAL_SITE_EMAIL`

Drupal site email.<br />Used only when installing from profile.

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DRUPAL_SITE_NAME`

Drupal site name.<br />Used only when installing from profile.

Default value: `DREVOPS_PROJECT`

Defined in: `.env`

### `DRUPAL_STAGE_FILE_PROXY_ORIGIN`

Stage file proxy origin. Note that HTTP Auth provided by Shield will be<br />automatically added to the origin URL.

Default value: `https://your-site-url.example/`

Defined in: `.env`

### `DRUPAL_THEME`

Drupal theme name.

Default value: `your_site_theme`

Defined in: `.env`

### `DRUPAL_UNBLOCK_ADMIN`

Unblock admin account when logging in.

Default value: `1`

Defined in: `.env`

### `GITHUB_TOKEN`

GitHub token used to overcome API rate limits or access private repositories.<br />@see https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token

Default value: `UNDEFINED`

Defined in: `.env.local.default`

### `LAGOON_PROJECT`

Lagoon project name. May be different from [`$DREVOPS_PROJECT`](#DREVOPS_PROJECT).

Default value: `your_site`

Defined in: `.env`

### `NEWRELIC_ENABLED`

Enable New Relic in Lagoon environment.

Set as project-wide variable.

Default value: `UNDEFINED`

Defined in: `LAGOON ENVIRONMENT`

### `NEWRELIC_LICENSE`

New Relic license.

Set as project-wide variable.

Default value: `UNDEFINED`

Defined in: `LAGOON ENVIRONMENT`

