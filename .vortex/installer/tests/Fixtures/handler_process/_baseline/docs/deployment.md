# Deployment

For information on how deployment works, see
[Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).

## Repeatable deploy hooks

Logic that must run on **every** deploy lives in the `ys_deploy` module
(`web/modules/custom/ys_deploy`), not in run-once hooks. Drupal and Drush
run-once hooks (`hook_update_N()`, `hook_post_update_NAME()`,
`hook_deploy_NAME()`) are recorded as completed and never run again, so they
cannot express "run on every deploy".

A project has two deploy-time layers:

- **Drupal-level "every deploy"** - the Drush command hooks in `ys_deploy`. They
  run wherever `drush deploy:hook` runs (CI, local, and production hosting after
  rollout). Add idempotent steps to `preDeploySteps()` or `postDeploySteps()` in
  `DeployCommands`.
- **Vortex tooling-level** - the pre/post provision event scripts, for
  orchestration that happens outside `drush deploy` (for example, work before
  the database is imported).

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
