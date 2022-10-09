# Variables

## Rules

1. Local variables are written in lowercase, global - uppercase.

2. All DrevOps variables MUST start with `DREVOPS_`.<br/>
   This is to clearly distinguish between DrevOps and 3rd party variables.

3. All DrevOps action-specific script variables MUST be namespaced within own
   script and map their default values to the global DrevOps variables.

4. All DrevOps action-specific variables MUST be namespaced with a prefix.
   If action has a specific service provider attached - a second prefix MUST
   be added.<br/>
   For example, to specify API key for New Relic deployment notification call,
   the variable name is `DREVOPS_NOTIFY_NEWRELIC_API_KEY`, where `NOTIFY` is an
   "action" and `NEWRELIC` is a "service provider".

5. Project-base variables SHOULD start with `DRUPAL_` prefix and have a second
   prefix of the module name.<br/>
   This is to clearly distinguish between DrevOps, 3rd party services variables
   and Drupal variables.<br/>
   For example, to specify a user for Drupal's Shield module configuration,
   use `DRUPAL_SHIELD_USER`, where `DRUPAL` is a prefix and `SHIELD` is a module
   name.

6. Variables SHOULD NOT be exported into the global scope unless absolutely
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

### `COMPOSE_PROJECT_NAME`

Docker Compose project name (all containers will have this name). Defaults to the name of the project directory.

Default value: `UNDEFINED`

### `DREVOPS_ACQUIA_APP_NAME`

Database dump Acquia application name.

Default value: `UNDEFINED`

### `DREVOPS_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `UNDEFINED`

### `DREVOPS_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `UNDEFINED`

### `DREVOPS_AHOY_CONFIRM_RESPONSE`

Uncomment below to suppress Ahoy prompts.

Default value: `UNDEFINED`

### `DREVOPS_APP`

Path to the root of the project inside of the container.

Default value: `app`

### `DREVOPS_COMPOSER_VALIDATE_LOCK`

Validate `composer.lock` file.

Default value: `1`

### `DREVOPS_COMPOSER_VERBOSE`

Print debug information from Composer install.

Default value: `UNDEFINED`

### `DREVOPS_DB_DIR`

Database dump data directory (file or Docker image archive).

Default value: `data`

### `DREVOPS_DB_DOCKER_IMAGE`

Name of the database docker image to use. Uncomment to use an image with a DB data loaded into it. @see https://github.com/drevops/mariadb-drupal-data to seed your DB image.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOCKER_IMAGE_BASE`

