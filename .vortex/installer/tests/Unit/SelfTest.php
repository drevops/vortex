<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(UnitTestCase::class)]
class SelfTest extends UnitTestCase {

  public function testEnvCleanup1SetVariables(): void {
    // Set environment variables using envSet.
    static::envSet('VORTEX_TEST_VAR_1', 'value1');
    static::envSet('VORTEX_TEST_VAR_2', 'value2');

    // Set environment variables using envSetMultiple.
    static::envSetMultiple([
      'VORTEX_TEST_VAR_3' => 'value3',
      'VORTEX_TEST_VAR_4' => 'value4',
    ]);

    // Verify variables are set during the test.
    $this->assertSame('value1', getenv('VORTEX_TEST_VAR_1'));
    $this->assertSame('value2', getenv('VORTEX_TEST_VAR_2'));
    $this->assertSame('value3', getenv('VORTEX_TEST_VAR_3'));
    $this->assertSame('value4', getenv('VORTEX_TEST_VAR_4'));

    // Note: tearDown() will be called after this test, which should clean up
    // all environment variables via envReset().
  }

  #[Depends('testEnvCleanup1SetVariables')]
  public function testEnvCleanup2VerifyCleanup(): void {
    // Verify that environment variables from the previous test were cleaned up
    // by tearDown() calling envReset().
    $this->assertFalse(getenv('VORTEX_TEST_VAR_1'), 'VORTEX_TEST_VAR_1 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_2'), 'VORTEX_TEST_VAR_2 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_3'), 'VORTEX_TEST_VAR_3 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_4'), 'VORTEX_TEST_VAR_4 should be cleaned up after previous test');
  }

}
