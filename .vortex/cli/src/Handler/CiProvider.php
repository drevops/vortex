<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "ci_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CiProvider extends AbstractHandler implements OptionsInterface, FieldInterface {

  const NONE = 'none';

  const GITHUB_ACTIONS = 'gha';

  const CIRCLECI = 'circleci';

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

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::GITHUB_ACTIONS => 'GitHub Actions',
      self::CIRCLECI => 'CircleCI',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->select('ci_provider', 'Continuous Integration provider')
      ->description('The CI provider for the project.')
      ->default(self::GITHUB_ACTIONS)
      ->options(self::options())
      ->weight(90);
  }

}
