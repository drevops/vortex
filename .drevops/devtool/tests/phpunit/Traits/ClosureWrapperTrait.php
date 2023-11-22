<?php

namespace DrevOps\DevTool\Tests\Traits;

use Opis\Closure\SerializableClosure;

/**
 * Trait ClosureWrapperTrait.
 *
 * Provides wrapper for closures that allows to use them as arguments in data
 * providers.
 *
 * The methods are deliberately named as short as possible to avoid long lines
 * in data providers.
 *
 * fnw() stands for "function wrap" and fnu() stands for "function unwrap".
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/2739
 */
trait ClosureWrapperTrait {

  /**
   * Wrap closure into serializable object.
   *
   * @param callable $closure
   *   The closure to wrap.
   *
   * @return \Opis\Closure\SerializableClosure
   *   The serializable closure.
   */
  public static function fnw(callable $closure): SerializableClosure {
    return new SerializableClosure($closure);
  }

  /**
   * Unwrap closure into serializable object.
   *
   * @param callable|null $callback
   *   The closure to unwrap.
   *
   * @return callable|null
   *   The unwrapped closure.
   */
  public static function fnu(callable|null $callback): mixed {
    return $callback && $callback instanceof SerializableClosure ? $callback->getClosure() : $callback;
  }

}
