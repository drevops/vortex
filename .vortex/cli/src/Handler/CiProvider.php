<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "ci_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CiProvider extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $v = is_string($value) ? $value : '';
    $t = $context->directory;

    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($v) {
      case 'gha':
        $remove_circleci = TRUE;
        break;

      case 'circleci':
        $remove_gha = TRUE;
        break;

      default:
        $remove_circleci = TRUE;
        $remove_gha = TRUE;
    }

    if ($remove_gha) {
      File::remove($t . '/.github/workflows/build-test-deploy.yml');
      File::removeTokenAsync('CI_PROVIDER_GHA');
      File::removeTokenAsync('SETTINGS_PROVIDER_GHA');
    }

    if ($remove_circleci) {
      File::remove($t . '/.circleci');
      File::remove($t . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenAsync('CI_PROVIDER_CIRCLECI');
      File::removeTokenAsync('SETTINGS_PROVIDER_CIRCLECI');
    }

    if ($remove_gha && $remove_circleci) {
      File::remove($t . '/docs/ci.md');
      File::removeTokenAsync('CI_PROVIDER_ANY');
    }
    else {
      File::removeTokenAsync('!CI_PROVIDER_ANY');
    }
  }

}
