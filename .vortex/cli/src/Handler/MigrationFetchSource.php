<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "migration_fetch_source" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class MigrationFetchSource extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $source = NULL;

    if (!empty($value)) {
      $source = is_string($value) ? $value : '';

      Env::writeValueDotenv('VORTEX_FETCH_DB2_SOURCE', $source, $context->directory . '/.env');

      // Lagoon identifies environments by branch name; the production branch
      // is `main`. The shared default (`prod`) is correct for Acquia only.
      if ($source === 'lagoon') {
        Env::writeValueDotenv('VORTEX_FETCH_DB2_ENVIRONMENT', 'main', $context->directory . '/.env');
      }
    }

    $types = ['url', 'ftp', 'acquia', 'lagoon', 'container_registry', 's3'];

    foreach ($types as $type) {
      $token = 'DB2_FETCH_SOURCE_' . strtoupper($type);
      if ($source === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }

    // Gates content required only for the hosting-connected fetch sources.
    if ($source !== 'acquia' && $source !== 'lagoon') {
      File::removeTokenAsync('DB2_FETCH_SOURCE_HOSTED');
    }
  }

}
