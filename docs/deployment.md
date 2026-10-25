# Deployment

To learn how deployment works, see the [Vortex deployment
documentation](https://www.vortextemplate.com/docs/deployment).

[//]: # (#;< HOSTING_ACQUIA)

## Hosting provider

This project is hosted on [Acquia
Cloud](https://www.acquia.com/products/drupal-cloud).

For setup and configuration details, see the [Acquia hosting
documentation](https://www.vortextemplate.com/docs/hosting/acquia).

### Deployment workflow

1. Code is pushed to GitHub (source repository).
2. CI builds and tests the code.
3. On success, CI builds an artifact and pushes it to Acquia Cloud
   (destination repository).
4. Acquia Cloud runs deployment hooks.

### Branch naming on Acquia Cloud

- Feature branches (`feature/ABC-123`) → same name on Acquia
- Release tags (`0.1.4`) → `deployment/0.1.4` branch on Acquia

### Important rules

- Never push directly to the Acquia Cloud repository.
- Only the Technical Lead and the Deployer user should have access to the
  Acquia repository.
- The Technical Lead should regularly clean up `feature/*` and `bugfix/*`
  branches.

[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< HOSTING_LAGOON)

## Hosting provider

This project is hosted on [Lagoon](https://www.amazee.io/lagoon).

For setup and configuration details, see the [Lagoon hosting
documentation](https://www.vortextemplate.com/docs/hosting/lagoon).

### Database refresh

To refresh the database in an existing Lagoon environment with production
data, run:

```bash
VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
```

### Maintenance page

To serve a static maintenance page in place of the site, set the
`VORTEX_WEBSERVER_MAINTENANCE_MODE` variable to `1` on the environment and
redeploy it. The `webserver` container then answers every request with the
page in `.docker/config/nginx/maintenance.html`. See [Serve a maintenance
page](https://www.vortextemplate.com/docs/hosting/lagoon#serve-a-maintenance-page).

[//]: # (#;> HOSTING_LAGOON)

## Repeatable deploy hooks

Put logic that must run on **every** deploy in a `DeployStep` plugin.
Drupal and Drush record their run-once hooks as completed and never run
them again. These are `hook_update_N()`, `hook_post_update_NAME()`, and
`hook_deploy_NAME()`.

The [`deploy_steps`](https://www.drupal.org/project/deploy_steps) module is
a Composer dependency of this project. It adds a Drush `pre-command` and
`post-command` hook to `deploy:hook`. On every deploy, these hooks:

1. Discover every `DeployStep` plugin in every enabled module.
2. Group the plugins by phase and order each phase by weight.
3. Check each plugin's skip reason and run the plugins that don't skip.

The steps run wherever `drush deploy:hook` runs: in CI, locally, and on
production hosting.

To add a step, declare a `DeployStep` plugin in the `Plugin/DeployStep/`
namespace of any enabled module. The plugin defines:

- **`phase`** - `PHASE_PRE` (before the `deploy:hook` body) or `PHASE_POST`
  (after it). A PRE-phase plugin that enables modules lets the body run
  their `hook_deploy_NAME()` in the same deploy.
- **`weight`** - run order within the phase (lower first).
- **`skip()`** - returns `NULL` to run, or a short reason to skip, which is
  logged verbatim. To skip by environment, compose `EnvironmentTrait` for
  the `environment()` helper.
- **`run()`** - the idempotent work. To run a long sub-command (for example,
  `migrate:import`) in its own memory-bounded, resumable subprocess,
  compose `DrushTrait` for the `drush()` helper.

For working examples, see the development and demo environment setup in
`ys_base` (PRE phase, non-production) and the content migration in
`ys_migrate` (POST phase, non-production).

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
