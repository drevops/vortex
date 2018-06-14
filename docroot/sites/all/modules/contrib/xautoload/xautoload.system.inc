<?php

use Drupal\xautoload\Discovery\WildcardFileFinder;
use Drupal\xautoload\FinderOperation\HookXautoloadOperation;

/**
 * Implements hook_modules_enabled()
 *
 * @param string[] $modules
 */
function xautoload_modules_enabled($modules) {
  xautoload()->extensionRegistrationService->registerExtensionsByName($modules);
  xautoload()->cacheManager->renewCachePrefix();

  // Trigger invocation of hook_xautoload() to catch any hooks.
  $operation = new HookXautoloadOperation();
  xautoload()->proxyFinder->onFinderInit($operation);
}

/**
 * Implements hook_modules_installed()
 */
function xautoload_modules_installed($modules) {
  xautoload()->extensionRegistrationService->registerExtensionsByName($modules);
  xautoload()->cacheManager->renewCachePrefix();
}

/**
 * Implements hook_registry_files_alter()
 *
 * Support wildcard syntax in the files[] setting in your module's info file.
 * See https://drupal.org/node/1976198
 *
 * @param array[] &$files
 *   List of files specified in files[] array in module info files.
 *   Format:
 *
 *     $files['modules/field/field.attach.inc'] = array(
 *       'module' => 'field',
 *       'weight' => 0,
 *     );
 *     // Wildcard syntax.
 *     $files['sites/all/modules/foo/inc/**'] = array(
 *       'module' => 'foo',
 *       'weight' => 0,
 *     );
 *
 *   This function will remove the entry for foo/inc/**, and instead add all the
 *   individual class files found in the foo/inc/ folder.
 *
 * @param stdClass[] $modules
 *   Array keys are numeric.
 *   Array values are objects each representing a module.
 *   This parameter will be ignored.
 */
function xautoload_registry_files_alter(&$files, $modules) {
  $file_finder = new WildcardFileFinder();
  $file_finder->addDrupalPaths($files);
  $files = $file_finder->getDrupalFiles();
}
