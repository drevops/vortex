<?php

namespace Drupal\xautoload\FinderOperation;

use Drupal\xautoload\Adapter\ClassFinderAdapter;
use Drupal\xautoload\Discovery\ClassMapGenerator;

class HookXautoloadOperation implements FinderOperationInterface {

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {
    // Let other modules register stuff to the finder via hook_xautoload().
    $classmap_generator = new ClassMapGenerator();
    $adapter = new ClassFinderAdapter($finder, $classmap_generator);
    $api = new \xautoload_InjectedAPI_hookXautoload($adapter, '');
    foreach (module_implements('xautoload') as $module) {
      $api->setExtensionDir($dir = drupal_get_path('module', $module));
      $f = $module . '_xautoload';
      $f($api, $dir);
    }
  }
}