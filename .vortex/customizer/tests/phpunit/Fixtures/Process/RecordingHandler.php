<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Fixtures\Process;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;

/**
 * A handler that records the value it processes, in call order.
 */
class RecordingHandler extends AbstractHandler {

  /**
   * The processed values, in call order.
   *
   * @var list<mixed>
   */
  public static array $log = [];

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    self::$log[] = $value;
  }

}
