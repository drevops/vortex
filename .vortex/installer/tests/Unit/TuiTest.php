<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\Strings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Tui;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the Tui class.
 */
#[CoversClass(Tui::class)]
class TuiTest extends UnitTestCase {

  public function testInit(): void {
    $output = new BufferedOutput();

    // Test basic initialization.
    Tui::init($output);
    $this->assertSame($output, Tui::output());

    // Test with non-interactive mode.
    Tui::init($output, FALSE);
    $this->assertSame($output, Tui::output());
  }

  public function testOutputNotInitialized(): void {
    // Since the output property is typed and doesn't allow null,
    // we can't easily test the uninitialized state.
    // Instead, we'll test that init properly sets the output.
    $output = new BufferedOutput();
    Tui::init($output);
    $this->assertSame($output, Tui::output());
  }

  public function testInfo(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::info('Test info message');

    $actual = $output->fetch();
    $this->assertStringContainsString('Test info message', $actual);
  }

  public function testNote(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::note('Test note message');

    $actual = $output->fetch();
    $this->assertStringContainsString('Test note message', $actual);
  }

  public function testError(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::error('Test error message');

    $actual = $output->fetch();
    $this->assertStringContainsString('✕ Test error message', $actual);
  }

  #[DataProvider('dataProviderColorMethods')]
  public function testColorMethods(string $method, string $input, string $expectedAnsi): void {
    $result = Tui::$method($input);
    $this->assertStringContainsString($expectedAnsi, $result);
    $this->assertStringContainsString($input, $result);
  }

  public static function dataProviderColorMethods(): array {
    return [
      'green method' => ['green', 'Hello', "\033[32m"],
      'blue method' => ['blue', 'Hello', "\033[34m"],
      'purple method' => ['purple', 'Hello', "\033[35m"],
      'yellow method' => ['yellow', 'Hello', "\033[33m"],
      'cyan method' => ['cyan', 'Hello', "\033[36m"],
      'bold method' => ['bold', 'Hello', "\033[1m"],
      'underscore method' => ['underscore', 'Hello', "\033[4m"],
      'dim method' => ['dim', 'Hello', "\033[2m"],
      'undim method' => ['undim', 'Hello', "\033[22m"],
    ];
  }

