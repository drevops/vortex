<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use Laravel\SerializableClosure\SerializableClosure;

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
 *
 * @phpstan-ignore trait.unused
 */
trait ClosureWrapperTrait {

  /**
   * Wrap closure into serializable object.
   *
   * @param callable $closure
   *   The closure to wrap.
   *
   * @return \Laravel\SerializableClosure\SerializableClosure
   *   The serializable closure.
   */
  public static function fnw(callable $closure): SerializableClosure {
    // @phpstan-ignore-next-line
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
