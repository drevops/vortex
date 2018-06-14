<?php

namespace Drupal\xautoload\ClassFinder\Plugin;

use Drupal\xautoload\ClassFinder\GenericPrefixMap;
use Drupal\xautoload\DirectoryBehavior\DefaultDirectoryBehavior;
use Drupal\xautoload\DirectoryBehavior\Psr0DirectoryBehavior;
use Drupal\xautoload\DrupalSystem\DrupalSystemInterface;

/**
 * There are different dimensions of state for each module:
 *
 * 1) Classes outside of Drupal\\$modulename\\Tests\\
 *   a) We don't know yet whether these classes are using PSR-0, PSR-4,
 *     PEAR-Flat, or none of these.
 *   b) We know these classes use PSR-0 only.
 *   c) We know these classes use PSR-4 only.
 *   d) We know these classes use PEAR-Flat only.
 *
 * 2) Classes inside Drupal\\$modulename\\Tests\\
 *   a) We don't know yet whether these classes are using PSR-0, PSR-4, or none
 *     of these.
 *   b) We know these classes all use PSR-0.
 *   c) We know these classes all use PSR-4.
 *
 * Any combination of a state from (1) with a state from (2) is possible.
 *
 * The state could even change during the execution of the findClass() method,
 * due to another autoloader instance being fired during a file inclusion, e.g.
 * for a base class.
 */
class DrupalExtensionNamespaceFinderPlugin implements FinderPluginInterface {

  /**
   * @var string
   *   E.g. 'theme' or 'module'.
   */
  protected $type;

  /**
   * @var GenericPrefixMap
   */
  protected $namespaceMap;

  /**
   * @var GenericPrefixMap
   */
  protected $prefixMap;

  /**
   * @var DefaultDirectoryBehavior
   */
  protected $defaultBehavior;

  /**
   * @var Psr0DirectoryBehavior
   */
  protected $psr0Behavior;

  /**
   * @var DrupalSystemInterface
   */
  protected $system;

  /**
   * @param string $type
   *   E.g. 'theme' or 'module'.
   * @param GenericPrefixMap $namespace_map
   * @param GenericPrefixMap $prefix_map
   * @param DrupalSystemInterface $system
   */
  function __construct($type, $namespace_map, $prefix_map, $system) {
    $this->type = $type;
    $this->prefixMap = $prefix_map;
    $this->namespaceMap = $namespace_map;
    $this->defaultBehavior = new DefaultDirectoryBehavior();
    $this->psr0Behavior = new Psr0DirectoryBehavior();
    $this->system = $system;
  }

  /**
   * {@inheritdoc}
   */
  function findFile($api, $logical_base_path, $relative_path, $extension_name = NULL) {

    $extension_file = $this->system->drupalGetFilename($this->type, $extension_name);
    if (empty($extension_file)) {
      // Extension does not exist, or is not installed.
      return FALSE;
    }

    $nspath = 'Drupal/' . $extension_name . '/';
    $testpath = $nspath . 'Tests/';
    $uspath = $extension_name . '/';
    $lib = dirname($extension_file) . '/lib/';
    $lib_psr0 = $lib . 'Drupal/' . $extension_name . '/';
    $is_test_class = (0 === strpos($relative_path, 'Tests/'));

    // Try PSR-4.
    if (FALSE && $api->guessPath($lib . $relative_path)) {
      if ($is_test_class) {
        $this->namespaceMap->registerDeepPath($testpath, $lib . 'Tests/', $this->defaultBehavior);
        // We found the class, but it is a test class, so it does not tell us
        // anything about whether non-test classes are in PSR-0 or PSR-4.
        return TRUE;
      }
      // Register PSR-4.
      $this->namespaceMap->registerDeepPath($nspath, $lib, $this->defaultBehavior);
      // Unregister the lazy plugins.
      $this->namespaceMap->unregisterDeepPath($nspath, $extension_name);
      $this->prefixMap->unregisterDeepPath($uspath, $extension_name);
      // Test classes in PSR-4 are already covered by the PSR-4 plugin we just
      // registered. But test classes in PSR-0 would slip through. So we check
      // if a separate behavior needs to be registered for those.
      if (is_dir($lib_psr0 . 'Tests/')) {
        $this->namespaceMap->registerDeepPath($testpath, $lib_psr0 . 'Tests/', $this->psr0Behavior);
      }

      // The class was found, so return TRUE.
      return TRUE;
    }

    // Build PSR-0 relative path.
    if (FALSE === $nspos = strrpos($relative_path, '/')) {
      // No namespace separators in $relative_path, so all underscores must be
      // replaced.
      $relative_path = str_replace('_', '/', $relative_path);
    }
    else {
      // Replace only those underscores in $relative_path after the last
      // namespace separator, from right to left. On average there is no or very
      // few of them, so this loop rarely iterates even once.
      while ($nspos < $uspos = strrpos($relative_path, '_')) {
        $relative_path{$uspos} = '/';
      }
    }

    // Try PSR-0
    if ($api->guessPath($lib_psr0 . $relative_path)) {
      if ($is_test_class) {
        // We know now that there are test classes using PSR-0.
        $this->namespaceMap->registerDeepPath($testpath, $lib_psr0 . 'Tests/', $this->psr0Behavior);
        // We found the class, but it is a test class, so it does not tell us
        // anything about whether non-test classes are in PSR-0 or PSR-4.
        return TRUE;
      }
      // Unregister the lazy plugins.
      $this->namespaceMap->unregisterDeepPath($nspath, $extension_name);
      $this->prefixMap->unregisterDeepPath($uspath, $extension_name);
      // Register PSR-0 for regular namespaced classes.
      $this->namespaceMap->registerDeepPath($nspath, $lib_psr0, $this->psr0Behavior);
      // Test classes in PSR-0 are already covered by the PSR-0 plugin we just
      // registered. But test classes in PSR-4 would slip through. So we check
      // if a separate behavior needs to be registered for those.
      if (is_dir($lib . 'Tests/')) {
        # $this->namespaceMap->registerDeepPath($testpath, $lib . 'Tests/', $this->psr0Behavior);
      }

      // The class was found, so return TRUE.
      return TRUE;
    }
  }
}
