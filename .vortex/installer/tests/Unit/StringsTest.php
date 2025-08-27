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

  #[DataProvider('dataProviderRemoveTrailingSpaces')]
  public function testRemoveTrailingSpaces(string $input, string $expected): void {
    $actual = Strings::removeTrailingSpaces($input);
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderRemoveTrailingSpaces(): array {
    return [
      'empty_string' => [
        '',
        '',
      ],
      'no_trailing_spaces' => [
        'hello world',
        'hello world',
      ],
      'single_line_with_trailing_spaces' => [
        'hello world   ',
        'hello world',
      ],
      'single_line_with_trailing_tabs' => [
        "hello world\t\t",
        'hello world',
      ],
      'single_line_with_mixed_trailing_whitespace' => [
        "hello world \t  \t",
        'hello world',
      ],
      'multiline_with_trailing_spaces' => [
        "line one   \nline two  \nline three",
        "line one\nline two\nline three",
      ],
      'multiline_with_trailing_tabs' => [
        "line one\t\t\nline two\t\nline three",
        "line one\nline two\nline three",
      ],
      'multiline_with_mixed_trailing_whitespace' => [
        "line one \t \nline two  \t\nline three \t\t ",
        "line one\nline two\nline three",
      ],
      'empty_lines_with_trailing_spaces' => [
        "line one\n   \nline three",
        "line one\n\nline three",
      ],
      'empty_lines_with_trailing_tabs' => [
        "line one\n\t\t\nline three",
        "line one\n\nline three",
      ],
      'only_trailing_whitespace_lines' => [
        "   \n\t\t\n \t ",
        "\n\n",
      ],
      'preserve_leading_whitespace' => [
        "  indented line  \n\tindented with tab\t",
        "  indented line\n\tindented with tab",
      ],
      'preserve_internal_whitespace' => [
        "hello  world   \ninternal   spaces\t",
        "hello  world\ninternal   spaces",
      ],
      'windows_line_endings' => [
        "line one  \r\nline two\t\r\nline three",
        "line one\r\nline two\r\nline three",
      ],
      'mixed_line_endings' => [
        "line one  \nline two\t\r\nline three ",
        "line one\nline two\r\nline three",
      ],
      'single_space_at_end' => [
        'hello world ',
        'hello world',
      ],
      'single_tab_at_end' => [
        "hello world\t",
        'hello world',
      ],
      'multiple_consecutive_trailing_spaces' => [
        'hello world     ',
        'hello world',
      ],
      'multiple_consecutive_trailing_tabs' => [
        "hello world\t\t\t\t",
        'hello world',
      ],
      'no_line_ending_with_trailing_spaces' => [
        'single line with spaces   ',
        'single line with spaces',
      ],
    ];
  }

  #[DataProvider('dataProviderWrapLines')]
  public function testWrapLines(string $input, string $prefix, string $suffix, string $eol, string $expected): void {
    $actual = Strings::wrapLines($input, $prefix, $suffix, $eol);
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderWrapLines(): array {
    return [
      'empty_string' => [
        '',
        '',
        '',
        PHP_EOL,
        '',
      ],
      'single_line_no_wrapping' => [
        'hello world',
        '',
        '',
        PHP_EOL,
        'hello world',
      ],
      'single_line_with_prefix' => [
        'hello world',
        '> ',
        '',
        PHP_EOL,
        '> hello world',
      ],
      'single_line_with_suffix' => [
        'hello world',
        '',
        ' <',
        PHP_EOL,
        'hello world <',
      ],
      'single_line_with_prefix_and_suffix' => [
        'hello world',
        '[ ',
        ' ]',
        PHP_EOL,
        '[ hello world ]',
      ],
      'multiline_with_prefix' => [
        "line one\nline two\nline three",
        '> ',
        '',
        "\n",
        "> line one\n> line two\n> line three",
      ],
      'multiline_with_suffix' => [
        "line one\nline two\nline three",
        '',
        ' <--',
        "\n",
        "line one <--\nline two <--\nline three <--",
      ],
      'multiline_with_prefix_and_suffix' => [
        "line one\nline two\nline three",
        '| ',
        ' |',
        "\n",
        "| line one |\n| line two |\n| line three |",
      ],
      'empty_lines_with_wrapping' => [
        "line one\n\nline three",
        '- ',
        '',
        "\n",
        "- line one\n- \n- line three",
      ],
      'custom_eol_character' => [
        "line one\r\nline two",
        '> ',
        ' <',
        "\r\n",
        "> line one <\r\n> line two <",
      ],
      'html_tag_wrapping' => [
        "First paragraph\nSecond paragraph",
        '<p>',
        '</p>',
        "\n",
        "<p>First paragraph</p>\n<p>Second paragraph</p>",
      ],
      'indentation_wrapping' => [
        "function test()\nreturn 'hello'",
        '    ',
        '',
        "\n",
        "    function test()\n    return 'hello'",
      ],
      'comment_wrapping' => [
        "This is a comment\nAnother line",
        '// ',
        '',
        "\n",
        "// This is a comment\n// Another line",
      ],
      'quote_wrapping' => [
        "First quote\nSecond quote",
        '"',
        '"',
        "\n",
        "\"First quote\"\n\"Second quote\"",
      ],
      'single_character_eol' => [
        "line1|line2|line3",
        '> ',
        ' <',
        "|",
        "> line1 <|> line2 <|> line3 <",
      ],
      'whitespace_only_lines' => [
        "   \n\t\n   ",
        '> ',
        ' <',
        "\n",
        ">     <\n> \t <\n>     <",
      ],
      'special_characters_in_prefix_suffix' => [
        "line one\nline two",
        '[INFO] ',
        ' ✓',
        "\n",
        "[INFO] line one ✓\n[INFO] line two ✓",
      ],
      'unicode_content_and_wrapping' => [
        "Hello 世界\nGoodbye 🌍",
        '🔹 ',
        ' ✨',
        "\n",
        "🔹 Hello 世界 ✨\n🔹 Goodbye 🌍 ✨",
      ],
      'complex_multiline_with_php_eol' => [
        "<?php\nfunction test() {\n  return 'hello';\n}",
        '  ',
        '',
        PHP_EOL,
        "  <?php" . PHP_EOL . "  function test() {" . PHP_EOL . "    return 'hello';" . PHP_EOL . "  }",
      ],
      'empty_eol_fallback_to_php_eol' => [
        "line one\nline two\nline three",
        '> ',
        ' <',
        '',
        "> line one <" . PHP_EOL . "> line two <" . PHP_EOL . "> line three <",
      ],
    ];
  }

}
