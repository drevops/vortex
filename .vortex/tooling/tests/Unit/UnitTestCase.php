<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use DrevOps\VortexTooling\Tests\Traits\MockTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;

/**
 * Abstract base class for unit tests with helper methods.
 */
abstract class UnitTestCase extends UpstreamUnitTestCase {

  use MockTrait;
  use EnvTrait;

  protected function tearDown(): void {
    self::envReset();

    $this->mockTearDown();

    parent::tearDown();
  }

  protected function runScript(string $script, string $dir = 'src'): string {
    ob_start();

    // Change to src directory so __DIR__ works correctly in the script.
    $original_dir = getcwd();
    if ($original_dir === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Failed to get current working directory.');
      // @codeCoverageIgnoreEnd
    }

    $root = __DIR__ . '/../../src';
    if (!file_exists($root)) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Root directory not found: ' . $root);
      // @codeCoverageIgnoreEnd
    }

    chdir($root);
    try {
      require __DIR__ . '/../../' . $dir . '/' . $script;
    }
    finally {
      $output = ob_get_clean() ?: '';
      chdir($original_dir);
    }

    return $output;
  }

}
