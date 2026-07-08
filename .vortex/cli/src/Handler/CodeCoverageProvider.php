<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "code_coverage_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CodeCoverageProvider extends AbstractHandler implements OptionsInterface, FieldInterface {

  const NONE = 'none';

  const CODECOV = 'codecov';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value === 'codecov') {
      File::removeTokenAsync('!CODE_COVERAGE_PROVIDER_CODECOV');
    }
    else {
      File::removeTokenAsync('CODE_COVERAGE_PROVIDER_CODECOV');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::CODECOV => 'Codecov',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->select('code_coverage_provider', 'Code coverage provider')
      ->description('The code coverage provider.')
      ->default(self::NONE)
      ->options(self::options())
      ->weight(60);
  }

}
