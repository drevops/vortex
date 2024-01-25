<?php

namespace DrevOps\Installer\Tests\Traits;

use Opis\Closure\SerializableClosure;

/**
 *
 *@see https://github.com/sebastianbergmann/phpunit/issues/2739
 */
trait ClosureWrapperTrait {

  /**
   * Wrap closure into serializable object.
   */
  public static function fnw(callable $closure): SerializableClosure {
    return new SerializableClosure($closure);
  }

  /**
   * Unwrap closure into serializable object.
   */
  public static function fnu(callable|null $callback): mixed {
    return $callback && $callback instanceof SerializableClosure ? $callback->getClosure() : $callback;
  }

}
