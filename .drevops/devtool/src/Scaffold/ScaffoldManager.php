<?php

namespace DrevOps\DevTool\Scaffold;

use DrevOps\DevTool\Utils\ClassLoader;
use DrevOps\DevTool\Utils\Downloader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ScaffoldManager.
 *
 * Manage scaffold components.
 *
 * @package DrevOps\DevTool\Scaffold
 */
class ScaffoldManager {

  /**
   * Stub dir path.
   */
  protected string $stubDir;

  /**
   * Filesystem.
   */
  protected Filesystem $fs;

  /**
   * ScaffoldManager constructor.
   *
   * @param string $root
   *   Project root.
   */
  public function __construct(/**
                               * Project root.
                               */
  protected string $root) {
    $this->fs = new Filesystem();
    $this->stubDir = sys_get_temp_dir() . '/temp_' . uniqid();
    $this->fs->mkdir($this->stubDir);
  }

  /**
   * Update root using scaffold.
   */
  public function update(): void {
    $component_classes = ClassLoader::load(__DIR__, AbstractScaffoldComponent::class);
    usort($component_classes, static function ($a, $b) : int {
        return $a::$weight <=> $b::$weight;
    });

    $downloader = new Downloader();
    foreach ($component_classes as $component_class) {
      $component = new $component_class($this->root, $this->stubDir, $downloader);
      $component->process();
    }

    if (!empty($component_classes)) {
      $this->updateRoot();
    }
  }

  /**
   * Update root directory.
   */
  protected function updateRoot() {
    $this->fs->mirror($this->stubDir, $this->root);
  }

}
