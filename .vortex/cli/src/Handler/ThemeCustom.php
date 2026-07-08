<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Derive\Derive;

/**
 * Reusable behaviour for the "theme_custom" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ThemeCustom implements FieldInterface {

  /**
   * Validate the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error message, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * Normalize the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('theme_custom', 'Custom theme machine name')
      ->description('We will use this name as a custom theme name.')
      ->required()
      ->when(new Condition('theme', eq: Theme::CUSTOM))
      ->derive(new Derive('{{machine_name}}', 'machine'))
      ->weight(330);
  }

}
