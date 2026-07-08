<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "code_coverage_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CodeCoverageProvider extends AbstractFieldHandler implements OptionsInterface {

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
  public static function id(): string {
    return 'code_coverage_provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Code coverage provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Select;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'The code coverage provider.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 60;
  }

}
