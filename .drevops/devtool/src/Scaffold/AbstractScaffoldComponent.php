<?php

namespace DrevOps\DevTool\Scaffold;

use DrevOps\DevTool\Utils\Downloader;

/**
 * Class AbstractScaffoldComponent.
 *
 * Abstract scaffold component.
 *
 * @package DrevOps\DevTool\Scaffold
 */
abstract class AbstractScaffoldComponent {

  /**
   * Project root.
   */
  protected string $rootDir;

  /**
   * Stub dir path.
   */
  protected string $stubDir;

  /**
   * Downloader.
   */
  protected Downloader $downloader;

  /**
   * Array of resource files.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Weight of the component.
   *
   * The higher the weight, the later the component will be run.
   *
   * @var int
   *   The weight greater than 0. 0 means that the component will not be run
   *   automatically.
   */
  protected static $weight = 0;

  /**
   * AbstractScaffoldComponent constructor.
   *
   * @param string $root_dir
   *   Project root.
   * @param string $stub_dir
   *   Stub dir path.
   * @param \DrevOps\DevTool\Utils\Downloader $downloader
   *   Downloader.
   */
  public function __construct(string $root_dir, string $stub_dir, Downloader $downloader) {
    $this->rootDir = $root_dir;
    $this->stubDir = $stub_dir;
    $this->downloader = $downloader;
  }

  /**
   * Process component.
   */
  public function process(): void {
    // Fetch remote files.
    foreach ($this->resourceUrls() as $root_file => $remote_file) {
      $this->files[$root_file] = $this->downloader->download($remote_file, $this->stubDir . DIRECTORY_SEPARATOR . basename($root_file));
    }

    $this->processResources();
  }

  /**
   * Return an array of resource files.
   */
  abstract protected function resourceUrls(): array;

  /**
   * Process resources.
   */
  abstract protected function processResources();

}
