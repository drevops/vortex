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
    $label,
    $action,
    $hint,
    $success,
    $failure,
    $expected_output
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
        'action' => fn() => NULL,
        'hint' => NULL,
        'success' => NULL,
        'failure' => NULL,
        'expected_output' => '✅  OK',
      ],

      'successful action with string messages' => [
        'label' => 'Processing task',
        'action' => fn() => NULL,
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
        'action' => fn() => TRUE,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => '✅  Completed successfully',
      ],

      'successful action with closures' => [
        'label' => fn() => 'Dynamic label',
        'action' => fn() => 'Done',
        'hint' => fn() => 'Processing dynamically',
        'success' => fn($result) => "Success: {$result}",
        'failure' => 'Failed',
        'expected_output' => [
          '✅  Success: Done',
          'Processing dynamically',
        ],
      ],

      'successful action returning array' => [
        'label' => 'Processing array',
        'action' => fn() => ['item1', 'item2'],
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
        'label' => fn() => 'Dynamic label',
        'action' => fn() => FALSE,
        'hint' => fn() => 'Processing dynamically',
        'success' => fn($result) => "Success: {$result}",
        'failure' => 'Failed',
        'expected_output' => 'Failed',
      ],

      'failed action with closure' => [
        'label' => fn() => 'Dynamic label',
        'action' => fn() => FALSE,
        'hint' => fn() => 'Processing dynamically',
        'success' => fn($result) => "Success: {$result}",
        'failure' => fn() => 'Failed dynamically',
        'expected_output' => 'Failed dynamically',
      ],

    ];
  }

}
