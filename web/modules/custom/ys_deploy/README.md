# YOURSITE Deploy

Hosts repeatable, run-on-every-deploy logic for this project via Drush command hooks.

## Why this module exists

Drupal and Drush run-once hooks (`hook_update_N()`, `hook_post_update_NAME()`, `hook_deploy_NAME()`) are recorded as completed and never run again. They cannot express "run on every deploy". This module provides that missing layer using Drush `pre-command` / `post-command` hooks, which are not run-once tracked.

## Two deploy-time layers

- **Drupal-level "every deploy"** -> the command hooks in this module. They run wherever `drush deploy:hook` runs: CI, local, and production hosting after rollout.
- **Vortex tooling-level** -> the pre/post provision event scripts, for orchestration that happens outside `drush deploy` (for example, work before the database is imported).

The hooks target `deploy:hook` because that is the Drush command the Vortex provision flow runs in every environment. The higher-level `deploy` command is never invoked directly: the provision flow runs its underlying steps (`updatedb`, config import, cache rebuild, `deploy:hook`) individually.

## Adding your own steps

Edit `src/Drush/Commands/DeployCommands.php` and add entries to `preDeploySteps()` or `postDeploySteps()`. Each entry is a human-readable label mapped to a callable; steps run in array order. Keep every step idempotent - it runs on every single deploy.

```php
protected function postDeploySteps(): array {
  return [
    'Rebuild the search index' => fn() => $this->reindexSearch(),
    'Re-run a config migration' => fn() => $this->runMigration('my_migration'),
  ];
}
```

Helper methods are available for common operations: `installModules()` to enable modules idempotently, and `environment()` / `isProduction()` for environment-aware behavior.
