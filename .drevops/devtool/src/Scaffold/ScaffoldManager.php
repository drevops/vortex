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
   * Project root.
   *
   * @var string
   */
  protected string $root;

  /**
   * Stub dir path.
   *
   * @var string
   */
  protected string $stubDir;

  /**
   * Filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected Filesystem $fs;

  /**
   * ScaffoldManager constructor.
   *
   * @param string $root
   *   Project root.
   */
  public function __construct(string $root) {
    $this->fs = new Filesystem();
    $this->stubDir = sys_get_temp_dir() . '/temp_' . uniqid();
    $this->fs->mkdir($this->stubDir);
    $this->root = $root;
  }

  /**
   * Update root using scaffold.
   */
  public function update(): void {
    $component_classes = ClassLoader::load(__DIR__, 'DrevOps\\DevTool\\Scaffold\\AbstractScaffoldComponent');
    usort($component_classes, function ($a, $b): int {
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