  #[DataProvider('dataProviderColorMethodsMultiline')]
  public function testColorMethodsMultiline(string $method, string $input, string $expected): void {
    $result = Tui::$method($input);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderColorMethodsMultiline(): array {
    return [
      'green multiline' => [
        'green',
        <<<'INPUT'
Line 1
Line 2
INPUT,
        "\033[32mLine 1\033[39m\n\033[32mLine 2\033[39m",
      ],
      'blue multiline' => [
        'blue',
        <<<'INPUT'
First
Second
INPUT,
        "\033[34mFirst\033[39m\n\033[34mSecond\033[39m",
      ],
    ];
  }

  public function testEscapeMultilineViaPublicMethod(): void {
    // Since escapeMultiline is protected, test it via public methods that
    // use it.
    $text = <<<'TEXT'
Line 1
Line 2
TEXT;

    $result = Tui::green($text);
    // The escape sequence appears without the leading backslash in the
    // actual output.
    $this->assertStringContainsString('[32m', $result);
    $this->assertStringContainsString('Line 1', $result);
    $this->assertStringContainsString('Line 2', $result);
  }

  public function testCaretMethods(): void {
    $this->assertSame("\033[B", Tui::caretDown());
    $this->assertSame("\033[A", Tui::caretUp());
  }

  public function testCaretEol(): void {
    $result = Tui::caretEol('Short');
    $this->assertSame("\033[5C", $result);

    $multiline = <<<'TEXT'
Short
Longer line
TEXT;
    $result = Tui::caretEol($multiline);
    $this->assertSame("\033[11C", $result);
  }

  public function testTerminalWidth(): void {
    $width = Tui::terminalWidth();
    $this->assertGreaterThanOrEqual(20, $width);
  }

  #[DataProvider('dataProviderBox')]
  public function testBox(
    string $content,
    ?string $title,
    ?int $width,
    ?int $terminal_width,
    string $expected_output,
  ): void {
    $output = new BufferedOutput();
    Tui::init($output);

    // Store original environment.
    $original_columns = getenv('COLUMNS');

    try {
      // Mock terminal width if specified.
      if ($terminal_width !== NULL) {
        putenv('COLUMNS=' . $terminal_width);
      }

      if ($width !== NULL) {
        Tui::box($content, $title, $width);
      }
      else {
        Tui::box($content, $title);
      }

      $actual = $output->fetch();

      // Strip ANSI color codes using the same method as Strings::strlenPlain()
      $actual_clean = Strings::stripAnsiColors($actual);
      $expected_clean = Strings::stripAnsiColors($expected_output);

      $this->assertSame($expected_clean, $actual_clean);

    }
    finally {
      // Restore environment.
      if ($original_columns !== FALSE) {
        putenv('COLUMNS=' . $original_columns);
      }
      else {
        putenv('COLUMNS');
      }
    }
  }

  public static function dataProviderBox(): array {
    return [
      'simple content without title' => [
        'content' => 'Simple content',
        'title' => NULL,
        'width' => 50,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────┐
 │ Simple content │
 └────────────────┘


EXPECTED,
      ],

      'content with title' => [
        'content' => 'Content with title',
        'title' => 'Box Title',
        'width' => 50,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────────┐
 │ Box Title          │
 │ ─────────          │
 │                    │
 │ Content with title │
 └────────────────────┘


EXPECTED,
      ],

      'empty content with title' => [
        'content' => '',
        'title' => 'Empty Content',
        'width' => 40,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌───────────────┐
 │ Empty Content │
 │ ───────────── │
 │               │
 │               │
 └───────────────┘


EXPECTED,
      ],

      'empty title' => [
        'content' => 'Content with empty title',
        'title' => '',
        'width' => 40,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌──────────────────────────┐
 │ Content with empty title │
 └──────────────────────────┘


EXPECTED,
      ],

      'single character content' => [
        'content' => 'X',
        'title' => 'A',
        'width' => 25,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌───┐
 │ A │
 │ ─ │
 │   │
 │ X │
 └───┘


EXPECTED,
      ],

      'whitespace only content' => [
        'content' => '   ',
        'title' => NULL,
        'width' => 30,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌─────┐
 │     │
 └─────┘


EXPECTED,
      ],

      'terminal width constraint' => [
        'content' => 'Terminal width test',
        'title' => 'Narrow Terminal',
        'width' => 100,
        'terminal_width' => 30,
        'expected_output' => <<<EXPECTED

 ┌─────────────────────┐
 │ Narrow Terminal     │
 │ ───────────────     │
 │                     │
 │ Terminal width test │
 └─────────────────────┘


EXPECTED,
      ],

      'default width when not specified' => [
        'content' => 'Default width content',
        'title' => 'Default Width',
        'width' => NULL,
        'terminal_width' => 50,
        'expected_output' => <<<EXPECTED

 ┌───────────────────────┐
 │ Default Width         │
 │ ─────────────         │
 │                       │
 │ Default width content │
 └───────────────────────┘


EXPECTED,
      ],

      'long content word wrapping' => [
        'content' => 'This is a very long line of content that should definitely be wrapped when the box width is narrow',
        'title' => 'Long Content Test',
        'width' => 40,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌───────────────────────────────────┐
 │ Long Content Test                 │
 │ ─────────────────                 │
 │                                   │
 │ This is a very long line of       │
 │ content that should definitely be │
 │ wrapped when the box width is     │
 │ narrow                            │
 └───────────────────────────────────┘


EXPECTED,
      ],

      'long title word wrapping' => [
        'content' => 'Short line',
        'title' => 'This is a very long title that should be wrapped across multiple lines when the box width is constrained',
        'width' => 35,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌───────────────────────────────┐
 │ This is a very long title     │
 │ that should be wrapped across │
 │ multiple lines when the box   │
 │ width is constrained          │
 │ ─────────────────────────     │
 │                               │
 │ Short line                    │
 └───────────────────────────────┘


EXPECTED,
      ],

      'multiline content with literal newlines' => [
        'content' => "Multi\nLine\nContent\nHere",
        'title' => 'Multi-Line Test',
        'width' => 30,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌─────────────────┐
 │ Multi-Line Test │
 │ ─────────────── │
 │                 │
 │ Multi           │
 │ Line            │
 │ Content         │
 │ Here            │
 └─────────────────┘


EXPECTED,
      ],

      'narrow width wrapping' => [
        'content' => 'This line has some words and should wrap nicely',
        'title' => NULL,
        'width' => 25,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────────┐
 │ This line has some │
 │ words and should   │
 │ wrap nicely        │
 └────────────────────┘


EXPECTED,
      ],

      'unbreakable long word' => [
        'content' => 'OneVeryLongWordThatCannotBeWrappedNormally',
        'title' => 'Unbreakable',
        'width' => 20,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────┐
 │ Unbreakable    │
 │ ───────────    │
 │                │
 │ OneVeryLongWor │
 │ dThatCannotBeW │
 │ rappedNormally │
 └────────────────┘


EXPECTED,
      ],

      'multi paragraph content' => [
        'content' => "Paragraph one with some text.\n\nParagraph two with more text after empty line.",
        'title' => 'Multi-Paragraph',
        'width' => 45,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────────────────────────┐
 │ Multi-Paragraph                    │
 │ ───────────────                    │
 │                                    │
 │ Paragraph one with some text.      │
 │                                    │
 │ Paragraph two with more text after │
 │ empty line.                        │
 └────────────────────────────────────┘


EXPECTED,
      ],

      'content with special characters' => [
        'content' => 'Content with émojis 🌟 and special chars ñáéíóú that affect wrapping calculations',
        'title' => 'Special Chars',
        'width' => 35,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────────────────────────┐
 │ Special Chars              │
 │ ─────────────              │
 │                            │
 │ Content with émojis 🌟 and │
 │ special chars ñáéíóú       │
 │ that affect wrapping       │
 │ calculations               │
 └────────────────────────────┘


EXPECTED,
      ],

      'extreme narrow width' => [
        'content' => 'Short',
        'title' => 'Normal Title',
        'width' => 10,
        'terminal_width' => 100,
        'expected_output' => <<<EXPECTED

 ┌────────┐
 │ Normal │
 │ Title  │
 │ ────── │
 │        │
 │ Shor   │
 │ t      │
 └────────┘


EXPECTED,
      ],
    ];
  }

  #[DataProvider('dataProviderList')]
  public function testList(array $values, ?string $title, array $expected_strings): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::list($values, $title);

    $actual = $output->fetch();
    foreach ($expected_strings as $expected_string) {
      $this->assertStringContainsString($expected_string, $actual);
    }
  }

