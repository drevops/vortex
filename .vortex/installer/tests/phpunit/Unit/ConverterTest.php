<?php

declare(strict_types=1);

namespace Drevops\Installer\Tests\Unit;

use DrevOps\Installer\Converter;

/**
 * Class InstallerHelpersTest.
 *
 * @coversDefaultClass \DrevOps\Installer\Converter
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class ConverterTest extends UnitTestBase {

  /**
   * @dataProvider dataProviderToHumanName
   * @covers ::toHumanName
   */
  public function testToHumanName(string $value, mixed $expected): void {
    $this->assertEquals($expected, Converter::toHumanName($value));
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
   * @dataProvider dataProviderToMachineName
   * @covers ::toMachineName
   */
  public function testToMachineName(string $value, array $preserve, mixed $expected): void {
    $this->assertEquals($expected, Converter::toMachineName($value, $preserve));
  }

  public static function dataProviderToMachineName(): array {
    return [
      ['', [], ''],
      [' ', [], '_'],
      [' word ', [], '_word_'],
      ['word other', [], 'word_other'],
      ['word  other', [], 'word__other'],
      ['word   other', [], 'word___other'],
      ['word-other', [], 'word_other'],
      ['word_other', [], 'word_other'],
      ['word_-other', [], 'word__other'],
      ['word_ - other', [], 'word____other'],
      [' _word_ - other - ', [], '__word____other___'],
      [' _word_ - other - third', [], '__word____other___third'],
      [' _%word_$ -# Other -@ Third!,', [], '___word______other____third__'],

      ['', ['-'], ''],
      [' ', ['-'], '_'],
      [' word ', ['-'], '_word_'],
      ['word other', ['-'], 'word_other'],
      ['word  other', ['-'], 'word__other'],
      ['word   other', ['-'], 'word___other'],
      ['word-other', ['-'], 'word-other'],
      ['word_other', ['-'], 'word_other'],
      ['word_-other', ['-'], 'word_-other'],
      ['word_ - other', ['-'], 'word__-_other'],
      [' _word_ - other - ', ['-'], '__word__-_other_-_'],
      [' _word_ - other - third', ['-'], '__word__-_other_-_third'],
      [' _%word_$ -# Other -@ Third!,', ['-'], '___word___-__other_-__third__'],
    ];
  }

  /**
   * @dataProvider dataProviderToCamelCase
   * @covers ::toCamelCase
   */
  public function testToCamelCase(string $value, bool $capitalise_first, mixed $expected): void {
    $this->assertEquals($expected, Converter::toCamelCase($value, $capitalise_first));
  }

  public static function dataProviderToCamelCase(): array {
    return [
      ['', FALSE, ''],
      [' ', FALSE, ''],
      [' word ', FALSE, 'word'],
      [' word ', TRUE, 'Word'],
      ['word other', FALSE, 'wordOther'],
      ['word other', TRUE, 'WordOther'],
      ['word  other', FALSE, 'wordOther'],
      ['word  other', TRUE, 'WordOther'],
      ['word-  other', FALSE, 'wordOther'],
      ['word-  other', TRUE, 'WordOther'],
      ['%word- * other', FALSE, 'wordOther'],
      ['%word- * other', TRUE, 'WordOther'],
      [' _%word_$ -# Other -@ Third!,', FALSE, 'wordOtherThird'],
      [' _%word_$ -# Other -@ Third!,', TRUE, 'WordOtherThird'],
    ];
  }

}
