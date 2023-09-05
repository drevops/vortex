# 🎛 Variables

## Guidelines

1. Local variables MUST be in lowercase, and global variables MUST be in
   uppercase.

2. All DrevOps variables MUST start with `DREVOPS_` to separate DrevOps from
   third-party variables.

3. Global variables MAY be re-used as-is across scripts. For instance, the
   `DREVOPS_APP` variable is used in several scripts.

4. DrevOps action-specific script variables MUST be scoped within their own
   script. For instance, the `DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB`
   variable in the `drupal-install-site.sh`.

5. Drupal-related variables SHOULD start with `DRUPAL_` and SHOULD have a module
   name added as a second prefix. This is to separate DrevOps,  third-party
   services variables, and Drupal variables. For instance, to set
   a user for Drupal's Shield module configuration, use `DRUPAL_SHIELD_USER`.

6. Variables SHOULD NOT be exported into the global scope unless absolutely
   necessary. Thus, values in `.env` SHOULD have default values set, but SHOULD
   be commented out to provide visibility and avoid exposure to the global scope.

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

Defined in: `.env.local.example`

### `CLAMAV_MODE`

ClamAV mode.

Run ClamAV in either daemon mode by setting it to `0` or in executable mode<br />by setting it to `1`.

Default value: `daemon`

Defined in: `.env`

### `COMPOSE_PROJECT_NAME`

Docker Compose project name (all containers will have this name). Defaults<br />to the name of the project directory.

Default value: `UNDEFINED`

Defined in: `ENVIRONMENT`

### `DOCKER_PASS`

The password (token) to log into the Docker registry.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/deploy-docker.sh`, `scripts/drevops/docker-login.sh`, `scripts/drevops/download-db-docker-registry.sh`

### `DOCKER_REGISTRY`

Docker registry name.

Provide port, if required as `<server_name>:<port>`.

Default value: `docker.io`

Defined in: `.env`, `scripts/drevops/deploy-docker.sh`, `scripts/drevops/docker-login.sh`, `scripts/drevops/download-db-docker-registry.sh`

### `DOCKER_USER`

The username to log into the Docker registry.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/deploy-docker.sh`, `scripts/drevops/docker-login.sh`, `scripts/drevops/download-db-docker-registry.sh`

### `DREVOPS_ACQUIA_APP_NAME`

Acquia application name to download the database from.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/download-db-acquia.sh`, `scripts/drevops/task-copy-db-acquia.sh`, `scripts/drevops/task-copy-files-acquia.sh`, `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_ACQUIA_KEY`

Acquia Cloud API key.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/download-db-acquia.sh`, `scripts/drevops/task-copy-db-acquia.sh`, `scripts/drevops/task-copy-files-acquia.sh`, `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_ACQUIA_SECRET`

Acquia Cloud API secret.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/download-db-acquia.sh`, `scripts/drevops/task-copy-db-acquia.sh`, `scripts/drevops/task-copy-files-acquia.sh`, `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_APP`

Path to the root of the project inside the container.

Default value: `/app`

Defined in: `.env`, `scripts/drevops/build.sh`, `scripts/drevops/download-db-lagoon.sh`, `scripts/drevops/drupal-install-site.sh`, `scripts/drevops/drupal-login.sh`, `scripts/drevops/drupal-logout.sh`, `scripts/drevops/drupal-sanitize-db.sh`, `scripts/drevops/export-db-file.sh`, `scripts/drevops/info.sh`, `scripts/drevops/test-functional.sh`, `scripts/drevops/test-kernel.sh`, `scripts/drevops/test-unit.sh`

### `DREVOPS_CLAMAV_ENABLED`

Enable ClamAV integration.

Default value: `1`

Defined in: `.env`

### `DREVOPS_COMPOSER_VERBOSE`

Print debug information from Composer install.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/build.sh`

### `DREVOPS_DB_DIR`

Database dump data directory (file or Docker image archive).

Default value: `./.data`

Defined in: `.env`, `scripts/drevops/build.sh`, `scripts/drevops/download-db-acquia.sh`, `scripts/drevops/download-db-curl.sh`, `scripts/drevops/download-db-ftp.sh`, `scripts/drevops/download-db-lagoon.sh`, `scripts/drevops/download-db.sh`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DB_DOCKER_IMAGE`

Name of the database docker image to use.

See https://github.com/drevops/mariadb-drupal-data to seed your DB image.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/build.sh`, `scripts/drevops/download-db-docker-registry.sh`, `scripts/drevops/drupal-install-site.sh`, `scripts/drevops/export-db.sh`, `scripts/drevops/info.sh`

