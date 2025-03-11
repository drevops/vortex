<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Tests\Functional\FunctionalTestBase;
use DrevOps\Installer\Utils\File;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Unit test for the FunctionalTestBase test class.
 *
 * @coversDefaultClass \DrevOps\Installer\Tests\Functional\FunctionalTestBase
 */
class SelfTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Override the parent method as this is a unit test for a method of
    // the FunctionalTestBase test class.
  }

  /**
   * @dataProvider dataProviderAssertDirectoriesEqual
   * @covers ::assertDirectoriesEqual
   */
  public function testAssertDirectoriesEqual(bool $expected_exception): void {
    $base = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'directory1');
    $expected = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'directory2');

    if ($expected_exception) {
      $this->expectException(AssertionFailedError::class);
    }

    $this->assertDirectoriesEqual($base, $expected);

    if (!$expected_exception) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Data provider for testAssertDirectoriesEqual().
   *
   * @return array
   *   The data provider.
   */
  public static function dataProviderAssertDirectoriesEqual(): array {
    return [
      'files_equal' => [FALSE],
      'files_not_equal' => [TRUE],
    ];
  }

}
