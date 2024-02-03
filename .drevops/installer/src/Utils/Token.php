<?php

namespace DrevOps\Installer\Utils;

/**
 * Tokens used int the scaffold.
 */
class Token {

  final const ACQUIA = 'ACQUIA';

  final const COMMENTED_CODE = '##### ';

  final const COMMENT_DOC = '#:';

  final const COMMENT_INTERNAL = '#;';

  final const COMMENT_INTERNAL_BEGIN = '#;<';

  final const COMMENT_INTERNAL_END = '#;>';

  final const DB_DOCKER_IMAGE = 'DB_DOCKER_IMAGE';

  final const DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY = 'DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY';

  final const DEMO = 'DEMO';

  final const DEPLOYMENT = 'DEPLOYMENT';

  final const DREVOPS_DEV = 'DREVOPS_DEV';

  final const FTP = 'FTP';

  final const LAGOON = 'LAGOON';

  final const PROVISION_USE_PROFILE = 'PROVISION_USE_PROFILE';

  final const RENOVATEBOT = 'RENOVATEBOT';

  /**
   * Get all constants defined in this class.
   */
  public static function getConstants(): array {
    return ConstantsLoader::load(__CLASS__);
  }

}
