<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(UnitTestCase::class)]
class SelfTest extends UnitTestCase {

  const AMBIENT_VAR = 'VORTEX_TEST_AMBIENT_VAR';

  const LEAKED_VAR = 'VORTEX_TEST_LEAKED_VAR';

  /**
   * @var string|false
   */
  protected static $ambientOriginal;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    static::$ambientOriginal = getenv(self::AMBIENT_VAR);
    putenv(self::AMBIENT_VAR . '=ambient');
  }

  public static function tearDownAfterClass(): void {
    if (static::$ambientOriginal === FALSE) {
      putenv(self::AMBIENT_VAR);
    }
    else {
      putenv(self::AMBIENT_VAR . '=' . static::$ambientOriginal);
    }

    parent::tearDownAfterClass();
  }

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

  public function testEnvRestore1WriteRawValues(): void {
    // envSet() is bypassed here to reproduce what production code does when it
    // writes the environment directly.
    putenv(self::LEAKED_VAR . '=leaked');
    putenv(self::AMBIENT_VAR . '=changed');
    $_ENV[self::LEAKED_VAR] = 'leaked';
    $_SERVER[self::LEAKED_VAR] = 'leaked';

    $this->assertSame('leaked', getenv(self::LEAKED_VAR));
    $this->assertSame('changed', getenv(self::AMBIENT_VAR));
  }

  #[Depends('testEnvRestore1WriteRawValues')]
  public function testEnvRestore2VerifyRestored(): void {
    $this->assertFalse(getenv(self::LEAKED_VAR), 'A variable added by the previous test should not outlive it');
    $this->assertSame('ambient', getenv(self::AMBIENT_VAR), 'A variable changed by the previous test should be back to its original value');
    $this->assertArrayNotHasKey(self::LEAKED_VAR, $_ENV);
    $this->assertArrayNotHasKey(self::LEAKED_VAR, $_SERVER);
  }

}
