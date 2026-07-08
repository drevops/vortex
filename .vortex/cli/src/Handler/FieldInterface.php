<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\DiscoverInterface;

/**
 * A handler declaring its question as pure data.
 *
 * Handlers describe what is asked - never how it is rendered: the form's
 * adapter is the only place converting this metadata into form elements.
 *
 * @package DrevOps\VortexCli\Handler
 */
interface FieldInterface {

  /**
   * The question id.
   *
   * @return string
   *   The id.
   */
  public static function id(): string;

  /**
   * The human-readable label.
   *
   * @return string
   *   The label.
   */
  public static function label(): string;

  /**
   * The question kind.
   *
   * @return \DrevOps\Tui\Config\FieldType
   *   The kind.
   */
  public static function type(): FieldType;

  /**
   * The help text.
   *
   * @return string
   *   The help text (empty for none).
   */
  public static function description(): string;

  /**
   * The default value.
   *
   * @return mixed
   *   The value, a `fn (Context): mixed` closure computing it from the run
   *   context, or NULL for the kind's own default.
   */
  public static function default(): mixed;

  /**
   * Whether an answer is required.
   *
   * @return bool
   *   TRUE when required.
   */
  public static function required(): bool;

  /**
   * The conditional-visibility rule.
   *
   * @return \DrevOps\Tui\Condition\ConditionInterface|null
   *   The condition, or NULL when always visible.
   */
  public static function when(): ?ConditionInterface;

  /**
   * The derive rule.
   *
   * @return \DrevOps\Tui\Derive\Derive|null
   *   The rule, or NULL when not derived.
   */
  public static function derive(): ?Derive;

  /**
   * The discovery rule.
   *
   * @return \DrevOps\Tui\Discovery\DiscoverInterface|\Closure|null
   *   The rule, a `fn (Context): mixed` detector, or NULL for none.
   */
  public static function discover(): DiscoverInterface|\Closure|null;

  /**
   * The processing weight; lower runs earlier.
   *
   * @return int
   *   The weight.
   */
  public static function weight(): int;

}
