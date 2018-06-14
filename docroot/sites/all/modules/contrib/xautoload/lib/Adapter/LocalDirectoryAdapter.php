<?php

namespace Drupal\xautoload\Adapter;

use Drupal\xautoload\Discovery\ComposerDir;
use Drupal\xautoload\Discovery\ComposerJson;

/**
 * An instance of this class is passed around to implementations of
 * hook_xautoload(). It acts as a wrapper around the ClassFinder, to register
 * stuff.
 */
class LocalDirectoryAdapter implements ClassFinderAdapterInterface {

  /**
   * @var string
   */
  protected $localDirectory;

  /**
   * @var ClassFinderAdapter
   */
  protected $master;

  /**
   * @param ClassFinderAdapter $adapter
   *   The class finder object.
   * @param string $localDirectory ;
   */
  function __construct($adapter, $localDirectory) {
    // parent::__construct($adapter->finder, $adapter->getClassmapGenerator());
    $this->master = $adapter;
    $this->localDirectory = $localDirectory;
  }

  /**
   * @return ClassFinderAdapter
   */
  function absolute() {
    return $this->master;
  }

  //                                                                   Discovery
  // ---------------------------------------------------------------------------

  /**
   * @param string[] $paths
   *   File paths or wildcard paths for class discovery.
   * @param bool $relative
   */
  function addClassmapSources($paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->addClassmapSources($paths);
  }

  //                                                              Composer tools
  // ---------------------------------------------------------------------------

  /**
   * Scan a composer.json file provided by a Composer package.
   *
   * @param string $file
   * @param bool $relative
   *
   * @throws \Exception
   */
  function composerJson($file, $relative = TRUE) {
    $relative && $file = $this->localDirectory . $file;
    $json = ComposerJson::createFromFile($file);
    $json->writeToAdapter($this->master);
  }

  /**
   * Scan a directory containing Composer-generated autoload files.
   *
   * @param string $dir
   *   Directory to look for Composer-generated files. Typically this is the
   *   ../vendor/composer dir.
   * @param bool $relative
   */
  function composerDir($dir, $relative = TRUE) {
    $relative && $dir = $this->localDirectory . $dir;
    $dir = ComposerDir::create($dir);
    $dir->writeToAdapter($this->master);
  }

  //                                                      multiple PSR-0 / PSR-4
  // ---------------------------------------------------------------------------

  /**
   * Add multiple PSR-0 namespaces
   *
   * @param array $prefixes
   * @param bool $relative
   */
  function addMultiplePsr0(array $prefixes, $relative = TRUE) {
    $relative && $this->prependMultiple($prefixes);
    $this->master->addMultiplePsr4($prefixes);
  }

  /**
   * @param array $map
   * @param bool $relative
   */
  function addMultiplePsr4(array $map, $relative = TRUE) {
    $relative && $this->prependMultiple($map);
    $this->master->addMultiplePsr4($map);
  }

  //                                                        Composer ClassLoader
  // ---------------------------------------------------------------------------

  /**
   * @param array $classMap
   * @param bool $relative
   */
  function addClassMap(array $classMap, $relative = TRUE) {
    $relative && $this->prependToPaths($classMap);
    $this->master->addClassMap($classMap);
  }

  /**
   * @param string $prefix
   * @param string|\string[] $paths
   * @param bool $relative
   */
  function add($prefix, $paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->add($prefix, $paths);
  }

  /**
   * @param string $prefix
   * @param string|\string[] $paths
   * @param bool $relative
   */
  function addPsr0($prefix, $paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->add($prefix, $paths);
  }

  /**
   * @param string $prefix
   * @param string|\string[] $paths
   * @param bool $relative
   */
  function addPsr4($prefix, $paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->addPsr4($prefix, $paths);
  }

  //                                                      More convenience stuff
  // ---------------------------------------------------------------------------

  /**
   * @param string $prefix
   * @param string|\string[] $paths
   * @param bool $relative
   */
  function addNamespacePsr0($prefix, $paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->addNamespacePsr0($prefix, $paths);
  }

  /**
   * @param $prefix
   * @param $paths
   * @param bool $relative
   */
  function addPear($prefix, $paths, $relative = TRUE) {
    $relative && $this->prependToPaths($paths);
    $this->master->addPear($prefix, $paths);
  }

  //                                                      Relative path handling
  // ---------------------------------------------------------------------------

  /**
   * @param array $map
   */
  protected function prependMultiple(array &$map) {
    foreach ($map as &$paths) {
      $paths = (array) $paths;
      foreach ($paths as &$path) {
        $path = $this->localDirectory . $path;
      }
    }
  }

  /**
   * @param string|string[] &$paths
   */
  protected function prependToPaths(&$paths) {
    if (!is_array($paths)) {
      $paths = $this->localDirectory . $paths;
    }
    else {
      foreach ($paths as &$path) {
        $path = $this->localDirectory . $path;
      }
    }
  }
}
