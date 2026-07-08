<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

/**
 * A handler declaring the selectable options for its question.
 *
 * @package DrevOps\VortexCli\Handler
 */
interface OptionsInterface {

  /**
   * The selectable options as a ready-to-use value => label map.
   *
   * @return array<string,string>
   *   The options, keyed by value.
   */
  public static function options(): array;

}
