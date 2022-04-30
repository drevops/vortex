# Variables

## Rules

1. All DrevOps variables MUST start with `DREVOPS_`.<br/>
   This is to clearly distinguish between DrevOps and 3rd party variables.
2. All DrevOps action-specific variables MUST have a prefix. If action has a
   specific service provider attached - a second prefix MUST be added.<br/>
   For example, to specify API key for NewRelic deployment notification call,
   the variable name is `DREVOPS_NOTIFY_NEWRELIC_API_KEY`, where `NOTIFY` is an
   "action" and `NEWRELIC` is a "service provider".
3. Project-base variables SHOULD start with project prefix (usually the value
   of `DREVOPS_DRUPAL_MODULE_PREFIX`).<br/>
   This is to clearly distinguish between DrevOps, 3rd party services variables
   and per-project variables.<br/>
   For example, to specify a user for Drupal's Shield module configuration,
   use `YOURSITE_DRUPAL_SHIELD_USER`, where `YOURSITE` is your site project
   prefix and `DRUPAL` is a name of the service.
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

| **New name**                                      | **Default value**                                                                                                                     | **Description**   |
|---------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------|-------------------|
| `APP`                                             | `/app`                                                                                                                                |                   |
| `COMPOSE_PROJECT_NAME`                            | `your_site`                                                                                                                           |                   |
| `DREVOPS_ALLOW_BDD_TESTS_FAIL`                    | `0`                                                                                                                                   |                   |
| `DREVOPS_ALLOW_BE_LINT_FAIL`                      | `0`                                                                                                                                   |                   |
| `DREVOPS_ALLOW_FE_LINT_FAIL`                      | `0`                                                                                                                                   |                   |
| `DREVOPS_ALLOW_FUNCTIONAL_TESTS_FAIL`             | `0`                                                                                                                                   |                   |
| `DREVOPS_ALLOW_KERNEL_TESTS_FAIL`                 | `0`                                                                                                                                   |                   |
| `DREVOPS_ALLOW_UNIT_TESTS_FAIL`                   | `0`                                                                                                                                   |                   |
| `DREVOPS_BUILD_VERBOSE`                           | `1`                                                                                                                                   |                   |
| `DREVOPS_COMMIT`                                  | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_COMPOSER_VALIDATE_LOCK`                  | `1`                                                                                                                                   |                   |
| `DREVOPS_CURL_DB_URL`                             | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DATABASE_DOWNLOAD_SOURCE`                | `curl`                                                                                                                                |                   |
| `DREVOPS_DATABASE_IMAGE`                          | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DB_DIR`                                  | `./.data`                                                                                                                             |                   |
| `DREVOPS_DB_EXPORT_BEFORE_IMPORT`                 | `0`                                                                                                                                   |                   |
| `DREVOPS_DB_FILE`                                 | `db.sql`                                                                                                                              |                   |
| `DREVOPS_DB_FTP_HOST`                             | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DB_FTP_PASS`                             | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DB_FTP_USER`                             | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DB_OVERWRITE_EXISTING`                   | `0`                                                                                                                                   |                   |
| `DREVOPS_DB_SANITIZE_EMAIL`                       | `%uid@@your-site-url`                                                                                                                 |                   |
| `DREVOPS_DB_SANITIZE_PASSWORD`                    | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL` | `0`                                                                                                                                   |                   |
| `DREVOPS_DEBUG`                                   | `1`                                                                                                                                   |                   |
| `DREVOPS_DEPLOY_CODE_GIT_REMOTE`                  | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DEPLOY_CODE_GIT_USER_EMAIL`              | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DEPLOY_CODE_GIT_USER_NAME`               | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DEPLOY_PROCEED`                          | `1`                                                                                                                                   |                   |
| `DREVOPS_DEPLOY_REPORT_FILE`                      | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DEPLOY_TYPE`                             | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS`          | `200`                                                                                                                                 |                   |
| `DREVOPS_DEPLOY_WEBHOOK_METHOD`                   | `GET`                                                                                                                                 |                   |
| `DREVOPS_DEPLOY_WEBHOOK_URL`                      | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DOCKER_REGISTRY`                         | `docker.io`                                                                                                                           |                   |
| `DREVOPS_DOCKER_REGISTRY_TOKEN`                   | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DOCKER_REGISTRY_USERNAME`                | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DRUPAL_ADMIN_EMAIL`                      | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_DRUPAL_BUILD_WITH_MAINTENANCE_MODE`      | `1`                                                                                                                                   |                   |
| `DREVOPS_DRUPAL_MODULE_PREFIX`                    | `your_site`                                                                                                                           |                   |
| `DREVOPS_DRUPAL_PROFILE`                          | `your_site_profile`                                                                                                                   |                   |
| `DREVOPS_DRUPAL_SITE_NAME`                        | `YOURSITE`                                                                                                                            |                   |
| `DREVOPS_DRUPAL_THEME`                            | `your_site_theme`                                                                                                                     |                   |
| `DREVOPS_DRUPAL_UNBLOCK_ADMIN`                    | `1`                                                                                                                                   |                   |
| `DREVOPS_DRUPAL_VERSION`                          | `9`                                                                                                                                   |                   |
| `DREVOPS_FORCE_FRESH_INSTALL`                     | `0`                                                                                                                                   |                   |
| `DREVOPS_LAGOON_INTEGRATION_COMPLETE`             | `0`                                                                                                                                   |                   |
| `DREVOPS_LOCALDEV_URL`                            | `your-site.docker.amazee.io`                                                                                                          |                   |
| `DREVOPS_NOTIFY_DEPLOY_GITHUB_TOKEN`              | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN`                | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_NOTIFY_DEPLOY_JIRA_USER`                 | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_NOTIFY_NEWRELIC_APIKEY`                  | `<EMPTY>`                                                                                                                             |                   |
| `DREVOPS_PHPCS_TARGETS`                           | `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests` |                   |
| `DREVOPS_PHPLINT_EXTENSIONS`                      | `php, inc, module, theme, install`                                                                                                    |                   |
| `DREVOPS_PHPLINT_TARGETS`                         | `docroot/profiles/custom/your_site_profile, docroot/modules/custom, docroot/themes/custom, docroot/sites/default/settings.php, tests` |                   |
| `DREVOPS_PROJECT`                                 | `your_site`                                                                                                                           |                   |
| `WEBROOT`                                         |                                                                                                                                       |                   |
