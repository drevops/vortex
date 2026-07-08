<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;

/**
 * Reusable behaviour for the "profile_custom" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ProfileCustom implements FieldInterface {

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
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.';
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
    return $p->text('profile_custom', 'Custom profile machine name')
      ->description('The machine name of your custom profile.')
      ->required()
      ->when(new Condition('profile', eq: Profile::CUSTOM))
      ->weight(260);
  }

}
