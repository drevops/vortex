<?php

namespace Drevops\Installer\Tests\Unit\Utils;

use DrevOps\Installer\Command\Installer;
use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Strings;

/**
 * Class InstallerHelpersTest.
 *
 * InstallerHelpersTest fixture class.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\Strings
 */
class StringsTest extends UnitTestBase {

  /**
   * @covers    ::toHumanName
   * @dataProvider dataProviderToHumanName
   */
  public function testToHumanName(string $value, mixed $expected): void {
    $actual = Strings::toHumanName($value);
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderToHumanName(): array {
    return [
      ['', ''],
      [' ', ''],
      [' word ', 'word'],
      ['word other', 'word other'],
      ['word  other', 'word other'],
      ['word   other', 'word other'],
      ['word-other', 'word other'],
      ['word_other', 'word other'],
      ['word_-other', 'word other'],
      ['word_ - other', 'word other'],
      [' _word_ - other - ', 'word other'],
      [' _word_ - other - third', 'word other third'],
      [' _%word_$ -# other -@ third!,', 'word other third'],
    ];
  }

  /**
   * @covers      ::toMachineName
   * @dataProvider dataProviderToMachineName
   */
  public function testToMachineName(string $value, array $preserve, mixed $expected): void {
    $actual = Strings::toMachineName($value, $preserve);
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderToMachineName(): array {
    return [
      ['', [], ''],
      [' ', [], ' '],
      [' word ', [], 'word'],
      ['word ', [], 'word'],
      ['word other', [], 'word_other'],
      ['word  other', [], 'word__other'],
      ['word   other', [], 'word___other'],
      ['word-other', [], 'word_other'],
      ['word_other', [], 'word_other'],
      ['word_-other', [], 'word__other'],
      ['word_ - other', [], 'word____other'],
      [' _word_ - other - ', [], ' _word_ - other - '],
      [' _word_ - other - third', [], ' _word_ - other - third'],
      [' _%word_$ -# Other -@ Third!,', [], ' _%word_$ -# Other -@ Third!,'],
      ['word_$ -# Other -@ Third!,', [], 'word______other____third__'],
      ['word_$ -# Other -@ Third!, ', [], 'word______other____third__'],
      ['Word_$ -# Other -@ Third!,', [], 'word______other____third__'],
      ['Word_$ -# Other -@ Third!, ', [], 'word______other____third__'],

      ['', ['-'], ''],
      [' ', ['-'], ' '],
      [' word ', ['-'], 'word'],
      ['word other', ['-'], 'word_other'],
      ['word  other', ['-'], 'word__other'],
      ['word   other', ['-'], 'word___other'],
      ['word-other', ['-'], 'word-other'],
      ['word_other', ['-'], 'word_other'],
      ['word_-other', ['-'], 'word_-other'],
      ['word_ - other', ['-'], 'word__-_other'],

      [' _word_ - other - ', ['-'], ' _word_ - other - '],
      [' _word_ - other - third', ['-'], ' _word_ - other - third'],
      [' _%word_$ -# Other -@ Third!,', ['-'], ' _%word_$ -# Other -@ Third!,'],

      ['word_ - other - ', ['-'], 'word__-_other_-'],
      ['word_ - other - third', ['-'], 'word__-_other_-_third'],
      ['word_$ -# Other -@ Third!,', ['-'], 'word___-__other_-__third__'],
      ['Word_$ -# Other -@ Third!,', ['-'], 'word___-__other_-__third__'],
    ];
  }

  /**
   * @covers    ::toUrl
   * @dataProvider dataProviderToUrl
   */
  public function testToUrl(string $input, mixed $expected): void {
    $this->assertEquals($expected, Strings::toUrl($input));
  }

  public static function dataProviderToUrl(): array {
    return [
      ['', ''], // empty string
      ['this is a string', 'this-is-a-string'], // spaces to dashes
      ['this_is_a_string', 'this-is-a-string'], // underscores to dashes
      ['this_is a_string', 'this-is-a-string'], // mix of spaces and underscores
      ['thisIsAString', 'thisIsAString'], // no spaces or underscores
    ];
  }

  /**
   * @covers ::listToString
   * @dataProvider dataProviderListToString
   */
  public function testListToString(mixed $input, bool $multiline, mixed $expected): void {
    $this->assertEquals($expected, Strings::listToString($input, $multiline));
  }

  public static function dataProviderListToString(): array {
    return [
      // Test arrays with multiline = false (default)
      [['a', 'b', 'c'], FALSE, 'a, b, c'],
      [['a'], FALSE, 'a'],
      [['a', 'b', 'c', 'd'], FALSE, 'a, b, c, d'],

      // Test arrays with multiline = true
      [['a', 'b', 'c'], TRUE, "a\nb\nc"],
      [['a'], TRUE, 'a'],
      [['a', 'b', 'c', 'd'], TRUE, "a\nb\nc\nd"],

      // Test strings (should remain unchanged)
      ['a, b, c', FALSE, 'a, b, c'],
      ['a', TRUE, 'a'],
    ];
  }

  /**
   * @covers ::toAbbreviation
   * @dataProvider dataProviderToAbbreviation
   */
  public function testToAbbreviation(string $input, int $length, string $word_delim, mixed $expected): void {
    $this->assertEquals($expected, Strings::toAbbreviation($input, $length, $word_delim));
  }

  public static function dataProviderToAbbreviation(): array {
    return [
      // Test abbreviation creation
      ['hello world', 2, '_', 'hw'],
      ['hello', 2, '_', 'he'],
      ['hello', 4, '_', 'hell'],

      // Test different delimiters
      ['hello-world', 2, '-', 'hw'],
      ['hello world', 2, '-', 'he'],

      // Test different max lengths
      ['hello world', 3, '_', 'hw'],
      ['hello', 1, '_', 'h'],
    ];
  }

  /**
   * @covers ::isRegex
   * @dataProvider dataProviderIsRegex
   */
  public function testIsRegex(string $input, mixed $expected): void {
    $result = Strings::isRegex($input);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderIsRegex(): array {
    return [
      ['/te/st/', FALSE],
      // Positive cases
      ['/test/i', TRUE],
      ['|test|', TRUE],
      ['{test}', TRUE],
      ['(test)', TRUE],
      ['<test>', TRUE],

      // Negative cases
      ['/test', FALSE],
      ['test', FALSE],
      ['/test/iu', TRUE],  // Even though u flag might be deprecated, we still consider it valid
      ['/te/st/', FALSE],  // Delimiters inside the pattern
      ['<test', FALSE],    // Not matching delimiter

      // Test for invalid delimiters
      ['/test/', TRUE],   // Valid: "/" is a common delimiter
      ['*test*', FALSE],  // Invalid: "*" is in the invalid delimiter list
      ['?test?', FALSE],  // Invalid: "?" is in the invalid delimiter list
      ['AtestA', FALSE],  // Invalid: "A" is alphanumeric
      [' test ', FALSE],  // Invalid: space is in the invalid delimiter list
      ['\\test\\', FALSE], // Invalid: "\\" is in the invalid delimiter list

      ['/test/test/', FALSE], // Unpaired delimiter inside the pattern
      ['/test\/test/', TRUE], // Paired delimiter inside the pattern because it's escaped
    ];
  }

}
