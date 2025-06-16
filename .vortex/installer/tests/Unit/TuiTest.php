<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Tui;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the Tui class.
 */
#[CoversClass(Tui::class)]
class TuiTest extends UnitTestCase {

  #[DataProvider('dataProviderAction')]
  public function testAction(
    \Closure|string $label,
    \Closure $action,
    \Closure|string|null $hint,
    \Closure|string|null $success,
    \Closure|string|null $failure,
    string|array $expected_output,
  ): void {
    $output = new BufferedOutput();

    Tui::init($output);
    Tui::action($label, $action, $hint, $success, $failure);

    $actual = $output->fetch();

    $expected_output = is_array($expected_output) ? $expected_output : [$expected_output];
    foreach ($expected_output as $expected) {
      $this->assertStringContainsString(Tui::normalizeText($expected), $actual);
    }
  }

  /**
   * Data provider for testAction.
   */
  public static function dataProviderAction(): array {
    return [

      'successful action with string label and no messages' => [
        'label' => 'Processing default messages',
        'action' => fn(): null => NULL,
        'hint' => NULL,
        'success' => NULL,
        'failure' => NULL,
        'expected_output' => '✅ OK',
      ],

      'successful action with string messages' => [
        'label' => 'Processing task',
        'action' => fn(): null => NULL,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => [
          '✅ Completed successfully',
          'This is a hint',
        ],
      ],

      'successful action with string label and TRUE value' => [
        'label' => 'Processing task',
        'action' => fn(): true => TRUE,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => '✅ Completed successfully',
      ],

      'successful action with closures' => [
        'label' => fn(): string => 'Dynamic label',
        'action' => fn(): string => 'Done',
        'hint' => fn(): string => 'Processing dynamically',
        'success' => fn($result): string => 'Success: ' . $result,
        'failure' => 'Failed',
        'expected_output' => [
          '✅ Success: Done',
          'Processing dynamically',
        ],
      ],

      'successful action returning array' => [
        'label' => 'Processing array',
        'action' => fn(): array => ['item1', 'item2'],
        'hint' => NULL,
        'success' => 'Processed items',
        'failure' => 'Processing failed',
        'expected_output' => [
          '✅ Processed items',
          'item1',
          'item2',
        ],
      ],

      'failed action with string' => [
        'label' => fn(): string => 'Dynamic label',
        'action' => fn(): false => FALSE,
        'hint' => fn(): string => 'Processing dynamically',
        'success' => fn($result): string => 'Success: ' . $result,
        'failure' => 'Failed',
        'expected_output' => 'Failed',
      ],

      'failed action with closure' => [
        'label' => fn(): string => 'Dynamic label',
        'action' => fn(): false => FALSE,
        'hint' => fn(): string => 'Processing dynamically',
        'success' => fn($result): string => 'Success: ' . $result,
        'failure' => fn(): string => 'Failed dynamically',
        'expected_output' => 'Failed dynamically',
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
        'expected' => <<<'EXPECTED'
                                     Hello
EXPECTED,
      ],

      'single line text with custom width' => [
        'text' => 'Test',
        'width' => 20,
        'border' => NULL,
        'expected' => <<<'EXPECTED'
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
        'expected' => <<<'EXPECTED'
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
        'expected' => <<<'EXPECTED'
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
        'expected' => <<<'EXPECTED'
       Line 1

       Line 3
EXPECTED,
      ],

      'single line text with border' => [
        'text' => 'Hello',
        'width' => 20,
        'border' => '=',
        'expected' => <<<'EXPECTED'
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
        'expected' => <<<'EXPECTED'
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
        'expected' => <<<'EXPECTED'
Exact
EXPECTED,
      ],

      'text wider than available width' => [
        'text' => 'Very long text',
        'width' => 20,
        'border' => NULL,
        'expected' => <<<'EXPECTED'
   Very long text
EXPECTED,
      ],

      'single character text' => [
        'text' => 'X',
        'width' => 10,
        'border' => NULL,
        'expected' => <<<'EXPECTED'
    X
EXPECTED,
      ],

      'empty text' => [
        'text' => '',
        'width' => 10,
        'border' => NULL,
        'expected' => <<<'EXPECTED'

EXPECTED,
      ],

      'whitespace only text' => [
        'text' => '   ',
        'width' => 15,
        'border' => NULL,
        'expected' => <<<'EXPECTED'
         
EXPECTED,
      ],

      'text with border using different character' => [
        'text' => 'Bordered',
        'width' => 16,
        'border' => '*',
        'expected' => <<<'EXPECTED'
**************

    Bordered

**************
EXPECTED,
      ],

    ];
  }

}