Name of the database fall-back docker image to use. If the image specified in [`$DREVOPS_DB_DOCKER_IMAGE`](#drevops_db_docker_image) does not exist and base image was provided - it will be used as a "clean slate" for the database.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_APP_NAME`

Application name. Used to discover UUID.

Default value: `DREVOPS_ACQUIA_APP_NAME`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_DB_NAME`

Database dump Acquia database name.

Default value: `your_site`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_DIR`

Directory where DB dumps are stored.

Default value: `DREVOPS_DB_DIR`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_ENV`

Database dump Acquia database environment.

Default value: `prod`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_FILE`

Database dump file name.

Default value: `DREVOPS_DB_FILE`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `DREVOPS_ACQUIA_KEY`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `DREVOPS_ACQUIA_SECRET`

### `DREVOPS_DB_DOWNLOAD_CURL_URL`

Database dump file source from CURL, with optional HTTP Basic Authentication credentials embedded into the value.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE`

The Docker image containing database passed in a form of `<org>/<repository>`.

Default value: `DREVOPS_DB_DOCKER_IMAGE`

### `DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY`

The name of the Docker registry to download the database from.

Default value: `DREVOPS_DOCKER_REGISTRY:-docker.io`

### `DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN`

The token of the docker registry to download the database from.

Default value: `DREVOPS_DOCKER_REGISTRY_TOKEN`

### `DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME`

The username of the docker registry to download the database from.

Default value: `DREVOPS_DOCKER_REGISTRY_USERNAME`

### `DREVOPS_DB_DOWNLOAD_FORCE`

Always override existing downloaded DB dump. Leave empty to always ask before overwriting existing DB dump.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_FTP_FILE`

Database dump FTP file name.

Default value: `db.sql`

### `DREVOPS_DB_DOWNLOAD_FTP_HOST`

Database dump FTP host.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_FTP_PASS`

Database dump FTP password.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_FTP_PORT`

Database dump FTP port.

Default value: `21`

### `DREVOPS_DB_DOWNLOAD_FTP_USER`

Database dump FTP user.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT`

Lagoon environment to download DB from.

Default value: `main`

### `DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT`

Lagoon project name.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR`

Remote DB dump directory location.

Default value: `tmp`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE`

Remote DB dump file name. Cached by the date suffix.

Default value: `db_$(date +%Y_%m_%d).sql`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP`

Wildcard file name to cleanup previously created dump files. Cleanup runs only if the variable is set and [`$DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE`](#drevops_db_download_lagoon_remote_file) does not exist.

Default value: `db_*.sql`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT`

The SSH key fingerprint. If provided - the key will be looked-up and loaded into ssh client.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST`

The SSH host of the Lagoon environment.

Default value: `ssh.lagoon.amazeeio.cloud`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE`

The SSH key used to SSH into Lagoon.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT`

The SSH port of the Lagoon environment.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER`

The SSH user of the Lagoon environment.

Default value: `DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT`

### `DREVOPS_DB_DOWNLOAD_POST_PROCESS`

Post process command or a script used for running after the database was downloaded.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_PROCEED`

Kill-switch to proceed with download.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_REFRESH`

Flag to download a fresh copy of the database.

Default value: `UNDEFINED`

### `DREVOPS_DB_DOWNLOAD_SOURCE`

Database can be sourced from one of the following locations: - "url" - directly from URL as a file using CURL. - "ftp" - directly from FTP as a file using CURL. - "acquia" - from the latest Acquia backup via Cloud API as a file. - "lagoon" - from Lagoon main enveronment as a file. - "docker_registry" - from the docker registry as a docker image. - "none" - not downloaded, site is freshly installed for every build.<br/>Note that "docker_registry" works only for database-in-Docker-image database storage (when [`$DREVOPS_DB_DOCKER_IMAGE`](#drevops_db_docker_image) variable has a value).

Default value: `curl`

### `DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE`

SSH key file used to access Lagoon environment to download the database. Create an SSH key and add it to your account in the Lagoon Dashboard.

Default value: `HOME/.ssh/id_rsa`

### `DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE`

Docker image archive file name.

Default value: `UNDEFINED`

### `DREVOPS_DB_EXPORT_DOCKER_DIR`

Directory with database image archive file.

Default value: `DREVOPS_DB_DIR`

### `DREVOPS_DB_EXPORT_DOCKER_IMAGE`

Docker image to store in a form of `<org>/<repository>`.

Default value: `UNDEFINED`

### `DREVOPS_DB_EXPORT_DOCKER_REGISTRY`

Docker registry name.

Default value: `DREVOPS_DOCKER_REGISTRY:-docker.io`

### `DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME`

The service name to capture.

Default value: `mariadb`

### `DREVOPS_DB_EXPORT_FILE_DIR`

Directory with database dump file.

Default value: `DREVOPS_DB_DIR`

### `DREVOPS_DB_FILE`

Database dump file name (Docker image archive will use '.tar' extension).

Default value: `db.sql`

### `DREVOPS_DEBUG`

Print debug information in DrevOps scripts.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ACTION`

Deployment action. Values can be one of: deploy, deploy_override_db, destroy.

Default value: `deploy`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_BRANCH`

Remote repository branch. Can be a specific branch or a token. @see https://github.com/drevops/git-artifact#token-support

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_REMOTE`

Remote repository to push code to.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_EMAIL`

Name of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_NAME`

Email address of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_REPORT_FILE`

Deployment report file name.

Default value: `DREVOPS_DEPLOY_ARTIFACT_ROOT`

### `DREVOPS_DEPLOY_ARTIFACT_ROOT`

The root directory where the deployment script should run from. Defaults to the current directory.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_SRC`

Source of the code to be used for artifact building.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_ARTIFACT_SSH_FILE`

Default SSH file used if custom fingerprint is not provided.

Default value: `HOME`

### `DREVOPS_DEPLOY_ARTIFACT_SSH_FINGERPRINT`

SSH key fingerprint used to connect to remote.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_BRANCH`

Deployment pull request number without "pr-" prefix.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_DOCKER_IMAGE_TAG`

The tag of the image to push to. Defaults to 'latest'.

Default value: `latest`

### `DREVOPS_DEPLOY_DOCKER_MAP`

Comma-separated map of docker services and images to use for deployment in format "service1=org/image1,service2=org/image2".

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_DOCKER_REGISTRY`

The registry of the docker registry to deploy Docker image to.

Default value: `docker.io`

### `DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN`

The token of the docker registry to deploy Docker image to.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME`

The username of the docker registry to deploy Docker image to.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_ACTION`

Deploy action. Values can be one of: deploy, deploy_override_db, destroy.

Default value: `create`

### `DREVOPS_DEPLOY_LAGOON_BRANCH`

The Lagoon branch to deploy.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE`

The Lagoon instance name to interact with.

Default value: `amazeeio`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_GRAPHQL`

The Lagoon instance GraphQL endpoint to interact with.

Default value: `https://api.lagoon.amazeeio.cloud/graphql`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_HOSTNAME`

The Lagoon instance hostname to interact with.

Default value: `ssh.lagoon.amazeeio.cloud`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_PORT`

The Lagoon instance port to interact with.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH`

Location of the Lagoon CLI binary.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL`

Flag to force the installation of Lagoon CLI.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION`

Lagoon CLI version to use.

Default value: `latest`

### `DREVOPS_DEPLOY_LAGOON_PR`

The PR number to deploy.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_PROJECT`

The Lagoon project to perform deployment for.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH`

The PR base branch (the branch the PR is raised against). Defaults to 'develop'.

Default value: `develop`

### `DREVOPS_DEPLOY_LAGOON_PR_HEAD`

The PR head branch to deploy.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_LAGOON_SSH_FILE`

Default SSH file used if custom fingerprint is not provided.

Default value: `HOME`

### `DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT`

SSH key fingerprint used to connect to remote. If not used, the currently loaded default SSH key (the key used for code checkout) will be used or deployment will fail with an error if the default SSH key is not loaded. In most cases, the default SSH key does not work (because it is a read-only key used by CircleCI to checkout code from git), so you should add another deployment key.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_PR`

Deployment pull request number without "pr-" prefix.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_PROCEED`

Flag to proceed with deployment. Set to `1` once the deployment configuration is configured in CI and is ready.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_SKIP`

Flag to allow skipping of a deployment using additional flags. Different to [`$DREVOPS_DEPLOY_PROCEED`](#drevops_deploy_proceed) in a way that [`$DREVOPS_DEPLOY_PROCEED`](#drevops_deploy_proceed) is a failsafe to prevent any deployments, while [`$DREVOPS_DEPLOY_SKIP`](#drevops_deploy_skip) allows to selectively skip certain deployments using '[`$DREVOPS_DEPLOY_SKIP`](#drevops_deploy_skip)_PR_<NUMBER>' and '[`$DREVOPS_DEPLOY_SKIP`](#drevops_deploy_skip)_BRANCH_<SAFE_BRANCH>' variables.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_TYPE`

Combination of comma-separated values to support multiple deployments: "artifact","docker", "webhook", "lagoon".

Default value: `artifact`

### `DREVOPS_DEPLOY_WEBHOOK_METHOD`

Webhook call method.

Default value: `GET`

### `DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS`

The status code of the expected response.

Default value: `UNDEFINED`

### `DREVOPS_DEPLOY_WEBHOOK_URL`

The URL of the webhook to call. Note that any tokens should be added to the value of this variable outside this script.

Default value: `UNDEFINED`

### `DREVOPS_DOCKER_REGISTRY`

Docker registry name. Provide port, if required as <server_name>:<port>.

Default value: `docker.io`

### `DREVOPS_DOCKER_REGISTRY_TOKEN`

The token to log into the Docker registry.

Default value: `UNDEFINED`

### `DREVOPS_DOCKER_REGISTRY_USERNAME`

The username to log into the Docker registry.

Default value: `UNDEFINED`

### `DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE`

Docker image archive file to restore passed as a second argument to this script.

Default value: `UNDEFINED`

### `DREVOPS_DOCKER_RESTORE_IMAGE`

Docker image archive file name.

Default value: `UNDEFINED`

### `DREVOPS_DOCKER_VERBOSE`

Print debug information from Docker build.

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_BOOTSTRAP`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_CONTAINERS`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_MINIMAL`

Check minimal Doctor requirements.

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_PORT`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_PREFLIGHT`

Check pre-flight Doctor requirements.

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_PYGMY`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_SSH`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_TOOLS`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_CHECK_WEBSERVER`

Default value: `UNDEFINED`

### `DREVOPS_DOCTOR_LOCALDEV_URL`

Local development URL.

Default value: `DREVOPS_LOCALDEV_URL`

### `DREVOPS_DOCTOR_SSH_KEY_FILE`

Default SSH key file.

Default value: `HOME`

### `DREVOPS_DRUPAL_ADMIN_EMAIL`

Drupal admin email. May need to be reset if database was sanitized.

Default value: `webmaster@your-site-url.example`

### `DREVOPS_DRUPAL_CONFIG_LABEL`

Config label.

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_CONFIG_PATH`

Path to configuration directory.

Default value: `DREVOPS_APP`

### `DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE`

Path to file with custom sanitization SQL queries. To skip custom sanitization, remove the [`$DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE`](#drevops_drupal_db_sanitize_additional_file) file from the codebase.

Default value: `DREVOPS_APP`

### `DREVOPS_DRUPAL_DB_SANITIZE_EMAIL`

Sanitization email pattern. Sanitization is enabled by default in all non-production environments. @see https://docs.drevops.com/build#sanitization

Default value: `user_%uid@your-site-url.example`

### `DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD`

Database sanitized account password replacement.

Default value: `RANDOM`

### `DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL`

Replace username with mail.

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP`

Skip database sanitization.

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_INSTALL_FROM_PROFILE`

Set to `1` to install a site from profile instead of database file dump.

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP`

Flag to skip running post DB import commands. Useful to only import the database from file (or install from profile) and not perform any additional operations. For example, when need to capture database state before any updates ran (for example, DB caching in CI).

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB`

Flag to always overwrite existing database. Usually set to `0` in deployed environments and can be temporary set to `1` for a specific deployment. Set this to `1` in .env.local to override when developing localy.

Default value: `UNDEFINED`

### `DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE`

Put the site into a maintenance mode during site installation phase.

Default value: `1`

### `DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN`

Unblock admin account when logging in.

Default value: `1`

### `DREVOPS_DRUPAL_PRIVATE_FILES`

Path to private files.

Default value: `DREVOPS_APP`

### `DREVOPS_DRUPAL_PROFILE`

Drupal profile name (used only when installing from profile).

Default value: `your_site_profile`

### `DREVOPS_DRUPAL_SHOW_LOGIN_LINK`

Show project information and a one-time login link.

Default value: `ahoy info`

### `DREVOPS_DRUPAL_SITE_EMAIL`

Drupal site email (used only when installing from profile).

Default value: `webmaster@your-site-url.example`

### `DREVOPS_DRUPAL_SITE_NAME`

Drupal site name (used only when installing from profile).

Default value: `YOURSITE`

### `DREVOPS_DRUPAL_THEME`

Drupal theme name.

Default value: `your_site_theme`

### `DREVOPS_DRUPAL_VERSION`

Drupal version.

Default value: `9`

### `DREVOPS_EXPORT_CODE_DIR`

Directory to store exported code.

Default value: `UNDEFINED`

### `DREVOPS_EXPORT_CODE_ONLY`

Only build and export code.

Default value: `UNDEFINED`

### `DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED`

Proceed with Docker image deployment after it was exported.

Default value: `UNDEFINED`

### `DREVOPS_GITHUB_DELETE_EXISTING_LABELS`

Delete existing labels to mirror the list below.

Default value: `UNDEFINED`

### `DREVOPS_GITHUB_REPO`

GitHub repository as "org/name" to perform operations on.

Default value: `UNDEFINED`

### `DREVOPS_GITHUB_TOKEN`

GitHub token to perform operations.

Default value: `GITHUB_TOKEN`

### `DREVOPS_INSTALLER_URL`

The URL of the installer script.

Default value: `https://install.drevops.com`

### `DREVOPS_INSTALL_COMMIT`

Allow providing custom DrevOps commit hash to download the sources from.

Default value: `UNDEFINED`

### `DREVOPS_LINT_BE_ALLOW_FAILURE`

Allow BE code linting failures.

Default value: `UNDEFINED`

### `DREVOPS_LINT_FE_ALLOW_FAILURE`

Allow FE code linting failures.

Default value: `UNDEFINED`

### `DREVOPS_LINT_PHPCS_TARGETS`

PHPCS comma-separated list of targets.

Default value: `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests`

### `DREVOPS_LINT_PHPLINT_EXTENSIONS`

PHP Parallel Lint comma-separated list of extensions (no preceding dot).

Default value: `php, inc, module, theme, install`

### `DREVOPS_LINT_PHPLINT_TARGETS`

PHP Parallel Lint comma-separated list of targets.

Default value: `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests`

### `DREVOPS_LINT_SKIP`

Flag to skip code linting. Helpful to set in CI to code linting without modifying the codebase.

Default value: `UNDEFINED`

### `DREVOPS_LINT_TYPE`

Provide argument as 'be' or 'fe' to lint only back-end or front-end code. If no argument is provided, all code will be linted.

Default value: `UNDEFINED`

### `DREVOPS_LOCALDEV_URL`

Local development URL (no trailing slashes).

Default value: `your-site.docker.amazee.io`

### `DREVOPS_MARIADB_DATABASE`

Local database name (not used in production).

Default value: `drupal`

### `DREVOPS_MARIADB_HOST`

Local database host (not used in production).

Default value: `mariadb`

### `DREVOPS_MARIADB_PASSWORD`

Local database password (not used in production).

Default value: `drupal`

### `DREVOPS_MARIADB_PORT`

Local database port (not used in production).

Default value: `3306`

### `DREVOPS_MARIADB_USER`

Local database user (not used in production).

Default value: `drupal`

### `DREVOPS_MIRROR_CODE_BRANCH_DST`

Destination branch name to mirror code.

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_BRANCH_SRC`

Source branch name to mirror code.

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_GIT_USER_EMAIL`

Name of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_GIT_USER_NAME`

Email address of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_PUSH`

Flag to push the branch.

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_REMOTE_DST`

Destination remote name.

Default value: `origin`

### `DREVOPS_MIRROR_CODE_SSH_FILE`

Default value: `UNDEFINED`

### `DREVOPS_MIRROR_CODE_SSH_FINGERPRINT`

Optional SSH key fingerprint to use for mirroring.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOYMENT_SKIP`

Flag to skip deployment notification.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_BRANCH`

Deployment reference, such as a git SHA.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_EMAIL_SKIP`

Flag to skip Email deployment notification.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE`

Deployment environment type: production, uat, dev, pr.

Default value: `PR`

### `DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL`

Deployment environment URL.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION`

Operation type: start or finish.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_GITHUB_SKIP`

Flag to skip GitHub deployment notification.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE`

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_JIRA_SKIP`

Flag to skip NewRelic deployment notification.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN`

JIRA token.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION`

State to move the ticket to.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_JIRA_USER`

JIRA user.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_NEWRELIC_SKIP`

Flag to skip NewRelic deployment notification.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_REF`

Deployment reference, such as a git SHA.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_DEPLOY_REPOSITORY`

Deployment repository.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_GITHUB_TOKEN`

Deployment GitHub token.

Default value: `GITHUB_TOKEN`

### `DREVOPS_NOTIFY_JIRA_ENDPOINT`

JIRA API endpoint.l

Default value: `https://jira.atlassian.com`

### `DREVOPS_NOTIFY_NEWRELIC_APIKEY`

The API key. Usually of type 'USER'.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_NEWRELIC_APPID`

Optional Application ID. Will be discovered automatically from application name if not provided.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_NEWRELIC_APP_NAME`

Application name as it appears in the dashboard.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_NEWRELIC_CHANGELOG`

Optional deployment changelog. Defaults to description.

Default value: `DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`

### `DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`

Optional deployment description.

Default value: `UNDEFINED`

### `DREVOPS_NOTIFY_NEWRELIC_ENDPOINT`

Optional endpoint.

Default value: `https://api.newrelic.com/v2`

### `DREVOPS_NOTIFY_NEWRELIC_USER`

Optional user name performing the deployment.

Default value: `UNDEFINED`

### `DREVOPS_NPM_VERBOSE`

Print debug information from NPM install.

Default value: `UNDEFINED`

### `DREVOPS_PRODUCTION_BRANCH`

Dedicated branch to identify production environment.

Default value: `main`

### `DREVOPS_PROJECT`

Project name.

Default value: `your_site`

### `DREVOPS_TASK_COPY_DB_ACQUIA_APP_NAME`

Application name. Used to discover UUID.

Default value: `DREVOPS_ACQUIA_APP_NAME`

### `DREVOPS_TASK_COPY_DB_ACQUIA_DST`

Destination environment name to copy DB to.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_DB_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `DREVOPS_ACQUIA_KEY`

### `DREVOPS_TASK_COPY_DB_ACQUIA_NAME`

Database name to copy.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `DREVOPS_ACQUIA_SECRET`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SKIP`

Skip copying of database between Acquia environments.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SRC`

Source environment name to copy DB from.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_DB_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_DB_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not yet finished, the task is considered failed.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME`

Application name. Used to discover UUID.

Default value: `DREVOPS_ACQUIA_APP_NAME`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_DST`

Destination environment name to copy to.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `DREVOPS_ACQUIA_KEY`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `DREVOPS_ACQUIA_SECRET`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SKIP`

Skip copying of files between Acquia environments.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SRC`

Source environment name to copy from.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `UNDEFINED`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not yet finished, the task is considered failed.

Default value: `UNDEFINED`

### `DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP`

Skip Drupal site installation in Acquia environments.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_BIN_PATH`

Location of the Lagoon CLI binary.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_BRANCH`

The Lagoon branch to run the task on.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_COMMAND`

The task command to execute.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_INSTALL_CLI_FORCE`

Flag to force the installation of Lagoon CLI.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_INSTANCE`

The Lagoon instance name to interact with.

Default value: `amazeeio`

### `DREVOPS_TASK_LAGOON_INSTANCE_GRAPHQL`

The Lagoon instance GraphQL endpoint to interact with.

Default value: `https://api.lagoon.amazeeio.cloud/graphql`

### `DREVOPS_TASK_LAGOON_INSTANCE_HOSTNAME`

The Lagoon instance hostname to interact with.

Default value: `ssh.lagoon.amazeeio.cloud`

### `DREVOPS_TASK_LAGOON_INSTANCE_PORT`

The Lagoon instance port to interact with.

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION`

Lagoon CLI version to use.

Default value: `latest`

### `DREVOPS_TASK_LAGOON_NAME`

Default value: `UNDEFINED`

### `DREVOPS_TASK_LAGOON_PROJECT`

The Lagoon project to run tasks for.

Default value: `UNDEFINED`

### `DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SKIP`

Skip deployment email notification in Acquia environments.

Default value: `UNDEFINED`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME`

Application name. Used to discover UUID.

Default value: `UNDEFINED`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE`

File with a list of domains that should be purged.

Default value: `domains.txt`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV`

An environment name to purge cache for.

Default value: `UNDEFINED`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `DREVOPS_ACQUIA_KEY`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `DREVOPS_ACQUIA_SECRET`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_SKIP`

Skip purging of edge cache in Acquia environments.

Default value: `UNDEFINED`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `UNDEFINED`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not yet finished, the task is considered failed.

Default value: `UNDEFINED`

### `DREVOPS_TASK_SSH_FILE`

Default SSH file used if custom fingerprint is not provided.

Default value: `HOME`

### `DREVOPS_TASK_SSH_FINGERPRINT`

SSH key fingerprint used to connect to remote.<br/>If not used, the currently loaded default SSH key (the key used for code checkout) will be used or deployment will fail with an error if the default SSH key is not loaded. In most cases, the default SSH key does not work (because it is a read-only key used by CircleCI to checkout code from git), so you should add another deployment key.

Default value: `UNDEFINED`

### `DREVOPS_TEST_ARTIFACT_DIR`

Directory to store test artifact files.

Default value: `UNDEFINED`

### `DREVOPS_TEST_BDD_ALLOW_FAILURE`

Allow BDD tests failures.

Default value: `UNDEFINED`

### `DREVOPS_TEST_BEHAT_FORMAT`

Behat format. Optional. Defaults to "pretty".

Default value: `pretty`

### `DREVOPS_TEST_BEHAT_PARALLEL_INDEX`

Behat test runner index. If is set  - the value is used as a suffix for the parallel Behat profile name (e.g., p0, p1).

Default value: `UNDEFINED`

### `DREVOPS_TEST_BEHAT_PROFILE`

Behat profile name. Optional. Defaults to "default".

Default value: `default`

### `DREVOPS_TEST_BEHAT_TAGS`

Behat tags. Optional. Default runs all tests.

Default value: `UNDEFINED`

### `DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE`

Allow custom Functional tests failures.

Default value: `UNDEFINED`

### `DREVOPS_TEST_FUNCTIONAL_GROUP`

Functional test group. Optional. Default runs all tests.

Default value: `UNDEFINED`

### `DREVOPS_TEST_KERNEL_ALLOW_FAILURE`

Allow custom Kernel tests failures.

Default value: `UNDEFINED`

### `DREVOPS_TEST_KERNEL_GROUP`

Kernel test group. Optional. Default runs all tests.

Default value: `UNDEFINED`

### `DREVOPS_TEST_REPORTS_DIR`

Directory to store test result files.

Default value: `UNDEFINED`

### `DREVOPS_TEST_SKIP`

Flag to skip running of all tests. Helpful to set in CI to skip running of tests without modifying the codebase.

Default value: `UNDEFINED`

### `DREVOPS_TEST_TYPE`

Get test type or fallback to defaults.

Default value: `unit-kernel-functional-bdd`

### `DREVOPS_TEST_UNIT_ALLOW_FAILURE`

Allow custom Unit tests failures.

Default value: `UNDEFINED`

### `DREVOPS_TEST_UNIT_GROUP`

Unit test group. Optional. Default runs all tests.

Default value: `UNDEFINED`

### `ENV_ID`

Default value: `echo `

### `LAGOON_PROJECT`

Lagoon project name. May be different from [`$DREVOPS_PROJECT`](#drevops_project).

Default value: `your_site`

### `NEWRELIC_ENABLED`

New Relic availability flag.

Default value: `UNDEFINED`

### `NEWRELIC_LICENSE`

New Relic license.

Default value: `UNDEFINED`

