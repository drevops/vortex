<?php

namespace DrevOps\Installer\Utils;

/**
 * Environment variables used in the scaffold.
 */
class Env {

  const DB_DIR = 'DREVOPS_DB_DIR';

  const DB_DOCKER_IMAGE = 'DREVOPS_DB_DOCKER_IMAGE';

  const DB_DOWNLOAD_CURL_URL = 'DREVOPS_DB_DOWNLOAD_CURL_URL';

  const DB_DOWNLOAD_SOURCE = 'DREVOPS_DB_DOWNLOAD_SOURCE';

  const DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY = 'DREVOPS_DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY';

  const DB_FILE = 'DREVOPS_DB_FILE';

  const DEPLOY_TYPES = 'DREVOPS_DEPLOY_TYPES';

  const DREVOPS_VERSION = 'DREVOPS_VERSION';

  const DREVOPS_VERSION_URLENCODED = 'DREVOPS_VERSION_URLENCODED';

  const DRUPAL_THEME = 'DREVOPS_DRUPAL_THEME';

  const DRUPAL_VERSION = 'DREVOPS_DRUPAL_VERSION';

  const PROJECT = 'DREVOPS_PROJECT';

  const PROVISION_OVERRIDE_DB = 'DREVOPS_PROVISION_OVERRIDE_DB';

  const PROVISION_USE_PROFILE = 'DREVOPS_PROVISION_USE_PROFILE';

  const WEBROOT = 'DREVOPS_WEBROOT';

  const INSTALLER_COMMIT = 'DREVOPS_INSTALLER_COMMIT';

  const INSTALLER_DEBUG = 'DREVOPS_INSTALLER_DEBUG';

  const INSTALLER_DEMO_MODE = 'DREVOPS_INSTALLER_DEMO_MODE';

  const INSTALLER_DEMO_MODE_SKIP = 'DREVOPS_INSTALLER_DEMO_MODE_SKIP';

  const INSTALLER_INSTALL_PROCEED = 'DREVOPS_INSTALLER_INSTALL_PROCEED';

  const INSTALLER_LOCAL_REPO = 'DREVOPS_INSTALLER_LOCAL_REPO';

  const INSTALLER_TMP_DIR = 'DREVOPS_INSTALLER_TMP_DIR';

  const INSTALLER_DST_DIR = 'DREVOPS_INSTALLER_DST_DIR';

  /**
   * Reliable wrapper to work with environment values.
   */
  public static function get($name, $default = NULL) {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] == '') {
      return $default;
    }

    return $vars[$name];
  }

  /**
   * Get all constants defined in this class.
   */
  public static function getConstants():array {
    return ConstantsLoader::load(__CLASS__, 'DREVOPS_', FALSE);
  }

}
