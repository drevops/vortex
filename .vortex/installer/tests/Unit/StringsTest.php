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

  #[DataProvider('dataProviderCollapsePhpBlockCommentsEmptyLines')]
  public function testCollapsePhpBlockCommentsEmptyLines(string $input, string $expected): void {
    $actual = Strings::collapsePhpBlockCommentsEmptyLines($input);
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderCollapsePhpBlockCommentsEmptyLines(): array {
    return [
      'empty_string' => [
        '',
        '',
      ],
      'no_docblock_comments' => [
        <<<'PHP'
        <?php
        function test() {
          return 'hello';
        }
        PHP,
        <<<'PHP'
        <?php
        function test() {
          return 'hello';
        }
        PHP,
      ],
      'single_line_docblock' => [
        '/** Single line comment */',
        '/** Single line comment */',
      ],
      'docblock_no_empty_lines' => [
        <<<'PHP'
        /**
         * Description here.
         * @param string $param
         * @return void
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         * @param string $param
         * @return void
         */
        PHP,
      ],
      'docblock_with_two_consecutive_empty_lines' => [
        <<<'PHP'
        /**
         * Description here.
         *
         *
         * @param string $param
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         *
         * @param string $param
         */
        PHP,
      ],
      'docblock_with_multiple_consecutive_empty_lines' => [
        <<<'PHP'
        /**
         * Description here.
         *
         *
         *
         *
         * @param string $param
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         *
         * @param string $param
         */
        PHP,
      ],
      'multiple_docblocks_with_empty_lines' => [
        <<<'PHP'
        /**
         * First docblock.
         *
         *
         * @param string $param
         */
        function first() {}

        /**
         * Second docblock.
         *
         *
         *
         * @return void
         */
        function second() {}
        PHP,
        <<<'PHP'
        /**
         * First docblock.
         *
         * @param string $param
         */
        function first() {}

        /**
         * Second docblock.
         *
         * @return void
         */
        function second() {}
        PHP,
      ],
      'docblock_with_whitespace_in_empty_lines' => [
        <<<'PHP'
        /**
         * Description here.
         *
         *
         * @param string $param
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         *
         * @param string $param
         */
        PHP,
      ],
      'mixed_docblocks_and_regular_comments' => [
        <<<'PHP'
        /**
         * Docblock comment.
         *
         *
         * @param string $param
         */
        function test() {
          /* Regular comment
             with multiple lines
             should not be affected */
          return 'test';
        }
        PHP,
        <<<'PHP'
        /**
         * Docblock comment.
         *
         * @param string $param
         */
        function test() {
          /* Regular comment
             with multiple lines
             should not be affected */
          return 'test';
        }
        PHP,
      ],
      'docblock_at_beginning_and_end' => [
        <<<'PHP'
        /**
         * File docblock.
         *
         *
         * @file
         */

        function test() {}

        /**
         * End docblock.
         *
         *
         * @return void
         */
        PHP,
        <<<'PHP'
        /**
         * File docblock.
         *
         * @file
         */

        function test() {}

        /**
         * End docblock.
         *
         * @return void
         */
        PHP,
      ],
      'empty_lines_at_start_of_docblock' => [
        <<<'PHP'
        /**
         *
         *
         * Description here.
         * @param string $param
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         * @param string $param
         */
        PHP,
      ],
      'empty_lines_at_end_of_docblock' => [
        <<<'PHP'
        /**
         * Description here.
         * @param string $param
         *
         *
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         * @param string $param
         */
        PHP,
      ],
      'entirely_empty_docblock' => [
        <<<'PHP'
        /**
         *
         *
         *
         */
        function test() {}
        PHP,
        <<<'PHP'
        function test() {}
        PHP,
      ],
      'docblock_with_only_whitespace' => [
        <<<'PHP'
        /**
         *
         *
         *
         */
        class Test {}
        PHP,
        <<<'PHP'
        class Test {}
        PHP,
      ],
      'mixed_empty_and_content_docblocks' => [
        <<<'PHP'
        /**
         *
         *
         */

        /**
         * Real content here.
         * @param string $param
         */
        function test() {}
        PHP,
        <<<'PHP'

        /**
         * Real content here.
         * @param string $param
         */
        function test() {}
        PHP,
      ],
      'docblock_with_leading_and_trailing_empty_lines' => [
        <<<'PHP'
        /**
         *
         *
         * Description here.
         *
         *
         * @param string $param
         *
         *
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         *
         * @param string $param
         */
        PHP,
      ],
      'docblock_with_custom_indentation' => [
        <<<'PHP'
        /**
         * Description here.
         *
         *
         *
         * @param string $param
         */
        PHP,
        <<<'PHP'
        /**
         * Description here.
         *
         * @param string $param
         */
        PHP,
      ],
      'docblock_with_deeper_indentation' => [
        <<<'PHP'
            /**
             * Description here.
             *
             *
             *
             * @param string $param
             */
        PHP,
        <<<'PHP'
            /**
             * Description here.
             *
             * @param string $param
             */
        PHP,
      ],
      'docblock_with_tabs_indentation' => [
        "/**\n\t * Description here.\n\t *\n\t *\n\t *\n\t * @param string \$param\n\t */",
        "/**\n\t * Description here.\n\t *\n\t * @param string \$param\n\t */",
      ],
      'json_string_with_docblock_pattern_should_not_be_modified' => [
        <<<'JSON'
        {
          "lint-css": "stylelint --allow-empty-input \"web/modules/custom/**/*.css\"",
          "description": "Some /** comment */ in JSON"
        }
        JSON,
        <<<'JSON'
        {
          "lint-css": "stylelint --allow-empty-input \"web/modules/custom/**/*.css\"",
          "description": "Some /** comment */ in JSON"
        }
        JSON,
      ],
      'inline_docblock_pattern_should_not_be_modified' => [
        'const foo = "/** some comment */"; // Not a real docblock',
        'const foo = "/** some comment */"; // Not a real docblock',
      ],
    ];
  }

  #[DataProvider('dataProviderIsRegex')]
  public function testIsRegex(string $value, mixed $expected): void {
    $this->assertEquals($expected, Strings::isRegex($value));
  }

  public static function dataProviderIsRegex(): array {
    return [
      ['', FALSE],

      // Valid regular expressions.
      ["/^[a-z]$/", TRUE],
      ["#[a-z]*#i", TRUE],

      // Invalid regular expressions (wrong delimiters or syntax).
      ["{\\d+}", FALSE],
      ["(\\d+)", FALSE],
      ["<[A-Z]{3,6}>", FALSE],
      ["^[a-z]$", FALSE],
      ["/[a-z", FALSE],
      ["[a-z]+/", FALSE],
      ["{[a-z]*", FALSE],
      ["(a-z]", FALSE],

      // Edge cases.
      // Valid, but '*' as delimiter would be invalid.
      ["/a*/", TRUE],
      // Empty string.
      ["", FALSE],
      // Just delimiters, no pattern.
      ["//", FALSE],

      ['web/', FALSE],
      ['web\/', FALSE],
      [': web', FALSE],
      ['=web', FALSE],
      ['!web', FALSE],
      ['/web', FALSE],
    ];
  }

}
