<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\DiscoverInterface;

/**
 * Base question handler with neutral field metadata.
 *
 * A concrete handler declares id(), label() and type(), overrides the
 * metadata it needs, and adds process() side effects and reusable static
 * validate()/transform() where the question has behaviour.
 *
 * @package DrevOps\VortexCli\Handler
 */
abstract class AbstractFieldHandler extends AbstractHandler implements FieldInterface {

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function required(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function when(): ?ConditionInterface {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function derive(): ?Derive {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function discover(): DiscoverInterface|\Closure|null {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 0;
  }

}