### `DREVOPS_DB_DOCKER_IMAGE_BASE`

Name of the database fall-back docker image to use.

If the image specified in [`$DREVOPS_DB_DOCKER_IMAGE`](#DREVOPS_DB_DOCKER_IMAGE) does not exist and base<br />image was provided - it will be used as a "clean slate" for the database.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/build.sh`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_DB_NAME`

Acquia database name to download the database from.

Default value: `your_site`

Defined in: `.env`, `scripts/drevops/download-db-acquia.sh`

### `DREVOPS_DB_DOWNLOAD_ACQUIA_ENV`

Acquia environment to download the database from.

Default value: `prod`

Defined in: `.env`, `scripts/drevops/download-db-acquia.sh`

### `DREVOPS_DB_DOWNLOAD_CURL_URL`

Database dump file source from CURL, with optional HTTP Basic Authentication<br />credentials embedded into the value.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/download-db-curl.sh`

### `DREVOPS_DB_DOWNLOAD_FORCE`

Set to `1` to override existing downloaded DB dump without asking.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/download-db.sh`

### `DREVOPS_DB_DOWNLOAD_FTP_FILE`

Database dump FTP file name.

Default value: `db.sql`

Defined in: `.env`, `scripts/drevops/download-db-ftp.sh`

### `DREVOPS_DB_DOWNLOAD_FTP_HOST`

Database dump FTP host.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/download-db-ftp.sh`

### `DREVOPS_DB_DOWNLOAD_FTP_PASS`

Database dump FTP password.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/download-db-ftp.sh`

### `DREVOPS_DB_DOWNLOAD_FTP_PORT`

Database dump FTP port.

Default value: `21`

Defined in: `.env`, `scripts/drevops/download-db-ftp.sh`

### `DREVOPS_DB_DOWNLOAD_FTP_USER`

Database dump FTP user.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/download-db-ftp.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_BRANCH`

Lagoon environment to download the database from.

Default value: `main`

Defined in: `.env`, `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR`

Remote DB dump directory location.

Default value: `/tmp`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE`

Remote DB dump file name. Cached by the date suffix.

Default value: `db_$(date +%Y%m%d).sql`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP`

Wildcard file name to cleanup previously created dump files.

Cleanup runs only if the variable is set and [`$DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE`](#DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE)<br />does not exist.

Default value: `db_*.sql`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST`

The SSH host of the Lagoon environment.

Default value: `ssh.lagoon.amazeeio.cloud`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT`

The SSH port of the Lagoon environment.

Default value: `32222`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER`

The SSH user of the Lagoon environment.

Default value: `LAGOON_PROJECT-${DREVOPS_DB_DOWNLOAD_LAGOON_BRANCH`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_PROCEED`

Proceed with download.

Default value: `1`

Defined in: `scripts/drevops/download-db.sh`

### `DREVOPS_DB_DOWNLOAD_REFRESH`

Flag to download a fresh copy of the database.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/download-db-lagoon.sh`

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

Defined in: `.env`, `scripts/drevops/download-db.sh`

### `DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT`

The SSH key fingerprint.

If provided - the key will be looked-up and loaded into ssh client.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE`

SSH key file used to access Lagoon environment to download the database.<br />Create an SSH key and add it to your account in the Lagoon Dashboard.

Default value: `HOME/.ssh/id_rsa`

Defined in: `.env.local.example`, `scripts/drevops/download-db-lagoon.sh`

### `DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE`

Docker image archive file name.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/export-db-docker.sh`

### `DREVOPS_DB_EXPORT_DOCKER_DIR`

Directory with database image archive file.

Default value: `DREVOPS_DB_DIR`

Defined in: `scripts/drevops/export-db-docker.sh`

### `DREVOPS_DB_EXPORT_DOCKER_IMAGE`

Docker image to store in a form of `<org>/<repository>`.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/export-db-docker.sh`, `scripts/drevops/export-db.sh`

### `DREVOPS_DB_EXPORT_DOCKER_REGISTRY`

Docker registry name.

Default value: `docker.io`

Defined in: `scripts/drevops/export-db-docker.sh`

### `DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME`

The service name to capture.

Default value: `mariadb`

Defined in: `scripts/drevops/export-db-docker.sh`

### `DREVOPS_DB_EXPORT_FILE_DIR`

Directory with database dump file.

Default value: `./.data`

Defined in: `scripts/drevops/export-db-file.sh`

### `DREVOPS_DB_FILE`

Database dump file name (Docker image archive will use '.tar' extension).

Default value: `db.sql`

Defined in: `.env`, `scripts/drevops/download-db-acquia.sh`, `scripts/drevops/download-db-curl.sh`, `scripts/drevops/download-db-ftp.sh`, `scripts/drevops/download-db-lagoon.sh`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DEBUG`

Set to `1` to print debug information in DrevOps scripts.

Default value: `UNDEFINED`

Defined in: `.env.local.example`

### `DREVOPS_DEPLOY_ACTION`

Deployment action.

Values can be one of: deploy, deploy_override_db, destroy.
- deploy: Deploy code and preserve database in the environment.
- deploy_override_db: Deploy code and override database in the environment.
- destroy: Destroy the environment (if the provider supports it).

Default value: `create`

Defined in: `scripts/drevops/deploy-lagoon.sh`, `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_ALLOW_SKIP`

Flag to allow skipping of a deployment using additional flags.

Different to [`$DREVOPS_DEPLOY_PROCEED`](#DREVOPS_DEPLOY_PROCEED) in a way that [`$DREVOPS_DEPLOY_PROCEED`](#DREVOPS_DEPLOY_PROCEED) is<br />a failsafe to prevent any deployments, while $DREVOPS_DEPLOY_SKIP allows to<br />selectively skip certain deployments using `$DREVOPS_DEPLOY_SKIP_PR_<NUMBER>'<br />and `$DREVOPS_DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>` variables.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_ARTIFACT_DST_BRANCH`

Remote repository branch. Can be a specific branch or a token.<br />@see https://github.com/drevops/git-artifact#token-support

Default value: `[branch]`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_REMOTE`

Remote repository to push code to.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_EMAIL`

Name of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_NAME`

Email address of the user who will be committing to a remote repository.

Default value: `Deployment Robot`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_REPORT_FILE`

Deployment report file name.

Default value: `DREVOPS_DEPLOY_ARTIFACT_ROOT/deployment_report.txt`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_ROOT`

The root directory where the deployment script should run from. Defaults to<br />the current directory.

Default value: `(pwd)`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_ARTIFACT_SRC`

Source of the code to be used for artifact building.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-artifact.sh`

### `DREVOPS_DEPLOY_BRANCH`

The Lagoon branch to deploy.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-lagoon.sh`, `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_DOCKER_MAP`

Comma-separated map of docker services and images to use for deployment in<br />format "service1=org/image1,service2=org/image2".

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-docker.sh`, `scripts/drevops/export-db.sh`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE`

The Lagoon instance name to interact with.

Default value: `amazeeio`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_GRAPHQL`

The Lagoon instance GraphQL endpoint to interact with.

Default value: `https://api.lagoon.amazeeio.cloud/graphql`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_HOSTNAME`

The Lagoon instance hostname to interact with.

Default value: `ssh.lagoon.amazeeio.cloud`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_INSTANCE_PORT`

The Lagoon instance port to interact with.

Default value: `32222`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH`

Location of the Lagoon CLI binary.

Default value: `/tmp`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL`

Flag to force the installation of Lagoon CLI.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION`

Lagoon CLI version to use.

Default value: `latest`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_MODE`

Deployment mode.

Values can be one of: branch, tag.

Default value: `branch`

Defined in: `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_PR`

The PR number to deploy.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-lagoon.sh`, `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_PROCEED`

Flag to proceed with deployment.<br />Usually set to `1` once the deployment configuration is configured in CI and<br />is ready for use.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_PR_BASE_BRANCH`

The PR base branch (the branch the PR is raised against). Defaults to 'develop'.

Default value: `develop`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_PR_HEAD`

The PR head branch to deploy.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_SSH_FILE`

Default SSH file used if custom fingerprint is not provided.

Default value: `HOME/.ssh/id_rsa`

Defined in: `scripts/drevops/deploy-artifact.sh`, `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_SSH_FINGERPRINT`

SSH key fingerprint used to connect to remote.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-artifact.sh`, `scripts/drevops/deploy-lagoon.sh`

### `DREVOPS_DEPLOY_TYPES`

The type of deployemt.

Combination of comma-separated values to support multiple deployments:<br />`artifact`,`docker`, `webhook`, `lagoon`.

See https://docs.drevops.com/deploy

Default value: `artifact`

Defined in: `.env`, `scripts/drevops/deploy.sh`

### `DREVOPS_DEPLOY_WEBHOOK_METHOD`

Webhook call method.

Default value: `GET`

Defined in: `scripts/drevops/deploy-webhook.sh`

### `DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS`

The status code of the expected response.

Default value: `200`

Defined in: `scripts/drevops/deploy-webhook.sh`

### `DREVOPS_DEPLOY_WEBHOOK_URL`

The URL of the webhook to call.<br />Note that any tokens should be added to the value of this variable outside<br />this script.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/deploy-webhook.sh`

### `DREVOPS_DOCKER_IMAGE_TAG`

The tag of the image to push to.

Default value: `latest`

Defined in: `scripts/drevops/deploy-docker.sh`

### `DREVOPS_DOCKER_RESTORE_IMAGE`

Docker image archive file name.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/docker-restore-image.sh`

### `DREVOPS_DOCKER_VERBOSE`

Set to `1` to print debug information from Docker build.

Default value: `UNDEFINED`

Defined in: `.env.local.example`, `scripts/drevops/build.sh`

### `DREVOPS_DOCTOR_CHECK_BOOTSTRAP`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_CONTAINERS`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_MINIMAL`

Check minimal Doctor requirements.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_PORT`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_PYGMY`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_SSH`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_TOOLS`

Default value: `1`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_CHECK_WEBSERVER`

Default value: `UNDEFINED`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DOCTOR_SSH_KEY_FILE`

Default SSH key file.

Default value: `HOME/.ssh/id_rsa`

Defined in: `scripts/drevops/doctor.sh`

### `DREVOPS_DRUPAL_ADMIN_EMAIL`

Drupal admin email. May need to be reset if database was sanitized.

Default value: `webmaster@your-site-url.example`

Defined in: `.env`

### `DREVOPS_DRUPAL_CONFIG_PATH`

Path to configuration directory.

Default value: `DREVOPS_APP/config/default`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE`

Path to file with custom sanitization SQL queries.

To skip custom sanitization, remove the #DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE<br />file from the codebase.

Default value: `DREVOPS_APP/scripts/sanitize.sql`

Defined in: `scripts/drevops/drupal-sanitize-db.sh`

### `DREVOPS_DRUPAL_DB_SANITIZE_EMAIL`

Sanitization email pattern. Sanitization is enabled by default in all<br />non-production environments.<br />@see https://docs.drevops.com/build#sanitization

Default value: `user_%uid@your-site-url.example`

Defined in: `.env`, `scripts/drevops/drupal-sanitize-db.sh`

### `DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD`

Password replacement used for sanitised database.

Default value: `<RANDOM STRING>`

Defined in: `.env`, `scripts/drevops/drupal-sanitize-db.sh`

### `DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL`

Replace username with mail.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/drupal-sanitize-db.sh`

### `DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP`

Skip database sanitization.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_ENVIRONMENT`

Current environment name discovered during site installation.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_FROM_PROFILE`

Set to `1` to install a site from profile instead of database file dump.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP`

Flag to skip running post DB import commands.<br />Useful to only import the database from file (or install from profile) and not<br />perform any additional operations. For example, when need to capture database<br />state before any updates ran (for example, DB caching in CI).

Default value: `UNDEFINED`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB`

Flag to always overwrite existing database. Usually set to `0` in deployed<br />environments and can be temporary set to `1` for a specific deployment.<br />Set this to `1` in .env.local to override when developing localy.

Default value: `UNDEFINED`

Defined in: `.env`, `.env.local.example`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_SKIP`

Flag to skip site installation.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE`

Put the site into a maintenance mode during site installation phase.

Default value: `1`

Defined in: `.env`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN`

Unblock admin account when logging in.

Default value: `1`

Defined in: `.env`, `scripts/drevops/drupal-login.sh`, `scripts/drevops/drupal-logout.sh`

### `DREVOPS_DRUPAL_PRIVATE_FILES`

Path to private files.

Default value: `DREVOPS_APP/${DREVOPS_WEBROOT}/sites/default/files/private`

Defined in: `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_PROFILE`

Drupal profile name (used only when installing from profile).

Default value: `your_site_profile`

Defined in: `.env`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_SHOW_LOGIN_LINK`

Show Drupal one-time login link.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/info.sh`

### `DREVOPS_DRUPAL_SITE_EMAIL`

Drupal site email (used only when installing from profile).

Default value: `webmaster@your-site-url.example`

Defined in: `.env`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_SITE_NAME`

Drupal site name (used only when installing from profile).

Default value: `YOURSITE`

Defined in: `.env`, `scripts/drevops/drupal-install-site.sh`

### `DREVOPS_DRUPAL_THEME`

Drupal theme name.

Default value: `your_site_theme`

Defined in: `.env`, `scripts/drevops/lint-fe.sh`, `scripts/drevops/test-unit.sh`

### `DREVOPS_DRUPAL_VERSION`

Drupal version.

Default value: `9`

Defined in: `.env`

### `DREVOPS_ENVIRONMENT_TYPE`

Override detected environment type.

Used in the application to override the automatically detected environment type.

Default value: `UNDEFINED`

Defined in: `ENVIRONMENT`

### `DREVOPS_EXPORT_CODE_DIR`

Directory to store exported code.

Default value: `UNDEFINED`

Defined in: `CI config`, `scripts/drevops/build.sh`

### `DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED`

Proceed with Docker image deployment after it was exported.

Default value: `UNDEFINED`

Defined in: `CI config`

### `DREVOPS_GITHUB_DELETE_EXISTING_LABELS`

Delete existing labels to mirror the list below.

Default value: `1`

Defined in: `scripts/drevops/github-labels.sh`

### `DREVOPS_GITHUB_REPO`

GitHub repository as "org/name" to perform operations on.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/github-labels.sh`

### `DREVOPS_INSTALLER_URL`

The URL of the installer script.

Default value: `https://install.drevops.com`

Defined in: `scripts/drevops/update-drevops.sh`

### `DREVOPS_INSTALL_COMMIT`

Allow providing custom DrevOps commit hash to download the sources from.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/update-drevops.sh`

### `DREVOPS_LINT_BE_ALLOW_FAILURE`

Allow BE code linting failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_FE_ALLOW_FAILURE`

Allow FE code linting failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/lint-fe.sh`

### `DREVOPS_LINT_PHPCS_TARGETS`

PHPCS comma-separated list of targets. Set to empty value to disable this check.

Default value: `web/profiles/custom/your_site_profile, web/modules/custom, web/themes/custom, web/sites/default/settings.php, tests`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_PHPLINT_EXTENSIONS`

PHP Parallel Lint comma-separated list of extensions (no preceding dot). Set to empty value to disable this check.

Default value: `php, inc, module, theme, install`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_PHPLINT_TARGETS`

PHP Parallel Lint comma-separated list of targets. Set to empty value to disable this check.

Default value: `web/profiles/custom/your_site_profile, web/modules/custom, web/themes/custom, web/sites/default/settings.php, tests`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_PHPMD_RULESETS`

PHPMD comma-separated list of rules.

Default value: `codesize, unusedcode, cleancode`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_PHPMD_TARGETS`

PHPMD comma-separated list of targets. Set to empty value to disable this check.

Default value: `web/profiles/custom/your_site_profile, web/modules/custom, web/themes/custom, web/sites/default/settings.php, tests`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_PHPSTAN_TARGETS`

PHPStan comma-separated list of targets. Set to empty value to disable this check.

Default value: `web/profiles/custom/your_site_profile, web/modules/custom, web/themes/custom, web/sites/default/settings.php, tests`

Defined in: `.env`, `scripts/drevops/lint-be.sh`

### `DREVOPS_LINT_SKIP`

Flag to skip code linting.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/lint.sh`

### `DREVOPS_LINT_TWIGCS_TARGETS`

Twigcs comma-separated list of targets. Set to empty value to disable this check.

Default value: `web/modules/custom/*/templates, web/themes/custom/*/templates`

Defined in: `.env`, `scripts/drevops/lint-fe.sh`

### `DREVOPS_LINT_TYPES`

Linting types.

Provide argument as 'be' or 'fe' to lint only back-end or front-end code.<br />If no argument is provided, all code will be linted.

Default value: `be-fe`

Defined in: `scripts/drevops/lint.sh`

### `DREVOPS_MARIADB_DATABASE`

Local database name (not used in production).

Default value: `drupal`

Defined in: `.env`

### `DREVOPS_MARIADB_HOST`

Local database host (not used in production).

Default value: `mariadb`

Defined in: `.env`, `scripts/drevops/info.sh`

### `DREVOPS_MARIADB_PASSWORD`

Local database password (not used in production).

Default value: `drupal`

Defined in: `.env`, `scripts/drevops/info.sh`

### `DREVOPS_MARIADB_PORT`

Local database port (not used in production).

Default value: `3306`

Defined in: `.env`, `scripts/drevops/info.sh`

### `DREVOPS_MARIADB_USER`

Local database user (not used in production).

Default value: `drupal`

Defined in: `.env`, `scripts/drevops/info.sh`

### `DREVOPS_MIRROR_CODE_BRANCH_DST`

Destination branch name to mirror code.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_BRANCH_SRC`

Source branch name to mirror code.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_GIT_USER_EMAIL`

Name of the user who will be committing to a remote repository.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_GIT_USER_NAME`

Email address of the user who will be committing to a remote repository.

Default value: `Deployment Robot`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_PUSH`

Flag to push the branch.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_REMOTE_DST`

Destination remote name.

Default value: `origin`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_SSH_FILE`

Default value: `DREVOPS_MIRROR_CODE_SSH_FINGERPRINT//:/`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_MIRROR_CODE_SSH_FINGERPRINT`

Optional SSH key fingerprint to use for mirroring.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/mirror-code.sh`

### `DREVOPS_NOTIFY_BRANCH`

Deployment reference, such as a git SHA.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_CHANNELS`

The channels of the notifications.

Can be a combination of comma-separated values: email,newrelic,github,jira

Default value: `email`

Defined in: `.env`, `scripts/drevops/notify.sh`

### `DREVOPS_NOTIFY_EMAIL_ENVIRONMENT_URL`

Environment URL to notify about.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-email.sh`

### `DREVOPS_NOTIFY_EMAIL_FROM`

Email address to send notifications from.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-email.sh`

### `DREVOPS_NOTIFY_EMAIL_PROJECT`

Project name to notify.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-email.sh`

### `DREVOPS_NOTIFY_EMAIL_RECIPIENTS`

Email address(es) to send notifications to.

Multiple names can be specified as a comma-separated list of email addresses<br />with optional names in the format "email|name".<br />Example: "to1@example.com|Jane Doe, to2@example.com|John Doe"

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/notify-email.sh`

### `DREVOPS_NOTIFY_EMAIL_REF`

Git reference to notify about.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-email.sh`

### `DREVOPS_NOTIFY_ENVIRONMENT_TYPE`

Deployment environment type: production, uat, dev, pr.

Default value: `PR`

Defined in: `scripts/drevops/notify-github.sh`

### `DREVOPS_NOTIFY_ENVIRONMENT_URL`

Deployment environment URL.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-github.sh`, `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_EVENT`

The event to notify about. Can be 'pre_deployment' or 'post_deployment'.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-github.sh`, `scripts/drevops/notify.sh`

### `DREVOPS_NOTIFY_GITHUB_TOKEN`

Deployment GitHub token.

Default value: `GITHUB_TOKEN`

Defined in: `scripts/drevops/notify-github.sh`

### `DREVOPS_NOTIFY_JIRA_ASSIGNEE`

Assign the ticket to this account.

If left empty - no assignment will be performed.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_JIRA_COMMENT_PREFIX`

Deployment comment prefix.

Default value: `Deployed to `

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_JIRA_ENDPOINT`

JIRA API endpoint.

Default value: `https://jira.atlassian.com`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_JIRA_TOKEN`

JIRA token.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_JIRA_TRANSITION`

State to move the ticket to.

If left empty - no transition will be performed.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_JIRA_USER`

JIRA user.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-jira.sh`

### `DREVOPS_NOTIFY_NEWRELIC_APIKEY`

NewRelic API key, usually of type 'USER'.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_APPID`

Optional NewRelic Application ID.

Will be discovered automatically from application name if not provided.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_APP_NAME`

NewRelic application name as it appears in the dashboard.

Default value: `DREVOPS_NOTIFY_NEWRELIC_PROJECT-${DREVOPS_NOTIFY_NEWRELIC_SHA}`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_CHANGELOG`

Optional NewRelic notification changelog.

Defaults to the description.

Default value: `DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`

Optional NewRelic notification description.

Default value: `DREVOPS_NOTIFY_NEWRELIC_SHA deployed`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_ENDPOINT`

Optional NewRelic endpoint.

Default value: `https://api.newrelic.com/v2`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_PROJECT`

Project name to notify.

Default value: `DREVOPS_NOTIFY_PROJECT`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_SHA`

Deployment reference, such as a git SHA.

Default value: `DREVOPS_NOTIFY_SHA`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_NEWRELIC_USER`

Optional name of the user performing the deployment.

Default value: `Deployment robot`

Defined in: `scripts/drevops/notify-newrelic.sh`

### `DREVOPS_NOTIFY_PROJECT`

The project to notify about.

Default value: `DREVOPS_PROJECT`

Defined in: `scripts/drevops/notify.sh`

### `DREVOPS_NOTIFY_REF`

Deployment reference, such as a git SHA.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-github.sh`

### `DREVOPS_NOTIFY_REPOSITORY`

Deployment repository.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify-github.sh`

### `DREVOPS_NOTIFY_SKIP`

Flag to skip running of all notifications.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/notify.sh`

### `DREVOPS_NPM_VERBOSE`

Print debug information from NPM install.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/build.sh`

### `DREVOPS_PRODUCTION_BRANCH`

Dedicated branch to identify production environment.

Default value: `main`

Defined in: `.env`

### `DREVOPS_PROJECT`

Project name.<br />Drives internal naming within the codebase.<br />Does not affect the names of containers and development URL - those depend on<br />the project directory and can be overridden with COMPOSE_PROJECT_NAME.

Default value: `your_site`

Defined in: `.env`, `scripts/drevops/build.sh`, `scripts/drevops/info.sh`

### `DREVOPS_REDIS_ENABLED`

Enable Redis integration.<br />See settings.redis.php for details.

Default value: `UNDEFINED`

Defined in: `.env`

### `DREVOPS_TASK_COPY_DB_ACQUIA_DST`

Destination environment name to copy DB to.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-copy-db-acquia.sh`

### `DREVOPS_TASK_COPY_DB_ACQUIA_NAME`

Database name to copy.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-copy-db-acquia.sh`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SKIP`

Skip copying of database between Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_COPY_DB_ACQUIA_SRC`

Source environment name to copy DB from.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-copy-db-acquia.sh`

### `DREVOPS_TASK_COPY_DB_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `10`

Defined in: `scripts/drevops/task-copy-db-acquia.sh`

### `DREVOPS_TASK_COPY_DB_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not<br />yet finished, the task is considered failed.

Default value: `600`

Defined in: `scripts/drevops/task-copy-db-acquia.sh`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_DST`

Destination environment name to copy to.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-copy-files-acquia.sh`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SKIP`

Skip copying of files between Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_SRC`

Source environment name to copy from.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-copy-files-acquia.sh`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `10`

Defined in: `scripts/drevops/task-copy-files-acquia.sh`

### `DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not<br />yet finished, the task is considered failed.

Default value: `300`

Defined in: `scripts/drevops/task-copy-files-acquia.sh`

### `DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP`

Skip Drupal site installation in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_LAGOON_BIN_PATH`

Location of the Lagoon CLI binary.

Default value: `/tmp`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_BRANCH`

The Lagoon branch to run the task on.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_COMMAND`

The task command to execute.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_INSTALL_CLI_FORCE`

Flag to force the installation of Lagoon CLI.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_INSTANCE`

The Lagoon instance name to interact with.

Default value: `amazeeio`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_INSTANCE_GRAPHQL`

The Lagoon instance GraphQL endpoint to interact with.

Default value: `https://api.lagoon.amazeeio.cloud/graphql`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_INSTANCE_HOSTNAME`

The Lagoon instance hostname to interact with.

Default value: `ssh.lagoon.amazeeio.cloud`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_INSTANCE_PORT`

The Lagoon instance port to interact with.

Default value: `32222`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION`

Lagoon CLI version to use.

Default value: `latest`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_NAME`

The task name.

Default value: `Automation task`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_LAGOON_PROJECT`

The Lagoon project to run tasks for.

Default value: `LAGOON_PROJECT`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE`

File with a list of domains that should be purged.

Default value: `domains.txt`

Defined in: `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV`

An environment name to purge cache for.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_SKIP`

Skip purging of edge cache in Acquia environment.

Default value: `UNDEFINED`

Defined in: `ACQUIA ENVIRONMENT`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL`

Interval in seconds to check task status.

Default value: `10`

Defined in: `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES`

Number of status retrieval retries. If this limit reached and task has not<br />yet finished, the task is considered failed.

Default value: `300`

Defined in: `scripts/drevops/task-purge-cache-acquia.sh`

### `DREVOPS_TASK_SSH_FILE`

Default SSH file used if custom fingerprint is not provided.

Default value: `HOME/.ssh/id_rsa`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TASK_SSH_FINGERPRINT`

SSH key fingerprint used to connect to remote.

If not used, the currently loaded default SSH key (the key used for code<br />checkout) will be used or deployment will fail with an error if the default<br />SSH key is not loaded.<br />In most cases, the default SSH key does not work (because it is a read-only<br />key used by CircleCI to checkout code from git), so you should add another<br />deployment key.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/task-custom-lagoon.sh`

### `DREVOPS_TEST_ARTIFACT_DIR`

Directory to store test artifact files.

If set, the directory is created and the Behat screenshot extension will<br />store screenshots in this directory.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_BDD_ALLOW_FAILURE`

Allow BDD tests failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_BEHAT_FORMAT`

Behat format.

Default value: `pretty`

Defined in: `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_BEHAT_PROFILE`

Behat profile name.

Default value: `default`

Defined in: `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_BEHAT_TAGS`

Behat tags.

Allows to run only tests with specified tags, which will override the tags<br />set in the Behat profile.

Useful for running specific tests in CI without changing the codebase.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE`

Allow custom Functional tests failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/test-functional.sh`

### `DREVOPS_TEST_FUNCTIONAL_CONFIG`

Functional test configuration file.

Defaults to core's configuration file.

Default value: `DREVOPS_APP/${DREVOPS_WEBROOT}/core/phpunit.xml.dist`

Defined in: `scripts/drevops/test-functional.sh`

### `DREVOPS_TEST_FUNCTIONAL_GROUP`

Functional test group.

Running Functional tests tagged with `site:functional`.

Default value: `site:functional`

Defined in: `scripts/drevops/test-functional.sh`

### `DREVOPS_TEST_KERNEL_ALLOW_FAILURE`

Allow custom Kernel tests failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/test-kernel.sh`

### `DREVOPS_TEST_KERNEL_CONFIG`

Kernel test configuration file.

Defaults to core's configuration file.

Default value: `DREVOPS_APP/${DREVOPS_WEBROOT}/core/phpunit.xml.dist`

Defined in: `scripts/drevops/test-kernel.sh`

### `DREVOPS_TEST_KERNEL_GROUP`

Kernel test group.

Running Kernel tests tagged with `site:kernel`.

Default value: `site:kernel`

Defined in: `scripts/drevops/test-kernel.sh`

### `DREVOPS_TEST_PARALLEL_INDEX`

Test runner parallel index.

If is set, the value is used as a suffix for the Behat profile name (e.g. p0, p1).

Default value: `UNDEFINED`

Defined in: `scripts/drevops/test-bdd.sh`

### `DREVOPS_TEST_REPORTS_DIR`

Directory to store test result files.

If set, the directory is created and the JUnit formatter is used to generate<br />test result files.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/test-bdd.sh`, `scripts/drevops/test-functional.sh`, `scripts/drevops/test-kernel.sh`, `scripts/drevops/test-unit.sh`

### `DREVOPS_TEST_SKIP`

Flag to skip running of all tests.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/test.sh`

### `DREVOPS_TEST_TYPES`

Test types to run.

Can be a combination of comma-separated values (no spaces): unit,kernel,functional,bdd

Default value: `unit,kernel,functional,bdd`

Defined in: `scripts/drevops/test.sh`

### `DREVOPS_TEST_UNIT_ALLOW_FAILURE`

Allow custom Unit tests failures.

Default value: `UNDEFINED`

Defined in: `.env`, `scripts/drevops/test-unit.sh`

### `DREVOPS_TEST_UNIT_CONFIG`

Unit test configuration file.

Defaults to core's configuration file.

Default value: `DREVOPS_APP/${DREVOPS_WEBROOT}/core/phpunit.xml.dist`

Defined in: `scripts/drevops/test-unit.sh`

### `DREVOPS_TEST_UNIT_GROUP`

Unit test group.

Running Unit tests tagged with `site:unit`.

Default value: `site:unit`

Defined in: `scripts/drevops/test-unit.sh`

### `DREVOPS_TZ`

The timezone for the containers.

Default value: `Australia/Melbourne`

Defined in: `.env`

### `DREVOPS_WEBROOT`

Name of the webroot directory with Drupal installation.

Default value: `web`

Defined in: `.env`, `scripts/drevops/build.sh`, `scripts/drevops/clean.sh`, `scripts/drevops/download-db-lagoon.sh`, `scripts/drevops/drupal-install-site.sh`, `scripts/drevops/info.sh`, `scripts/drevops/lint-fe.sh`, `scripts/drevops/test-functional.sh`, `scripts/drevops/test-kernel.sh`, `scripts/drevops/test-unit.sh`

### `GITHUB_TOKEN`

GitHub token to perform operations.

Default value: `UNDEFINED`

Defined in: `scripts/drevops/github-labels.sh`

### `LAGOON_PROJECT`

Lagoon project name. May be different from [`$DREVOPS_PROJECT`](#DREVOPS_PROJECT).

Default value: `your_site`

Defined in: `.env`, `scripts/drevops/deploy-lagoon.sh`, `scripts/drevops/download-db-lagoon.sh`

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

### `TARGET_ENV_REMAP`

Special variable to remap target env to the sub-domain prefix based on UI name.

Default value: `target_env`

Defined in: `scripts/drevops/task-purge-cache-acquia.sh`

