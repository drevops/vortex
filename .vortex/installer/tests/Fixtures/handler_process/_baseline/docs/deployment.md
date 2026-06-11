# Deployment

For information on how deployment works, see
[Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).

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
`sw_base` (PRE phase, non-production), and the content migration in `ys_migrate`
(POST phase, non-production).

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
