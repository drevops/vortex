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

Defined in: `.drevops/docs/.utils/variables/extra/.env.local.example.variables.sh`

### `CLAMAV_MODE`

ClamAV mode.

Run ClamAV in either daemon mode by setting it to `0` (or 'daemon') or in<br />executable mode by setting it to `1`.

Default value: `daemon`

Defined in: `.env`

### `COMPOSE_PROJECT_NAME`

Docker Compose project name (all containers will have this name). Defaults<br />to the name of the project directory.

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

### `DREVOPS_CI_TEST_RESULTS`

Directory to store test results in CI.

Default value: `/tmp/tests`

Defined in: `CI config`

### `DREVOPS_CI_TWIGCS_IGNORE_FAILURE`

Ignore Twigcs failures.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_CLAMAV_ENABLED`

Enable ClamAV integration.

Default value: `1`

Defined in: `.env`

### `DREVOPS_COMPOSER_VERBOSE`

Print debug information from Composer install.

Default value: `1`

Defined in: `.env`

### `DREVOPS_DB_DIR`

Database dump data directory (file or Docker image archive).

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

Database dump file source from CURL, with optional HTTP Basic Authentication<br />credentials embedded into the value.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_DB_DOWNLOAD_FORCE`

Set to `1` to override existing downloaded DB dump without asking.

Default value: `UNDEFINED`

Defined in: `.drevops/docs/.utils/variables/extra/.env.local.example.variables.sh`

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

Database dump file name (Docker image archive will use '.tar' extension).

Default value: `db.sql`

Defined in: `.env`

### `DREVOPS_DEBUG`

Set to `1` to print debug information in DrevOps scripts.

Default value: `UNDEFINED`

Defined in: `.drevops/docs/.utils/variables/extra/.env.local.example.variables.sh`

### `DREVOPS_DEPLOY_TYPES`

The type of deployment.

Combination of comma-separated values to support multiple deployments:<br />`artifact`,`docker`, `webhook`, `lagoon`.

See https://docs.drevops.com/deploy

Default value: `artifact`

Defined in: `.env`

### `DREVOPS_DOCKER_VERBOSE`

Print debug information from Docker build.

Default value: `1`

Defined in: `.env`, `.drevops/docs/.utils/variables/extra/.env.local.example.variables.sh`

### `DREVOPS_DRUPAL_ADMIN_EMAIL`

Drupal admin email. May need to be reset if database was sanitized.

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DREVOPS_DRUPAL_DB_SANITIZE_EMAIL`

Sanitization email pattern. Sanitization is enabled by default in all<br />non-production environments.<br />@see https://docs.drevops.com/build#sanitization

Default value: `user_%uid@your-site-url.example`

Defined in: `.env`

### `DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD`

Password replacement used for sanitised database.

Default value: `<RANDOM STRING>`

Defined in: `.env`

### `DREVOPS_DRUPAL_PROFILE`

Drupal profile name (used only when installing from profile).

Default value: `your_site_profile`

Defined in: `.env`

### `DREVOPS_DRUPAL_SITE_EMAIL`

Drupal site email (used only when installing from profile).

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DREVOPS_DRUPAL_SITE_NAME`

Drupal site name (used only when installing from profile).

Default value: `YOURSITE`

Defined in: `.env`

### `DREVOPS_DRUPAL_THEME`

Drupal theme name.

Default value: `your_site_theme`

Defined in: `.env`

### `DREVOPS_DRUPAL_UNBLOCK_ADMIN`

Unblock admin account when logging in.

Default value: `1`

Defined in: `.env`

### `DREVOPS_DRUPAL_VERSION`

Drupal version.

Default value: `10`

Defined in: `.env`

### `DREVOPS_ENVIRONMENT`

Override detected environment type.

Used in the application to override the automatically detected environment type.

Default value: `UNDEFINED`

Defined in: `ENVIRONMENT`

### `DREVOPS_EXPORT_CODE_DIR`

Directory to store exported code.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED`

Proceed with Docker image deployment after it was exported.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_NOTIFY_CHANNELS`

The channels of the notifications.

Can be a combination of comma-separated values: email,newrelic,github,jira

Default value: `email`

Defined in: `.env`

### `DREVOPS_NOTIFY_EMAIL_FROM`

Email to send notifications from.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_NOTIFY_EMAIL_RECIPIENTS`

Email address(es) to send notifications to.

Multiple names can be specified as a comma-separated list of email addresses<br />with optional names in the format "email|name".<br />Example: "to1@example.com|Jane Doe, to2@example.com|John Doe"

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_NPM_VERBOSE`

Print debug information from NPM install.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PRODUCTION_BRANCH`

Dedicated branch to identify production environment.

Default value: `main`

Defined in: `.env`

### `DREVOPS_PROJECT`

Project name.<br />Drives internal naming within the codebase.<br />Does not affect the names of containers and development URL - those depend on<br />the project directory and can be overridden with COMPOSE_PROJECT_NAME.

Default value: `your_site`

Defined in: `.env`

### `DREVOPS_PROVISION_ACQUIA_SKIP`

Skip Drupal site provisioning in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_PROVISION_OVERRIDE_DB`

Flag to always overwrite existing database. Usually set to `0` in deployed<br />environments and can be temporary set to `1` for a specific deployment.<br />Set this to `1` in .env.local to override when developing localy.

Default value: `UNDEFINED`

Defined in: `.env`, `.env.local.default`

### `DREVOPS_PROVISION_USE_MAINTENANCE_MODE`

Put the site into a maintenance mode during site provisioning phase.

Default value: `1`

Defined in: `.env`

### `DREVOPS_PROVISION_USE_PROFILE`

Set to `1` to install a site from profile instead of database file dump.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_PURGE_CACHE_ACQUIA_SKIP`

Skip purging of edge cache in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_REDIS_ENABLED`

Enable Redis integration.<br />See settings.redis.php for details.

Default value: `UNDEFINED`

Defined in: `.env`

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

### `DRUPAL_SHIELD_PRINT`

Shield print message.

Default value: `Restricted access.`

Defined in: `.env`

### `DRUPAL_STAGE_FILE_PROXY_ORIGIN`

Stage file proxy origin. Note that HTTP Auth provided by Shield will be<br />automatically added to the origin URL.

Default value: `https://your-site-url.example/`

Defined in: `.env`

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

