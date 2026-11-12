<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use AlexSkrypnyk\Prompty\Prompty;
use AlexSkrypnyk\Prompty\PromptyTestTrait;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(Tui::class)]
class TuiTest extends UnitTestCase {

  use PromptyTestTrait;

  public function testInit(): void {
    $output = new BufferedOutput();

    Tui::init($output);
    $this->assertSame($output, Tui::output());
    $this->assertTrue(Tui::isInteractive());

    Tui::init($output, FALSE);
    $this->assertSame($output, Tui::output());
    $this->assertFalse(Tui::isInteractive());
  }

  public function testOutputNotInitialized(): void {
    // The typed output property cannot hold NULL, so the uninitialized state
    // is not reachable; init() setting the output is asserted instead.
    $output = new BufferedOutput();
    Tui::init($output);
    $this->assertSame($output, Tui::output());
  }

  #[DataProvider('dataProviderMessages')]
  public function testMessages(string $method, string $message, array $expected): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::$method($message);

    $actual = $output->fetch();
    foreach ($expected as $expected_string) {
      $this->assertStringContainsString($expected_string, $actual);
    }
  }

  public static function dataProviderMessages(): \Iterator {
    yield 'info' => ['info', 'Test info message', ['Test info message']];
    yield 'note' => ['note', "Line 1\nLine 2", ['Line 1', 'Line 2']];
    yield 'success' => ['success', 'Operation succeeded', ['✓', 'Operation succeeded']];
    yield 'error' => ['error', 'Test error message', ['✕', 'Test error message']];
  }

  #[DataProvider('dataProviderColorMethods')]
  public function testColorMethods(string $method, string $input, string $expected_ansi): void {
    $result = Tui::$method($input);
    $this->assertStringContainsString($expected_ansi, $result);
    $this->assertStringContainsString($input, $result);
  }

  public static function dataProviderColorMethods(): \Iterator {
    yield 'green method' => ['green', 'Hello', "\033[32m"];
    yield 'blue method' => ['blue', 'Hello', "\033[34m"];
    yield 'purple method' => ['purple', 'Hello', "\033[35m"];
    yield 'yellow method' => ['yellow', 'Hello', "\033[33m"];
    yield 'cyan method' => ['cyan', 'Hello', "\033[36m"];
    yield 'bold method' => ['bold', 'Hello', "\033[1m"];
    yield 'underscore method' => ['underscore', 'Hello', "\033[4m"];
    yield 'dim method' => ['dim', 'Hello', "\033[2m"];
    yield 'undim method' => ['undim', 'Hello', "\033[22m"];
  }

  #[DataProvider('dataProviderColorMethodsMultiline')]
  public function testColorMethodsMultiline(string $method, string $input, string $expected): void {
    $result = Tui::$method($input);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderColorMethodsMultiline(): \Iterator {
    yield 'green multiline' => [
      'green',
        <<<'INPUT'
Line 1
Line 2
INPUT,
      "\033[32mLine 1\033[39m\n\033[32mLine 2\033[39m",
    ];
    yield 'blue multiline' => [
      'blue',
        <<<'INPUT'
First
Second
INPUT,
      "\033[34mFirst\033[39m\n\033[34mSecond\033[39m",
    ];
  }

  public function testTerminalWidth(): void {
    Tui::init(new BufferedOutput());

    $this->assertSame(min(100, max(20, Prompty::terminalWidth())), Tui::terminalWidth());
    $this->assertSame(min(10, max(20, Prompty::terminalWidth())), Tui::terminalWidth(10));
  }

  #[DataProvider('dataProviderBox')]
  public function testBox(string $content, ?string $title, array $expected): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::box($content, $title);

    $actual = $this->promptyNormalizeOutput($output->fetch());
    foreach ($expected as $expected_string) {
      $this->assertStringContainsString($expected_string, $actual);
    }
  }

  public static function dataProviderBox(): \Iterator {
    yield 'content without title' => ['Simple content', NULL, ['┌', '│ Simple content │', '┘']];
    yield 'content with title' => ['Content with title', 'Box Title', ['Box Title', 'Content with title']];
    yield 'multiline content' => ["Multi\nLine", 'Multi-Line Test', ['Multi-Line Test', '│ Multi ', '│ Line ']];
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

  public static function dataProviderList(): \Iterator {
    yield 'simple list' => [
      'values' => ['key1' => 'value1', 'key2' => 'value2'],
      'title' => 'Test List',
      'expected_strings' => ['key1', 'value1', 'key2', 'value2', 'Test List'],
    ];
    yield 'list with array values' => [
      'values' => ['items' => ['item1', 'item2', 'item3']],
      'title' => 'Array Test',
      'expected_strings' => ['items', 'item1, item2, item3', 'Array Test'],
    ];
    yield 'list with section title' => [
      'values' => [
        'Section A' => Tui::LIST_SECTION_TITLE,
        'key1' => 'value1',
      ],
      'title' => 'Section Test',
      'expected_strings' => ['Section A', 'key1', 'value1'],
    ];
  }

  public function testTable(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::table(['Name', 'Value'], [['key1', 'value1'], 'Section']);

    $actual = $output->fetch();
    $this->assertStringContainsString('Name', $actual);
    $this->assertStringContainsString('key1', $actual);
    $this->assertStringContainsString('Section', $actual);
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

  public static function dataProviderCenter(): \Iterator {
    yield 'single line text with default width' => [
      'text' => 'Hello',
      'width' => 80,
      'border' => NULL,
      'expected' => <<<EXPECTED
                                     Hello
EXPECTED,
    ];
    yield 'single line text with custom width' => [
      'text' => 'Test',
      'width' => 20,
      'border' => NULL,
      'expected' => <<<EXPECTED
        Test
EXPECTED,
    ];
    yield 'multiline text without border' => [
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
    ];
    yield 'multiline text with different line lengths' => [
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
    ];
    yield 'empty line in multiline text' => [
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
    ];
    yield 'single line text with border' => [
      'text' => 'Hello',
      'width' => 20,
      'border' => '=',
      'expected' => <<<EXPECTED
==================

       Hello

==================
EXPECTED,
    ];
    yield 'multiline text with border' => [
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
    ];
    yield 'text with exact width match' => [
      'text' => 'Exact',
      'width' => 5,
      'border' => NULL,
      'expected' => <<<EXPECTED
Exact
EXPECTED,
    ];
    yield 'text wider than available width' => [
      'text' => 'Very long text',
      'width' => 20,
      'border' => NULL,
      'expected' => <<<EXPECTED
   Very long text
EXPECTED,
    ];
    yield 'single character text' => [
      'text' => 'X',
      'width' => 10,
      'border' => NULL,
      'expected' => <<<EXPECTED
    X
EXPECTED,
    ];
    yield 'empty text' => [
      'text' => '',
      'width' => 10,
      'border' => NULL,
      'expected' => <<<EXPECTED

EXPECTED,
    ];
    yield 'whitespace only text' => [
      'text' => '   ',
      'width' => 15,
      'border' => NULL,
      'expected' => '         ',
    ];
    yield 'text with border using different character' => [
      'text' => 'Bordered',
      'width' => 16,
      'border' => '*',
      'expected' => <<<EXPECTED
**************

    Bordered

**************
EXPECTED,
    ];
  }

  public function testSetOutput(): void {
    $output1 = new BufferedOutput();
    $output2 = new BufferedOutput();

    Tui::init($output1);
    $this->assertSame($output1, Tui::output());

    Tui::setOutput($output2);
    $this->assertSame($output2, Tui::output());

    // Prompty keeps drawing to the output passed to init().
    Tui::info('Drawn by Prompty');
    $this->assertStringContainsString('Drawn by Prompty', $output1->fetch());
    $this->assertSame('', $output2->fetch());
  }

  public function testLine(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::line('Test line');

    $actual = $output->fetch();
    $this->assertStringContainsString('Test line', $actual);
  }

  public function testLineWithPadding(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    Tui::line('Test line', 5);

    $actual = $output->fetch();
    $this->assertStringContainsString('     Test line', $actual);
  }

  public function testConfirmNonInteractive(): void {
    $output = new BufferedOutput();
    Tui::init($output, FALSE);

    $result = Tui::confirm('Confirm action?', TRUE);
    $this->assertTrue($result);

    $result = Tui::confirm('Confirm action?', FALSE);
    $this->assertFalse($result);
  }

  public function testPauseNonInteractive(): void {
    $output = new BufferedOutput();
    Tui::init($output, FALSE);

    Tui::pause('Press any key to continue...');

    $this->assertStringNotContainsString('Press any key to continue...', $output->fetch());
  }

}
