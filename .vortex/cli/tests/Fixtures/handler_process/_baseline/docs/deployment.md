# Deployment

To learn how deployment works, see the [Vortex deployment
documentation](https://www.vortextemplate.com/docs/deployment).

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
`sw_base` (PRE phase, non-production) and the content migration in
`ys_migrate` (POST phase, non-production).

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