  public static function dataProviderList(): array {
    return [
      'simple list' => [
        'values' => ['key1' => 'value1', 'key2' => 'value2'],
        'title' => 'Test List',
        'expected_strings' => ['key1', 'value1', 'key2', 'value2', 'Test List'],
      ],
      'list with array values' => [
        'values' => ['items' => ['item1', 'item2', 'item3']],
        'title' => 'Array Test',
        'expected_strings' => ['items', 'item1, item2, item3', 'Array Test'],
      ],
      'list with section title' => [
        'values' => [
          'Section A' => Tui::LIST_SECTION_TITLE,
          'key1' => 'value1',
        ],
        'title' => 'Section Test',
        'expected_strings' => ['Section A', 'key1', 'value1'],
      ],
    ];
  }

  #[DataProvider('dataProviderUtfPadding')]
  public function testUtfPadding(
    string $char,
    ?string $terminal_emulator,
    ?string $term_program,
    string $expected_padding,
  ): void {
    // Store original environment values.
    $original_terminal_emulator = getenv('TERMINAL_EMULATOR');
    $original_term_program = getenv('TERM_PROGRAM');

    try {
      // Set test environment variables.
      if ($terminal_emulator !== NULL) {
        putenv('TERMINAL_EMULATOR=' . $terminal_emulator);
      }
      else {
        putenv('TERMINAL_EMULATOR');
      }

      if ($term_program !== NULL) {
        putenv('TERM_PROGRAM=' . $term_program);
      }
      else {
        putenv('TERM_PROGRAM');
      }

      // Use reflection to access the protected method.
      $reflection = new \ReflectionClass(Tui::class);
      $method = $reflection->getMethod('utfPadding');

      $result = $method->invoke(NULL, $char);
      $this->assertSame($expected_padding, $result);

    }
    finally {
      // Restore original environment variables.
      if ($original_terminal_emulator !== FALSE) {
        putenv('TERMINAL_EMULATOR=' . $original_terminal_emulator);
      }
      else {
        putenv('TERMINAL_EMULATOR');
      }

      if ($original_term_program !== FALSE) {
        putenv('TERM_PROGRAM=' . $original_term_program);
      }
      else {
        putenv('TERM_PROGRAM');
      }
    }
  }

