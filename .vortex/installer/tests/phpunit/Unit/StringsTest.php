<?php

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\Strings;

/**
 * Tests for the Strings class.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\Strings
 */
class StringsTest extends UnitTestBase {

  /**
   * @dataProvider dataProviderUtfPos
   * @covers ::utfPos
   */
  public function testUtfPos(string $input, ?int $expected) {
    $this->assertEquals($expected, Strings::utfPos($input));
  }

  public static function dataProviderUtfPos(): array {
    return [
      ['Hello', 1],
      ['Ångström', 0],
      ['⚙️', 0],
      ['⚙️ Text', 0],
      ["\x80Invalid UTF", 0],
      ['', 0],
    ];
  }

  /**
   * @dataProvider dataProviderStrlenPlain
   * @covers ::strlenPlain
   */
  public function testStrlenPlain(string $input, int $expected) {
    $this->assertEquals($expected, Strings::strlenPlain($input));
  }

  public static function dataProviderStrlenPlain(): array {
    return [
      ['Hello', 5],
      ["\e[31mRedText\e[0m", 7],
      ['NoEscapeCodes', 13],
      ['', 0],
    ];
  }
}
