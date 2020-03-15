<?php

namespace Drevops\Tests;

/**
 * Class InstallerHelpersTest.
 */
class InstallerHelpersTest extends DrevopsTestCase {

  /**
   * @dataProvider dataProviderToHumanName
   */
  public function testToHumanName($value, $expected) {
    $actual = to_human_name($value);
    $this->assertEquals($expected, $actual);
  }

  public function dataProviderToHumanName() {
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
   */
  public function testToMachineName($value, $preserve, $expected) {
    $actual = to_machine_name($value, $preserve);
    $this->assertEquals($expected, $actual);
  }

  public function dataProviderToMachineName() {
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
   */
  public function testToCamelCase($value, $capitalise_first, $expected) {
    $actual = to_camel_case($value, $capitalise_first);
    $this->assertEquals($expected, $actual);
  }

  public function dataProviderToCamelCase() {
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
