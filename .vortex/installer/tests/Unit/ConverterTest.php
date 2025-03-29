<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Converter::class)]
class ConverterTest extends UnitTestBase {

  #[DataProvider('dataProviderAbbreviation')]
  public function testAbbreviation(string $value, int $length, array $word_delims, string $expected): void {
    $this->assertSame($expected, Converter::abbreviation($value, $length, $word_delims));
  }

  public static function dataProviderAbbreviation(): array {
    return [
      ['Hello World', 2, [' '], 'HW'],
      ['singleword', 2, ['_'], 'si'],
      ['singleword', 2, [' '], 'si'],
      ['multiple_words_here', 3, ['_'], 'mwh'],
      ['multiple_words_here', 3, [' '], 'mul'],
      ['Mixed-Case Words', 2, ['-'], 'MC'],
      ['Mixed-Case Words', 3, ['-'], 'MC'],
      ['Mixed-Case Words', 3, [' ', '-'], 'MCW'],
      [' spaced words ', 3, [' '], 'sw'],
      [' spaced words ', 3, ['_'], 'spa'],
      ['longword', 10, [' '], 'longword'],
      ['longword', 10, ['_'], 'longword'],
      ['abc def', 2, [' '], 'ad'],
      ['abc def', 2, ['_'], 'ab'],
      ['a_b_c', 3, [' '], 'a_b'],
      ['a_b_c', 3, ['_'], 'abc'],
    ];
  }

  #[DataProvider('dataProviderDomain')]
  public function testDomain(string $string, string $expected): void {
    $this->assertSame($expected, Converter::domain($string));
  }

  public static function dataProviderDomain(): array {
    return [
      ['https://example.com', 'example.com'],
      ['http://example.com', 'example.com'],
      ['www.example.com', 'example.com'],
      ['https://www.example.com', 'example.com'],
      ['http://www.example.com', 'example.com'],
      ['example.com/', 'example.com'],
      ['https://example.com/', 'example.com'],
      ['http://example.com/', 'example.com'],
      ['www.example.com/', 'example.com'],
      ['example.com/path', 'example.com/path'],
      ['https://www.example.com/path', 'example.com/path'],
      ['example com', 'example-com'],
      ['example_com', 'example-com'],
      ['www.example_com', 'example-com'],
      ['http://www.example_com', 'example-com'],
    ];
  }

  #[DataProvider('dataProviderPath')]
  public function testPath(string $string, string $expected): void {
    $this->assertSame($expected, Converter::path($string));
  }

  public static function dataProviderPath(): array {
    return [
      ['simple-path', 'simple-path'],
      [' leading-space', '-leading-space'],
      ['trailing-space ', 'trailing-space-'],
      [' /slashes/ ', '-/slashes/-'],
      [' multiple spaces here ', '-multiple-spaces-here-'],
      ['path/with/slashes', 'path/with/slashes'],
      ['  spaces  ', '--spaces--'],
      ['mixed Spaces_and_underscores', 'mixed-Spaces_and_underscores'],
    ];
  }

}
