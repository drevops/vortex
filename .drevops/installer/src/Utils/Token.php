<?php

namespace DrevOps\Installer\Utils;

/**
 * Tokens used int the scaffold.
 */
class Token {

  const ACQUIA = 'ACQUIA';

  const COMMENTED_CODE = '##### ';

  const COMMENT_DOC = '#:';

  const COMMENT_INTERNAL = '#;';

  const COMMENT_INTERNAL_BEGIN = '#;<';

  const COMMENT_INTERNAL_END = '#;>';

  const DB_DOCKER_IMAGE = 'DB_DOCKER_IMAGE';

  const DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY = 'DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY';

  const DEMO = 'DEMO';

  const DEPLOYMENT = 'DEPLOYMENT';

  const DREVOPS_DEV = 'DREVOPS_DEV';

  const FTP = 'FTP';

  const LAGOON = 'LAGOON';

  const PROVISION_USE_PROFILE = 'PROVISION_USE_PROFILE';

  const RENOVATEBOT = 'RENOVATEBOT';

  /**
   * Get all constants defined in this class.
   */
  public static function getConstants():array {
    return ConstantsLoader::load(__CLASS__);
  }

}
