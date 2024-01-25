<?php

namespace Drevops\Installer\Tests\Unit\Utils;

use Drevops\Installer\Tests\Traits\EnvTrait;
use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Env;

/**
 * @coversDefaultClass \Drevops\Installer\Utils\Env
 */
class EnvTest extends UnitTestBase {

  use EnvTrait;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Cleanup any environment variables set during tests.
    $this->envReset();

    parent::tearDown();
  }

  /**
   * @covers ::get
   * @dataProvider dataProviderTestGet
   */
  public function testGet(string $name, string $value, bool $isset, ?string $default, mixed $expected): void {
    if ($isset) {
      self::envSet($name, $value);
    }
    $this->assertEquals($expected, Env::get($name, $default));
  }

  public static function dataProviderTestGet(): array {
    return [
      ['name1', 'val1', FALSE, NULL, NULL],
      ['name1', 'val1', FALSE, 'default', 'default'],
      ['name1', 'val1', TRUE, 'default', 'val1'],
      ['name1', 'val1', TRUE, NULL, 'val1'],
    ];
  }

  /**
   * @covers ::getConstants
   */
  public function testGetConstants(): void {
    $constants = Env::getConstants();

    $this->assertIsArray($constants);
    $this->assertContains(Env::DB_DIR, $constants);
  }

}
