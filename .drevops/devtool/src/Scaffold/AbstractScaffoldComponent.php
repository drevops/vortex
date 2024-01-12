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
   * @param string $rootDir
   *   Project root.
   * @param string $stubDir
   *   Stub dir path.
   * @param \DrevOps\DevTool\Utils\Downloader $downloader
   *   Downloader.
   */
  public function __construct(protected string $rootDir, protected string $stubDir, protected Downloader $downloader) {
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
