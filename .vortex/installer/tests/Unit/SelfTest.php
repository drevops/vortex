<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(UnitTestCase::class)]
class SelfTest extends UnitTestCase {

  public function testEnvCleanup1SetVariables(): void {
    static::envSet('VORTEX_TEST_VAR_1', 'value1');
    static::envSet('VORTEX_TEST_VAR_2', 'value2');

    static::envSetMultiple([
      'VORTEX_TEST_VAR_3' => 'value3',
      'VORTEX_TEST_VAR_4' => 'value4',
    ]);

    $this->assertSame('value1', getenv('VORTEX_TEST_VAR_1'));
    $this->assertSame('value2', getenv('VORTEX_TEST_VAR_2'));
    $this->assertSame('value3', getenv('VORTEX_TEST_VAR_3'));
    $this->assertSame('value4', getenv('VORTEX_TEST_VAR_4'));
  }

  #[Depends('testEnvCleanup1SetVariables')]
  public function testEnvCleanup2VerifyCleanup(): void {
    // tearDown() of the previous test cleared its variables via envReset().
    $this->assertFalse(getenv('VORTEX_TEST_VAR_1'), 'VORTEX_TEST_VAR_1 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_2'), 'VORTEX_TEST_VAR_2 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_3'), 'VORTEX_TEST_VAR_3 should be cleaned up after previous test');
    $this->assertFalse(getenv('VORTEX_TEST_VAR_4'), 'VORTEX_TEST_VAR_4 should be cleaned up after previous test');
  }

}
