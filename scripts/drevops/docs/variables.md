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

Name                                                    |Default value                            |Description
--------------------------------------------------------|-----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
`DREVOPS_BUILD_CODE_EXPORT_DIR`                         |`<NOT SET>`                              |Export code built within containers before adding development dependencies. This usually is not used locally, but used when production-grade code (without dev dependencies) is used.
`DREVOPS_BUILD_VERBOSE`                                 |`dev`                                    |Running 'ahoy up' directly will show the build progress.
`DREVOPS_COMMIT`                                        |`<NOT SET>`                              |Allow providing custom DrevOps commit hash to download the sources from.
`DREVOPS_COMPOSER_VALIDATE_LOCK`                        |`<NOT SET>`                              |
`DREVOPS_DB_DIR`                                        |`<NOT SET>`                              |Create data directory in the container and copy database dump file into container, but only if it exists, while also replacing relative directory path with absolute path. Note, that the DREVOPS_DB_DIR path is the same inside and outside of the container.
`DREVOPS_DB_DOCKER_IMAGE`                               |`<NOT SET>`                              |
`DREVOPS_DB_DOWNLOAD_CURL_URL`                          |`<NOT SET>`                              |URL of the remote database. If HTTP authentication required, it must be included in the variable.
`DREVOPS_DB_DOWNLOAD_FORCE`                             |`<NOT SET>`                              |Flag to force DB download even if the cache exists. Usually set in CircleCI UI to override per build cache.
`DREVOPS_DB_DOWNLOAD_FTP_FILE`                          |`<NOT SET>`                              |The file name, including any directories.
`DREVOPS_DB_DOWNLOAD_FTP_HOST`                          |`<NOT SET>`                              |The FTP host.
`DREVOPS_DB_DOWNLOAD_FTP_PASS`                          |`<NOT SET>`                              |The FTP password.
`DREVOPS_DB_DOWNLOAD_FTP_PORT`                          |`<NOT SET>`                              |The FTP port.
`DREVOPS_DB_DOWNLOAD_FTP_USER`                          |`<NOT SET>`                              |The FTP user.
`DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT`                |`master`                                 |The source environment for the database source.
`DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT`                    |`<NOT SET>`                              |Lagoon project name.
`DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR`                 |`tmp`                                    |Remote DB dump directory location.
`DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE`                |`db_`                                    |Remote DB dump file name. Cached by the date suffix.
`DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP`        |`db_`                                    |Wildcard file name to cleanup previously created dump files. Cleanup runs only if the variable is set and DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE does not exist.
`DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT`            |`<NOT SET>`                              |The SSH key fingerprint. If provided - the key will be looked-up and loaded into ssh client.
`DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST`                   |`ssh`                                    |The SSH host of the Lagoon environment.
`DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE`               |`<NOT SET>`                              |The SSH key used to SSH into Lagoon.
`DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT`                   |`<NOT SET>`                              |The SSH port of the Lagoon environment.
`DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER`                   |`DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT`     |The SSH user of the Lagoon environment.
`DREVOPS_DB_DOWNLOAD_POST_PROCESS`                      |`<NOT SET>`                              |Post process command or a script used for running after the database was downloaded.
`DREVOPS_DB_DOWNLOAD_PROCEED`                           |`<NOT SET>`                              |Kill-switch to proceed with download.
`DREVOPS_DB_DOWNLOAD_REFRESH`                           |`<NOT SET>`                              |Flag to download a fresh copy of the database.
`DREVOPS_DB_DOWNLOAD_SOURCE`                            |`curl`                                   |Where the database is downloaded from: - "url" - directly from URL as a file using CURL. - "ftp" - directly from FTP as a file using CURL. - "acquia" - from latest Acquia backup via Cloud API as a file. - "docker_registry" - from the docker registry as a docker image. - "none" - not downloaded, site is freshly installed for every build. Note that "docker_registry" works only for database-in-Docker-image database storage (when DREVOPS_DB_DOCKER_IMAGE variable has a value).
`DREVOPS_DB_EXPORT_BEFORE_IMPORT`                       |`<NOT SET>`                              |Flag to export database before import.
`DREVOPS_DB_FILE`                                       |`db`                                     |Database dump file name.
`DREVOPS_DB_IMPORT_PROGRESS`                            |`<NOT SET>`                              |Flag to use database import progress indicator (pv).
`DREVOPS_DB_OVERWRITE_EXISTING`                         |`<NOT SET>`                              |Flag to always overwrite existing database. Usually set to 0 in deployed environments.
`DREVOPS_DEBUG`                                         |`<NOT SET>`                              |
`DREVOPS_DEPLOY_BRANCH`                                 |`<NOT SET>`                              |
`DREVOPS_DEPLOY_CODE_GIT_BRANCH`                        |`<NOT SET>`                              |Remote repository branch. Can be a specific branch or a token. @see https://github.com/drevops/git-artifact#token-support
`DREVOPS_DEPLOY_CODE_GIT_REMOTE`                        |`<NOT SET>`                              |Remote repository to push code to.
`DREVOPS_DEPLOY_CODE_GIT_USER_EMAIL`                    |`<NOT SET>`                              |Name of the user who will be committing to a remote repository.
`DREVOPS_DEPLOY_CODE_GIT_USER_NAME`                     |`<NOT SET>`                              |Email address of the user who will be committing to a remote repository.
`DREVOPS_DEPLOY_CODE_REPORT_FILE`                       |`DREVOPS_DEPLOY_CODE_ROOT`               |Deployment report file name.
`DREVOPS_DEPLOY_CODE_ROOT`                              |`<NOT SET>`                              |The root directory where the deployment script should run from. Defaults to the current directory.
`DREVOPS_DEPLOY_CODE_SRC`                               |`<NOT SET>`                              |Source of the code to be used for artifact building.
`DREVOPS_DEPLOY_CODE_SSH_FILE`                          |`HOME`                                   |Default SSH file used if custom fingerprint is not provided.
`DREVOPS_DEPLOY_CODE_SSH_FINGERPRINT`                   |`<NOT SET>`                              |SSH key fingerprint used to connect to remote. If not used, the currently loaded default SSH key (the key used for code checkout) will be used or deployment will fail with an error if the default SSH key is not loaded. In most cases, the default SSH key does not work (because it is a read-only key used by CircleCI to checkout code from git), so you should add another deployment key.
`DREVOPS_DEPLOY_DOCKER_IMAGE_TAG`                       |`latest`                                 |The tag of the image to push to. Defaults to 'latest'.
`DREVOPS_DEPLOY_DOCKER_MAP`                             |`<NOT SET>`                              |Comma-separated map of docker services and images to use for deployment in format "service1=org/image1,service2=org/image2".
`DREVOPS_DEPLOY_DOCKER_REGISTRY`                        |`docker`                                 |Docker registry name. Provide port, if required as <server_name>:<port>. Defaults to DockerHub.
`DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN`                  |`<NOT SET>`                              |
`DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME`               |`<NOT SET>`                              |Docker registry credentials to read and write Docker images. Note that for CI, these variables should be set through UI.
`DREVOPS_DEPLOY_LAGOON_ACTION`                          |`create`                                 |
`DREVOPS_DEPLOY_LAGOON_BRANCH`                          |`<NOT SET>`                              |The Lagoon branch to deploy.
`DREVOPS_DEPLOY_LAGOON_INSTANCE`                        |`amazeeio`                               |The Lagoon instance to interact with.
`DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH`              |`<NOT SET>`                              |Location of the Lagoon CLI binary.
`DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL`         |`<NOT SET>`                              |Flag to force the installation of Lagoon CLI.
`DREVOPS_DEPLOY_LAGOON_PR`                              |`<NOT SET>`                              |The PR number to deploy.
`DREVOPS_DEPLOY_LAGOON_PROJECT`                         |`<NOT SET>`                              |The Lagoon project to perform deployment for.
`DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH`                  |`develop`                                |The PR base branch (the branch the PR is raised against). Defaults to 'develop'.
`DREVOPS_DEPLOY_LAGOON_PR_HEAD`                         |`<NOT SET>`                              |The PR head branch to deploy.
`DREVOPS_DEPLOY_LAGOON_SSH_FILE`                        |`HOME`                                   |Default SSH file used if custom fingerprint is not provided.
`DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT`                 |`<NOT SET>`                              |SSH key fingerprint used to connect to remote. If not used, the currently loaded default SSH key (the key used for code checkout) will be used or deployment will fail with an error if the default SSH key is not loaded. In most cases, the default SSH key does not work (because it is a read-only key used by CircleCI to checkout code from git), so you should add another deployment key.
`DREVOPS_DEPLOY_PR`                                     |`<NOT SET>`                              |
`DREVOPS_DEPLOY_PROCEED`                                |`<NOT SET>`                              |Flag to proceed with deployment.
`DREVOPS_DEPLOY_SKIP`                                   |`<NOT SET>`                              |Flag to allow skipping of a deployment using additional flags. Different to DREVOPS_DEPLOY_PROCEED in a way that DREVOPS_DEPLOY_PROCEED is a failsafe to prevent any deployments, while DREVOPS_DEPLOY_SKIP allows to selectively skip certain deployments using 'DREVOPS_DEPLOY_SKIP_PR_<NUMBER>' and 'DREVOPS_DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>' variables.
`DREVOPS_DEPLOY_TYPE`                                   |`<NOT SET>`                              |The type of deployment. Can be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon.
`DREVOPS_DEPLOY_WEBHOOK_METHOD`                         |`GET`                                    |Webhook call method.
`DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS`                |`<NOT SET>`                              |The status code of the expected response.
`DREVOPS_DEPLOY_WEBHOOK_URL`                            |`<NOT SET>`                              |The URL of the webhook to call. Note that any tokens should be added to the value of this variable outside this script.
`DREVOPS_DOCKER_IMAGE`                                  |`<NOT SET>`                              |Docker image passed as a first argument to this script in a form of <org>/<repository>.
`DREVOPS_DOCKER_IMAGE_ARCHIVE`                          |`<NOT SET>`                              |Docker image archive file to restore passed as a second argument to this script.
`DREVOPS_DOCKER_REGISTRY`                               |`DREVOPS_DEPLOY_DOCKER_REGISTRY`         |
`DREVOPS_DOCKER_REGISTRY_TOKEN`                         |`DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN`   |
`DREVOPS_DOCKER_REGISTRY_USERNAME`                      |`DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME`|Login to the registry.
`DREVOPS_DOCKER_SERVICE_NAME`                           |`mariadb`                                |The service name to capture. Optional. Defaults to "mariadb".
`DREVOPS_DOCTOR_CHECK_BOOTSTRAP`                        |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_CONTAINERS`                       |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_MINIMAL`                          |`<NOT SET>`                              |Shortcut to set variables for minimal requirements checking.
`DREVOPS_DOCTOR_CHECK_PORT`                             |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_PREFLIGHT`                        |`ahoy`                                   |Check all pre-requisites before starting the stack.
`DREVOPS_DOCTOR_CHECK_PYGMY`                            |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_SSH`                              |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_TOOLS`                            |`<NOT SET>`                              |
`DREVOPS_DOCTOR_CHECK_WEBSERVER`                        |`<NOT SET>`                              |
`DREVOPS_DOCTOR_SSH_KEY_FILE`                           |`HOME`                                   |
`DREVOPS_DRUPAL_ADMIN_EMAIL`                            |`<NOT SET>`                              |User mail could have been sanitized - setting it back to a pre-defined mail.
`DREVOPS_DRUPAL_BUILD_WITH_MAINTENANCE_MODE`            |`<NOT SET>`                              |
`DREVOPS_DRUPAL_CONFIG_LABEL`                           |`<NOT SET>`                              |Config label.
`DREVOPS_DRUPAL_CONFIG_PATH`                            |`APP`                                    |Path to configuration directory.
`DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE`            |`APP`                                    |Path to file with custom sanitization SQL queries. To skip custom sanitization, remove the DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE file from the codebase.
`DREVOPS_DRUPAL_DB_SANITIZE_EMAIL`                      |`user`                                   |Database sanitized account email replacement.
`DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD`                   |`RANDOM`                                 |Database sanitized account password replacement.
`DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL`|`<NOT SET>`                              |
`DREVOPS_DRUPAL_DB_SANITIZE_SKIP`                       |`<NOT SET>`                              |Flag to skip DB sanitization.
`DREVOPS_DRUPAL_FORCE_FRESH_INSTALL`                    |`<NOT SET>`                              |Flag to force fresh install even if the site exists.
`DREVOPS_DRUPAL_MODULE_PREFIX`                          |`<NOT SET>`                              |Drupal custom module prefix. @todo Remove this as modeule prefix is not used anywhere.
`DREVOPS_DRUPAL_PRIVATE_FILES`                          |`APP`                                    |Path to private files.
`DREVOPS_DRUPAL_PROFILE`                                |`standard`                               |Profile machine name.
`DREVOPS_DRUPAL_SITE_MAIL`                              |`webmaster`                              |Drupal site name
`DREVOPS_DRUPAL_SITE_NAME`                              |`Example`                                |Drupal site name
`DREVOPS_DRUPAL_SKIP_DB_IMPORT`                         |`<NOT SET>`                              |Flag to skip DB import.
`DREVOPS_DRUPAL_SKIP_POST_DB_IMPORT`                    |`<NOT SET>`                              |Flag to skip running post DB import commands.
`DREVOPS_DRUPAL_THEME`                                  |`<NOT SET>`                              |
`DREVOPS_DRUPAL_UNBLOCK_ADMIN`                          |`<NOT SET>`                              |Flag to unblock admin.
`DREVOPS_GITHUB_DELETE_EXISTING_LABELS`                 |`<NOT SET>`                              |Delete existing labels to mirror the list below.
`DREVOPS_GITHUB_REPO`                                   |`<NOT SET>`                              |GitHub repository as "org/name" to perform operations on.
`DREVOPS_GITHUB_TOKEN`                                  |`<NOT SET>`                              |!/usr/bin/env bash Update project labels in GitHub. @usage: Interactive prompt: ./github-labels
`DREVOPS_HOST_DB_PORT`                                  |`<NOT SET>`                              |
`DREVOPS_HOST_SOLR_PORT`                                |`<NOT SET>`                              |
`DREVOPS_INSTALLER_URL`                                 |`https`                                  |The URL of the installer script.
`DREVOPS_LINT_BE_ALLOW_FAILURE`                         |`<NOT SET>`                              |Flag to allow BE lint to fail.
`DREVOPS_LINT_FE_ALLOW_FAILURE`                         |`<NOT SET>`                              |Flag to allow FE lint to fail.
`DREVOPS_LINT_PHPCS_TARGETS`                            |`<NOT SET>`                              |Comma-separated list of PHPCS targets (no spaces).
`DREVOPS_LINT_PHPLINT_EXTENSIONS`                       |`php`                                    |PHP Parallel Lint extensions as a comma-separated list of extensions with no preceding dot or space.
`DREVOPS_LINT_PHPLINT_TARGETS`                          |`<NOT SET>`                              |PHP Parallel Lint targets as a comma-separated list of extensions with no preceding dot or space.
`DREVOPS_LINT_TYPE`                                     |`<NOT SET>`                              |Provide argument as 'be' or 'fe' to lint only back-end or front-end code. If no argument is provided, all code will be linted.
`DREVOPS_LOCALDEV_URL`                                  |`<NOT SET>`                              |
`DREVOPS_MARIADB_HOST`                                  |`<NOT SET>`                              |
`DREVOPS_MARIADB_PASSWORD`                              |`<NOT SET>`                              |
`DREVOPS_MARIADB_PORT`                                  |`<NOT SET>`                              |
`DREVOPS_MARIADB_USER`                                  |`<NOT SET>`                              |
`DREVOPS_NOTIFY_DEPLOYMENT_SKIP`                        |`<NOT SET>`                              |
`DREVOPS_NOTIFY_DEPLOY_BRANCH`                          |`<NOT SET>`                              |Deployment reference, such as a git SHA.
`DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE`                |`PR`                                     |Deployment environment type: production, uat, dev, pr.
`DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL`                 |`<NOT SET>`                              |Deployment environment URL.
`DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION`                |`<NOT SET>`                              |Operation type: start or finish.
`DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE`                   |`<NOT SET>`                              |
`DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN`                      |`<NOT SET>`                              |JIRA token.
`DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION`                 |`<NOT SET>`                              |State to move the ticket to.
`DREVOPS_NOTIFY_DEPLOY_JIRA_USER`                       |`<NOT SET>`                              |JIRA user.
`DREVOPS_NOTIFY_DEPLOY_REF`                             |`<NOT SET>`                              |Deployment reference, such as a git SHA.
`DREVOPS_NOTIFY_DEPLOY_REPOSITORY`                      |`<NOT SET>`                              |Deployment repository.
`DREVOPS_NOTIFY_GITHUB_TOKEN`                           |`GITHUB_TOKEN`                           |Deployment GitHub token.
`DREVOPS_NOTIFY_JIRA_ENDPOINT`                          |`https`                                  |JIRA API endpoint.l
`DREVOPS_NOTIFY_NEWRELIC_APIKEY`                        |`<NOT SET>`                              |The API key. Usually of type 'USER'.
`DREVOPS_NOTIFY_NEWRELIC_APPID`                         |`<NOT SET>`                              |Optional Application ID. Will be discovered automatically from application name if not provided.
`DREVOPS_NOTIFY_NEWRELIC_APPNAME`                       |`<NOT SET>`                              |Application name as it appears in the dashboard.
`DREVOPS_NOTIFY_NEWRELIC_CHANGELOG`                     |`DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`    |Optional deployment changelog. Defaults to description.
`DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION`                   |`<NOT SET>`                              |Optional deployment description.
`DREVOPS_NOTIFY_NEWRELIC_ENDPOINT`                      |`https`                                  |Optional endpoint.
`DREVOPS_NOTIFY_NEWRELIC_USER`                          |`<NOT SET>`                              |Optional user name performing the deployment.
`DREVOPS_PROJECT`                                       |`<NOT SET>`                              |
`DREVOPS_SHOW_LOGIN_LINK`                               |`ahoy`                                   |Show project information and a one-time login link.
`DREVOPS_TEST_ARTIFACT_DIR`                             |`<NOT SET>`                              |Directory to store test artifact files.
`DREVOPS_TEST_BDD_ALLOW_FAILURE`                        |`<NOT SET>`                              |Flag to allow BDD tests to fail.
`DREVOPS_TEST_BEHAT_FORMAT`                             |`pretty`                                 |Behat format. Optional. Defaults to "pretty".
`DREVOPS_TEST_BEHAT_PARALLEL_INDEX`                     |`<NOT SET>`                              |Behat test runner index. If is set  - the value is used as a suffix for the parallel Behat profile name (e.g., p0, p1).
`DREVOPS_TEST_BEHAT_PROFILE`                            |`default`                                |Behat profile name. Optional. Defaults to "default".
`DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE`                 |`<NOT SET>`                              |Flag to allow Functional tests to fail.
`DREVOPS_TEST_KERNEL_ALLOW_FAILURE`                     |`<NOT SET>`                              |Flag to allow Kernel tests to fail.
`DREVOPS_TEST_REPORTS_DIR`                              |`<NOT SET>`                              |Directory to store test result files.
`DREVOPS_TEST_TYPE`                                     |`unit`                                   |Get test type or fallback to defaults.
`DREVOPS_TEST_UNIT_ALLOW_FAILURE`                       |`<NOT SET>`                              |Flag to allow Unit tests to fail.
`account_id`                                            |`echo`                                   |
`branch_skip_var`                                       |`<NOT SET>`                              |
`build_verbose_output`                                  |`<NOT SET>`                              |
`cid`                                                   |`<NOT SET>`                              |
`cmd`                                                   |`<NOT SET>`                              |
`color`                                                 |`color`                                  |
`comment`                                               |`<NOT SET>`                              |
`comment_id`                                            |`<NOT SET>`                              |
`data`                                                  |`<NOT SET>`                              |
`db_dump_compressed`                                    |`db_dump_file_actual`                    |
`db_dump_discovered`                                    |`db_dump_file_actual`                    |
`db_dump_ext`                                           |`DREVOPS_DB_FILE`                        |Insert backup id as a suffix.
`db_dump_file_actual`                                   |`DREVOPS_DB_DIR`                         |
`db_dump_file_actual_prefix`                            |`AC_API_DB_NAME`                         |
`deploy_pr_full`                                        |`<NOT SET>`                              |
`deployment_id`                                         |`<NOT SET>`                              |Check deployment ID.
`docker_service`                                        |`<NOT SET>`                              |shellcheck disable=SC2143
`docker_services`                                       |`<NOT SET>`                              |
`drush`                                                 |`if`                                     |Use local or global Drush.
`dump_file`                                             |`echo`                                   |Create dump file name with a timestamp or use the file name provided as a first argument.
`environment`                                           |`<NOT SET>`                              |
`existing_label_name`                                   |`<NOT SET>`                              |
`existing_labels`                                       |`<NOT SET>`                              |
`existing_labels_strings`                               |`n`                                      |shellcheck disable=SC2207
`file`                                                  |`<NOT SET>`                              |
`found_db`                                              |`<NOT SET>`                              |
`iid`                                                   |`<NOT SET>`                              |
`image`                                                 |`<NOT SET>`                              |
`images`                                                |`()`                                     |
`input_color`                                           |`<NOT SET>`                              |
`is_redeploy`                                           |`<NOT SET>`                              |Re-deployment of the existing environment.
`issue`                                                 |`extract_issue`                          |
`json`                                                  |`echo`                                   |
`key`                                                   |`<NOT SET>`                              |
`label`                                                 |`<NOT SET>`                              |
`labels`                                                |`(`                                      |Array of labels to create. If DELETE_EXISTING_LABELS=1, the labels list will be exactly as below, otherwise labels below will be added to existing ones.
`latest_symlink`                                        |`latest_symlink`                         |
`message`                                               |`<NOT SET>`                              |
`name`                                                  |`<NOT SET>`                              |
`names`                                                 |`<NOT SET>`                              |
`new_image`                                             |`<NOT SET>`                              |
`parts`                                                 |`<NOT SET>`                              |
`payload`                                               |`echo`                                   |
`phpunit_opts`                                          |`<NOT SET>`                              |
`pr_skip_var`                                           |`<NOT SET>`                              |
`prefix`                                                |`prefix`                                 |
`prop`                                                  |`<NOT SET>`                              |
`repo_org`                                              |`<NOT SET>`                              |
`res`                                                   |`<NOT SET>`                              |Try homebrew lookup, if brew is available.
`response`                                              |`<NOT SET>`                              |
`rsync_opts`                                            |`e`                                      |
`s`                                                     |`s`                                      |
`safe_branch_name`                                      |`DREVOPS_DEPLOY_SKIP_BRANCH_`            |
`seconds`                                               |`<NOT SET>`                              |
`service`                                               |`<NOT SET>`                              |
`services`                                              |`()`                                     |
`site_is_installed`                                     |`drush`                                  |
`ssh_opts`                                              |`o`                                      |
`ssh_opts_string`                                       |`ssh_opts`                               |
`status`                                                |`<NOT SET>`                              |
`t`                                                     |`mktemp`                                 |Read variables from .env file, respecting existing environment variable values. shellcheck disable=SC1090,SC1091
`temp`                                                  |`temp`                                   |
`token`                                                 |`<NOT SET>`                              |
`transition_id`                                         |`<NOT SET>`                              |
`value`                                                 |`read`                                   |
`values`                                                |`<NOT SET>`                              |
