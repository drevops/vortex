<?php


/**
 * This class is not used anywhere in xautoload, but could be used by other
 * modules.
 */
class xautoload_FinderPlugin_CheckIncludePath implements xautoload_FinderPlugin_Interface {

  /**
   * {@inheritdoc}
   */
  function findFile($api, $path_fragment, $path_suffix) {
    $path = $path_fragment . $path_suffix;
    if ($api->suggestFile_checkIncludePath($path)) {
      return TRUE;
    }
  }
}
