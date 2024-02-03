<?php

namespace DrevOps\Installer\Utils;

/**
 * Environment variables used in the scaffold.
 */
class Env {

  final const DB_DIR = 'DREVOPS_DB_DIR';

  final const DB_DOCKER_IMAGE = 'DREVOPS_DB_DOCKER_IMAGE';

  final const DB_DOWNLOAD_CURL_URL = 'DREVOPS_DB_DOWNLOAD_CURL_URL';

  final const DB_DOWNLOAD_SOURCE = 'DREVOPS_DB_DOWNLOAD_SOURCE';

  final const DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY = 'DREVOPS_DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY';

  final const DB_FILE = 'DREVOPS_DB_FILE';

  final const DEPLOY_TYPES = 'DREVOPS_DEPLOY_TYPES';

  final const DREVOPS_VERSION = 'DREVOPS_VERSION';

  final const DREVOPS_VERSION_URLENCODED = 'DREVOPS_VERSION_URLENCODED';

  final const DRUPAL_THEME = 'DREVOPS_DRUPAL_THEME';

  final const DRUPAL_VERSION = 'DREVOPS_DRUPAL_VERSION';

  final const PROJECT = 'DREVOPS_PROJECT';

  final const PROVISION_OVERRIDE_DB = 'DREVOPS_PROVISION_OVERRIDE_DB';

  final const PROVISION_USE_PROFILE = 'DREVOPS_PROVISION_USE_PROFILE';

  final const WEBROOT = 'DREVOPS_WEBROOT';

  final const INSTALLER_COMMIT = 'DREVOPS_INSTALLER_COMMIT';

  final const INSTALLER_DEBUG = 'DREVOPS_INSTALLER_DEBUG';

  final const INSTALLER_DEMO_MODE = 'DREVOPS_INSTALLER_DEMO_MODE';

  final const INSTALLER_DEMO_MODE_SKIP = 'DREVOPS_INSTALLER_DEMO_MODE_SKIP';

  final const INSTALLER_INSTALL_PROCEED = 'DREVOPS_INSTALLER_INSTALL_PROCEED';

  final const INSTALLER_LOCAL_REPO = 'DREVOPS_INSTALLER_LOCAL_REPO';

  final const INSTALLER_TMP_DIR = 'DREVOPS_INSTALLER_TMP_DIR';

  final const INSTALLER_DST_DIR = 'DREVOPS_INSTALLER_DST_DIR';

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
  public static function getConstants(): array {
    return ConstantsLoader::load(__CLASS__, 'DREVOPS_', FALSE);
  }

}
