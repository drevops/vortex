<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\Tui;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the Tui class.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\Tui
 */
class TuiTest extends UnitTestBase {

  /**
   * @dataProvider dataProviderAction
   * @covers ::action
   */
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
      $this->assertStringContainsString($expected, $actual);
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
        'expected_output' => '✅  OK',
      ],

      'successful action with string messages' => [
        'label' => 'Processing task',
        'action' => fn(): null => NULL,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => [
          '✅  Completed successfully',
          'This is a hint',
        ],
      ],

      'successful action with string label and TRUE value' => [
        'label' => 'Processing task',
        'action' => fn(): true => TRUE,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => '✅  Completed successfully',
      ],

      'successful action with closures' => [
        'label' => fn(): string => 'Dynamic label',
        'action' => fn(): string => 'Done',
        'hint' => fn(): string => 'Processing dynamically',
        'success' => fn($result): string => 'Success: ' . $result,
        'failure' => 'Failed',
        'expected_output' => [
          '✅  Success: Done',
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
          '✅  Processed items',
          '- item1',
          '- item2',
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

}