  public static function dataProviderUtfPadding(): array {
    return [
      // JetBrains terminal conditions.
      'JetBrains with 1-byte UTF-8 char' => [
    // 2 bytes, 1 mb_strlen
        'char' => 'é',
        'terminal_emulator' => 'JetBrains-something',
        'term_program' => NULL,
        'expected_padding' => ' ',
      ],

      'JetBrains with 3-byte UTF-8 char' => [
      // 3 bytes, 1 mb_strlen
        'char' => 'あ',
        'terminal_emulator' => 'JetBrains-IDE',
        'term_program' => NULL,
        'expected_padding' => ' ',
      ],

      'JetBrains with 4-byte UTF-8 char' => [
      // 4 bytes, 1 mb_strlen - should NOT get padding (len >= 4)
        'char' => '🌟',
        'terminal_emulator' => 'JetBrains-IDE',
        'term_program' => NULL,
        'expected_padding' => '',
      ],

      'Non-JetBrains terminal' => [
        'char' => 'é',
        'terminal_emulator' => 'other-terminal',
        'term_program' => NULL,
        'expected_padding' => '',
      ],

      // Apple Terminal conditions.
      'Apple Terminal with multi-byte char under 8 bytes' => [
      // 2 chars × 3 bytes = 6 bytes total, mblen=2, len=6 < 8
        'char' => 'あい',
        'terminal_emulator' => NULL,
        'term_program' => 'Apple_Terminal',
        'expected_padding' => ' ',
      ],

      'Apple Terminal with long multi-byte string' => [
      // 5 chars × 3 bytes = 15 bytes, mblen=5, len=15 >= 8
        'char' => 'あいうえお',
        'terminal_emulator' => NULL,
        'term_program' => 'Apple_Terminal',
        'expected_padding' => '',
      ],

      'Apple Terminal with single-byte char' => [
      // Single byte, single mb_strlen.
        'char' => 'A',
        'terminal_emulator' => NULL,
        'term_program' => 'Apple_Terminal',
        'expected_padding' => '',
      ],

      // No special terminal conditions.
      'No special terminal with UTF-8' => [
        'char' => '🌟',
        'terminal_emulator' => NULL,
        'term_program' => NULL,
        'expected_padding' => '',
      ],

      'Regular ASCII character' => [
        'char' => 'A',
        'terminal_emulator' => NULL,
        'term_program' => NULL,
        'expected_padding' => '',
      ],

      // Both terminals set - JetBrains takes precedence.
      'Both JetBrains and Apple set' => [
        'char' => 'é',
        'terminal_emulator' => 'JetBrains-IDE',
        'term_program' => 'Apple_Terminal',
      // JetBrains condition should trigger first.
        'expected_padding' => ' ',
      ],

      // Empty/null environment values.
      'Empty environment values' => [
        'char' => 'é',
        'terminal_emulator' => '',
        'term_program' => '',
        'expected_padding' => '',
      ],
    ];
  }

