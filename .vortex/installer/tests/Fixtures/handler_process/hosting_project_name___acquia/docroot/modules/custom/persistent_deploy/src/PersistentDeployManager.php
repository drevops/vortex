<?php

declare(strict_types=1);

namespace Drupal\persistent_deploy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\persistent_deploy\Attribute\PersistentDeploy;

/**
 * Plugin manager for persistent deploy plugins.
 *
 * Discovers PersistentDeploy plugins from `Plugin/PersistentDeploy/` in every
 * enabled module. Because discovery is keyed on the live enabled-module list,
 * any enabled module can contribute steps without registering its own Drush
 * hook - only persistent_deploy needs to be wired into Drush.
 */
class PersistentDeployManager extends DefaultPluginManager {

  /**
   * Constructs a PersistentDeployManager object.
   *
   * @param \Traversable<string, string> $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PersistentDeploy', $namespaces, $module_handler, PersistentDeployInterface::class, PersistentDeploy::class);

    $this->setCacheBackend($cache_backend, 'persistent_deploy_plugins', ['persistent_deploy_plugins']);
    $this->alterInfo('persistent_deploy_info');
  }

  /**
   * Returns deploy step instances for a phase, ordered by ascending weight.
   *
   * @param string $phase
   *   The phase to filter by: PersistentDeployInterface::PHASE_PRE or
   *   ::PHASE_POST.
   *
   * @return \Drupal\persistent_deploy\PersistentDeployInterface[]
   *   Deploy step plugin instances for the phase, keyed by plugin ID.
   */
  public function getSortedSteps(string $phase): array {
    $definitions = array_filter($this->getDefinitions(), static fn(array $definition): bool => ($definition['phase'] ?? PersistentDeployInterface::PHASE_POST) === $phase);
    uasort($definitions, fn(array $a, array $b): int => ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0));

    $steps = [];
    foreach (array_keys($definitions) as $id) {
      $instance = $this->createInstance($id);
      if ($instance instanceof PersistentDeployInterface) {
        $steps[$id] = $instance;
      }
    }

    return $steps;
  }

}
