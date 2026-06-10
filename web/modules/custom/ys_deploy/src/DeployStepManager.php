<?php

declare(strict_types=1);

namespace Drupal\ys_deploy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ys_deploy\Attribute\DeployStep;

/**
 * Plugin manager for deploy step plugins.
 *
 * Discovers DeployStep plugins from `Plugin/DeployStep/` in every enabled
 * module. Because discovery is keyed on the live enabled-module list, any
 * enabled module can contribute steps without registering its own Drush hook -
 * only ys_deploy needs to be wired into Drush.
 */
class DeployStepManager extends DefaultPluginManager {

  /**
   * Constructs a DeployStepManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DeployStep', $namespaces, $module_handler, DeployStepInterface::class, DeployStep::class);

    $this->setCacheBackend($cache_backend, 'ys_deploy_deploy_step_plugins', ['ys_deploy_deploy_step_plugins']);
    $this->alterInfo('ys_deploy_deploy_step');
  }

  /**
   * Returns all deploy step instances, ordered by ascending weight.
   *
   * @return \Drupal\ys_deploy\DeployStepInterface[]
   *   Deploy step plugin instances, keyed by plugin ID.
   */
  public function getSortedSteps(): array {
    $definitions = $this->getDefinitions();
    uasort($definitions, fn(array $a, array $b): int => ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0));

    $steps = [];
    foreach (array_keys($definitions) as $id) {
      $instance = $this->createInstance($id);
      if ($instance instanceof DeployStepInterface) {
        $steps[$id] = $instance;
      }
    }

    return $steps;
  }

}