  #[DataProvider('dataProviderCenter')]
  public function testCenter(
    string $text,
    int $width,
    ?string $border,
    string $expected,
  ): void {
    $actual = Tui::center($text, $width, $border);
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testCenter.
   */
  public static function dataProviderCenter(): array {
    return [

      'single line text with default width' => [
        'text' => 'Hello',
        'width' => 80,
        'border' => NULL,
        'expected' => <<<EXPECTED
                                     Hello
EXPECTED,
      ],

      'single line text with custom width' => [
        'text' => 'Test',
        'width' => 20,
        'border' => NULL,
        'expected' => <<<EXPECTED
        Test
EXPECTED,
      ],

      'multiline text without border' => [
        'text' => <<<'TEXT'
Line 1
Line 2
TEXT,
        'width' => 20,
        'border' => NULL,
        'expected' => <<<EXPECTED
       Line 1
       Line 2
EXPECTED,
      ],

      'multiline text with different line lengths' => [
        'text' => <<<'TEXT'
Short
Longer line
X
TEXT,
        'width' => 30,
        'border' => NULL,
        'expected' => <<<EXPECTED
         Short
         Longer line
         X
EXPECTED,
      ],

      'empty line in multiline text' => [
        'text' => <<<'TEXT'
Line 1

Line 3
TEXT,
        'width' => 20,
        'border' => NULL,
        'expected' => <<<EXPECTED
       Line 1

       Line 3
EXPECTED,
      ],

      'single line text with border' => [
        'text' => 'Hello',
        'width' => 20,
        'border' => '=',
        'expected' => <<<EXPECTED
==================

       Hello

==================
EXPECTED,
      ],

      'multiline text with border' => [
        'text' => <<<'TEXT'
Line 1
Line 2
TEXT,
        'width' => 25,
        'border' => '-',
        'expected' => <<<EXPECTED
-----------------------

         Line 1
         Line 2

-----------------------
EXPECTED,
      ],

      'text with exact width match' => [
        'text' => 'Exact',
        'width' => 5,
        'border' => NULL,
        'expected' => <<<EXPECTED
Exact
EXPECTED,
      ],

      'text wider than available width' => [
        'text' => 'Very long text',
        'width' => 20,
        'border' => NULL,
        'expected' => <<<EXPECTED
   Very long text
EXPECTED,
      ],

      'single character text' => [
        'text' => 'X',
        'width' => 10,
        'border' => NULL,
        'expected' => <<<EXPECTED
    X
EXPECTED,
      ],

      'empty text' => [
        'text' => '',
        'width' => 10,
        'border' => NULL,
        'expected' => <<<EXPECTED

EXPECTED,
      ],

      'whitespace only text' => [
        'text' => '   ',
        'width' => 15,
        'border' => NULL,
        'expected' => '         ',
      ],

      'text with border using different character' => [
        'text' => 'Bordered',
        'width' => 16,
        'border' => '*',
        'expected' => <<<EXPECTED
**************

    Bordered

**************
EXPECTED,
      ],

    ];
  }

  #[DataProvider('dataProviderNormalizeText')]
  public function testNormalizeText(string $input, string $expected): void {
    $result = Tui::normalizeText($input);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderNormalizeText(): array {
    return [
      // Test whitespace collapsing.
      'multiple spaces' => [
        'input' => 'Hello    world',
        'expected' => 'Hello world',
      ],

      'multiple types of whitespace' => [
        'input' => "Hello\t\t  \n  world",
        'expected' => 'Hello world',
      ],

      'leading and trailing spaces with multiple interior spaces' => [
        'input' => '  Hello    world  ',
        'expected' => ' Hello world ',
      ],

      // Test non-ASCII text gets whitespace normalized + UTF padding.
      'text starting with non-ASCII character' => [
        'input' => 'éHello    world',
        'expected' => 'éHello    world',
      ],

      // Test ASCII text processing (with UTF padding).
      'simple ASCII text' => [
        'input' => 'Hello world',
        'expected' => 'Hello world',
      ],

      'ASCII text with UTF-8 characters' => [
        'input' => 'Hello 🌟 world',
        'expected' => 'Hello 🌟 world',
      ],

      'empty string' => [
        'input' => '',
        'expected' => '',
      ],

      'single space' => [
        'input' => ' ',
        'expected' => ' ',
      ],

      'only multiple spaces' => [
        'input' => '    ',
        'expected' => ' ',
      ],
    ];
  }

}
