# Persistent Deploy

Runs repeatable, run-on-every-deploy logic as discoverable **persistent deploy** plugins.

## Why this module exists

Drupal and Drush run-once hooks (`hook_update_N()`, `hook_post_update_NAME()`, `hook_deploy_NAME()`) are recorded as completed and never run again - they cannot express "run on every deploy". This module provides that missing layer.

It owns the single pair of Drush `pre-command` / `post-command` hooks on `deploy:hook`, and on every deploy it **discovers** every `PersistentDeploy` plugin from every enabled module, groups them by phase, orders each phase by weight, asks each plugin's gate whether to run, and runs the rest. Any enabled module contributes steps just by declaring a plugin - no Drush wiring of its own - which is what makes the mechanism reusable (and extractable to a standalone contrib module).

## Adding a deploy step

Create a plugin in any enabled module's `src/Plugin/PersistentDeploy/` namespace:

```php
namespace Drupal\my_module\Plugin\PersistentDeploy;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\persistent_deploy\Attribute\PersistentDeploy;
use Drupal\persistent_deploy\PersistentDeployBase;
use Drupal\persistent_deploy\PersistentDeployInterface;

#[PersistentDeploy(
  id: 'rebuild_search_index',
  label: new TranslatableMarkup('Rebuild the search index'),
  weight: 10,
  phase: PersistentDeployInterface::PHASE_POST,
)]
final class RebuildSearchIndex extends PersistentDeployBase {

  // Return NULL to run, or a human-readable reason to skip (logged verbatim).
  public function gate(): ?string {
    return $this->isProduction() ? 'production environment' : NULL;
  }

  public function run(): void {
    // Idempotent work - it runs on every deploy.
  }

}
```

- **`weight`** sets the run order within the phase (lower runs first).
- **`phase`** chooses when the step runs: `PHASE_PRE` (before the `deploy:hook` body) or `PHASE_POST` (after it, the default).
- **`gate()`** decides whether the step runs. Returning a *reason* instead of a bare boolean means every skip is explicit and explained in the deploy log. The inherited `environment()` / `isProduction()` helpers cover the common case.
- **`run()`** is the step. It must be idempotent; throw to abort the deploy.
- Inject services with `ContainerFactoryPluginInterface::create()`, like any Drupal plugin (see the bundled `RecordEnvironment` example).

A single module can declare as many steps as it needs - each is its own plugin with its own ID.

## Two deploy-time layers

- **Drupal-level "every deploy"** -> the persistent deploy plugins discovered by this module. They run wherever `drush deploy:hook` runs: CI, local, and production hosting after rollout.
- **Vortex tooling-level** -> the pre/post provision event scripts, for orchestration outside `drush deploy` (for example, work before the database is imported).
