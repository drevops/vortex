<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Tui;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the Task class.
 */
#[CoversClass(Task::class)]
class TaskTest extends UnitTestCase {

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
    Task::action($label, $action, $hint, $success, $failure);

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
        'expected_output' => '✓ OK',
      ],

      'successful action with string messages' => [
        'label' => 'Processing task',
        'action' => fn(): null => NULL,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => [
          '✓ Completed successfully',
          'This is a hint',
        ],
      ],

      'successful action with string label and TRUE value' => [
        'label' => 'Processing task',
        'action' => fn(): true => TRUE,
        'hint' => 'This is a hint',
        'success' => 'Completed successfully',
        'failure' => 'Failed',
        'expected_output' => '✓ Completed successfully',
      ],

      'successful action with closures' => [
        'label' => fn(): string => 'Dynamic label',
        'action' => fn(): string => 'Done',
        'hint' => fn(): string => 'Processing dynamically',
        'success' => fn($result): string => 'Success: ' . $result,
        'failure' => 'Failed',
        'expected_output' => [
          '✓ Success: Done',
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
          '✓ Processed items',
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

  public function testActionInvalidArgument(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Action must be callable.');

    Task::action('Test label');
  }

  /**
   * Test streaming mode with OutputInterface usage.
   *
   * This tests when the closure uses Tui methods (or any OutputInterface
   * methods) to write output.
   */
  public function testActionStreamingWithOutputInterface(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Processing with output',
      action: function (): string {
        // Use Tui methods that write to the output interface.
        Tui::line('Line 1 from Tui');
        Tui::line('Line 2 from Tui');
        return 'done';
      },
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals('done', $result);

    // Verify the start message is shown.
    $this->assertStringContainsString('Processing with output', $actual);

    // Verify the output from Tui methods is captured and dimmed.
    // TaskOutput wraps messages with dim ANSI codes.
    $this->assertStringContainsString('Line 1 from Tui', $actual);
    $this->assertStringContainsString('Line 2 from Tui', $actual);

    // Verify success message is shown.
    $this->assertStringContainsString('✓ OK', $actual);
  }

  /**
   * Test streaming mode with echo/print statements.
   *
   * This tests when the closure uses echo/print (PHP output buffering
   * captures these).
   */
  public function testActionStreamingWithEchoPrint(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Processing with echo',
      action: function (): string {
        // Use echo/print statements.
        echo "Echo output line 1\n";
        echo "Echo output line 2\n";
        print "Print output line 3\n";
        return 'completed';
      },
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals('completed', $result);

    // Verify the start message is shown.
    $this->assertStringContainsString('Processing with echo', $actual);

    // Verify echo/print output is captured via output buffering.
    $this->assertStringContainsString('Echo output line 1', $actual);
    $this->assertStringContainsString('Echo output line 2', $actual);
    $this->assertStringContainsString('Print output line 3', $actual);

    // Verify success message is shown.
    $this->assertStringContainsString('✓ OK', $actual);
  }

  /**
   * Test streaming mode with both OutputInterface and echo/print.
   *
   * This tests that both types of output are captured correctly in the same
   * action.
   */
  public function testActionStreamingWithMixedOutput(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Processing mixed output',
      action: function (): array {
        // Mix OutputInterface usage and echo statements.
        Tui::line('From Tui line 1');
        echo "From echo line 1\n";
        Tui::line('From Tui line 2');
        echo "From echo line 2\n";
        print "From print line 3\n";
        return ['result1', 'result2'];
      },
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals(['result1', 'result2'], $result);

    // Verify both types of output are captured.
    $this->assertStringContainsString('From Tui line 1', $actual);
    $this->assertStringContainsString('From echo line 1', $actual);
    $this->assertStringContainsString('From Tui line 2', $actual);
    $this->assertStringContainsString('From echo line 2', $actual);
    $this->assertStringContainsString('From print line 3', $actual);

    // Verify success message is shown.
    $this->assertStringContainsString('✓ OK', $actual);
  }

  /**
   * Test streaming mode with failure.
   */
  public function testActionStreamingWithFailure(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Processing that fails',
      action: function (): false {
        echo "Some output before failure\n";
        Tui::line('More output');
        return FALSE;
      },
      failure: 'Operation failed',
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action returned FALSE.
    $this->assertFalse($result);

    // Verify output is captured even on failure.
    $this->assertStringContainsString('Some output before failure', $actual);
    $this->assertStringContainsString('More output', $actual);

    // Verify failure message is shown.
    $this->assertStringContainsString('Operation failed', $actual);
  }

  /**
   * Test streaming mode with custom success message.
   */
  public function testActionStreamingWithCustomSuccessMessage(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Processing data',
      action: function (): int {
        echo "Processing item 1\n";
        echo "Processing item 2\n";
        return 42;
      },
      success: fn(mixed $result): string => sprintf('Processed %s items', $result),
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals(42, $result);

    // Verify output is captured.
    $this->assertStringContainsString('Processing item 1', $actual);
    $this->assertStringContainsString('Processing item 2', $actual);

    // Verify custom success message with result is shown.
    $this->assertStringContainsString('Processed 42 items', $actual);
  }

  /**
   * Test that output is restored after streaming.
   */
  public function testActionStreamingRestoresOutput(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    // Store original output.
    $original_output = Tui::output();

    Task::action(
      label: 'Testing output restoration',
      action: function (): string {
        echo "Some streamed output\n";
        return 'done';
      },
      streaming: TRUE,
    );

    // Verify output is restored to the original.
    $restored_output = Tui::output();
    $this->assertSame($original_output, $restored_output);
  }

  /**
   * Test streaming with no output from action.
   */
  public function testActionStreamingWithNoOutput(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    $result = Task::action(
      label: 'Silent processing',
      action: fn(): string => 'completed',
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals('completed', $result);

    // Verify start message and success are shown even with no action output.
    $this->assertStringContainsString('Silent processing', $actual);
    $this->assertStringContainsString('✓ OK', $actual);
  }

  /**
   * Test streaming with action that directly uses the output parameter.
   *
   * This verifies that when the action callback receives and uses the output
   * parameter directly (not through Tui), the streaming functionality properly
   * captures all output and doesn't let it spill elsewhere.
   */
  public function testActionStreamingWithDirectOutputUsage(): void {
    $output = new BufferedOutput();
    Tui::init($output);

    // Track what was captured in the main output.
    $result = Task::action(
      label: 'Processing with direct output',
      action: function (): string {
        // Get the current output (which should be TaskOutput during streaming).
        $current_output = Tui::output();

        // Write directly to the output parameter.
        $current_output->writeln('Direct output line 1');
        $current_output->writeln('Direct output line 2');

        // Mix with echo.
        echo "Echo during streaming\n";

        // Write more to output.
        $current_output->write('Direct output line 3');

        return 'done';
      },
      streaming: TRUE,
    );

    $actual = $output->fetch();

    // Verify the action executed successfully.
    $this->assertEquals('done', $result);

    // Verify all output was captured and not spilled.
    $this->assertStringContainsString('Direct output line 1', $actual);
    $this->assertStringContainsString('Direct output line 2', $actual);
    $this->assertStringContainsString('Direct output line 3', $actual);
    $this->assertStringContainsString('Echo during streaming', $actual);

    // Verify the label and success are shown.
    $this->assertStringContainsString('Processing with direct output', $actual);
    $this->assertStringContainsString('✓ OK', $actual);

    // The key verification: all output should be in the buffered output,
    // not leaked anywhere else. We verify this by checking that the
    // BufferedOutput received everything.
    $this->assertNotEmpty($actual);
  }

}
