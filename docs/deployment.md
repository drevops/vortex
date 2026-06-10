# Deployment

For information on how deployment works, see
[Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).

[//]: # (#;< HOSTING_ACQUIA)

## Hosting provider

This project is hosted on [Acquia Cloud](https://www.acquia.com/products/drupal-cloud).

See [Acquia hosting documentation](https://www.vortextemplate.com/docs/hosting/acquia)
for setup and configuration details.

### Deployment workflow

1. Code is pushed to GitHub (source repository).
2. CI builds and tests the code.
3. On success, CI builds an artifact and pushes to Acquia Cloud (destination
   repository).
4. Acquia Cloud runs deployment hooks.

### Branch naming on Acquia Cloud

- Feature branches (`feature/ABC-123`) → same name on Acquia
- Release tags (`0.1.4`) → `deployment/0.1.4` branch on Acquia

### Important rules

- No direct pushes to Acquia Cloud repository.
- Only Technical Lead and Deployer user should have access to Acquia repository.
- Technical Lead should regularly clean up `feature/*` and `bugfix/*` branches.

[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< HOSTING_LAGOON)

## Hosting provider

This project is hosted on [Lagoon](https://www.amazee.io/lagoon).

See [Lagoon hosting documentation](https://www.vortextemplate.com/docs/hosting/lagoon)
for setup and configuration details.

### Database refresh

To refresh the database in an existing Lagoon environment with production data:

```bash
VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
```

[//]: # (#;> HOSTING_LAGOON)

## Repeatable deploy hooks

Logic that must run on **every** deploy is a `PersistentDeploy` plugin, not a
run-once hook. Drupal and Drush run-once hooks (`hook_update_N()`,
`hook_post_update_NAME()`, `hook_deploy_NAME()`) are recorded as completed and
never run again, so they cannot express "run on every deploy".

The `persistent_deploy` module (`web/modules/custom/persistent_deploy`) owns a
single pair of Drush `pre-command` / `post-command` hooks on `deploy:hook` and,
on every deploy, discovers every `PersistentDeploy` plugin from every enabled
module, groups them by phase, orders each phase by weight, asks each plugin's
gate whether to run, and runs the rest. They run wherever `drush deploy:hook`
runs - CI, local, and production hosting.

Add a step by declaring a `PersistentDeploy` plugin in any enabled module's
`Plugin/PersistentDeploy/` namespace:

- **`phase`** - `PHASE_PRE` (before the `deploy:hook` body) or `PHASE_POST`
  (after it). A PRE-phase plugin that enables modules lets the body run their
  `hook_deploy_NAME()` in the same deploy.
- **`weight`** - run order within the phase (lower first).
- **`gate()`** - return `NULL` to run, or a short reason to skip (logged
  verbatim). The inherited `environment()` / `isProduction()` helpers cover
  environment gating.
- **`run()`** - the idempotent work. Use the inherited `drush()` helper to run a
  long sub-command (for example `migrate:import`) in its own memory-bounded,
  resumable subprocess.

This project ships two examples: the development and demo environment setup in
`ys_base` (PRE phase, non-production), and the content migration in `ys_migrate`
(POST phase, non-production).

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
