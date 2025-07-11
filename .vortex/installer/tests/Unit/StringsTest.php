<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Strings;

/**
 * Tests for the Strings class.
 */
#[CoversClass(Strings::class)]
class StringsTest extends UnitTestCase {

  #[DataProvider('dataProviderIsAsciiStart')]
  public function testIsAsciiStart(string $input, ?int $expected): void {
    $this->assertEquals($expected, Strings::isAsciiStart($input));
  }

  public static function dataProviderIsAsciiStart(): array {
    return [
      ['Hello', 1],
      ['Ångström', 0],
      ['⚙️', 0],
      ['⚙️ Text', 0],
      ["\x80Invalid UTF", 0],
      ['', 0],
    ];
  }

  #[DataProvider('dataProviderStrlenPlain')]
  public function testStrlenPlain(string $input, int $expected): void {
    $this->assertEquals($expected, Strings::strlenPlain($input));
  }

  public static function dataProviderStrlenPlain(): array {
    return [
      ['Hello', 5],
      ["\e[31mRedText\e[0m", 7],
      ['NoEscapeCodes', 13],
      ['', 0],
      ['Vortex 🚀🚀🚀', 13],
    ];
  }

}
