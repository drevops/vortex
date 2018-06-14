<?php

namespace Drupal\xautoload\Tests\FinderOperation;

use Drupal\xautoload\Adapter\ClassFinderAdapter;
use Drupal\xautoload\Discovery\ClassMapGenerator;
use Drupal\xautoload\FinderOperation\FinderOperationInterface;

class MainPhase implements FinderOperationInterface {

  /**
   * @var \stdClass[]
   */
  protected $extensions = array();

  /**
   * @param \stdClass[] $extensions
   */
  function __construct(array $extensions) {
    $this->extensions = $extensions;
  }

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {
    // Let other modules register stuff to the finder via hook_xautoload().
    $classmap_generator = new ClassMapGenerator();
    $adapter = new ClassFinderAdapter($finder, $classmap_generator);
    $api = new \xautoload_InjectedAPI_hookXautoload($adapter, '');
    foreach ($this->extensions as $info) {
      // The simplest module dir is enough for this simulation.
      $api->setExtensionDir('test://modules/' . $info->name);
      $f = $info->name . '_xautoload';
      $f($api);
    }
  }
}