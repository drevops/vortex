<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Command\BuildCommand;
use DrevOps\VortexInstaller\Command\CheckRequirementsCommand;
use DrevOps\VortexInstaller\Logger\FileLoggerInterface;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Tests\Helpers\TuiOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Functional tests for BuildCommand.
 */
#[CoversClass(BuildCommand::class)]
class BuildCommandTest extends FunctionalTestCase {

  /**
   * Test build command with mocked runner.
   */
  #[DataProvider('dataProviderBuildCommand')]
  public function testBuildCommand(
    \Closure $exit_code_callback,
    array $command_inputs,
    bool $expect_failure,
    array $output_assertions,
    ?\Closure $requirements_exit_callback = NULL,
  ): void {
    // Create a mock ProcessRunner for ahoy build.
    $mock_runner = $this->createMock(ProcessRunner::class);

    // Set up common default behaviors.
    $current_command = '';
    $mock_runner->method('run')
      ->willReturnCallback(function (string $command) use ($mock_runner, &$current_command): MockObject {
        $current_command = $command;
        return $mock_runner;
      });

    // Mock getOutput() to handle both string and array returns.
    $mock_runner->method('getOutput')->willReturnCallback(fn(bool $as_array = FALSE): array|string => $as_array ? ['Mock build output line 1', 'Mock build output line 2'] : 'Mock build output');
    $mock_runner->method('getCommand')->willReturn('ahoy build');

    // Set up getExitCode using the provided callback.
    $mock_runner->method('getExitCode')
      ->willReturnCallback(function () use ($exit_code_callback, &$current_command) {
        return $exit_code_callback($current_command);
      });

    // Mock logger to prevent errors in showSuccessSummary/showFailureSummary.
    $mock_logger = $this->createMock(FileLoggerInterface::class);
    $mock_logger->method('getPath')->willReturn('/tmp/mock.log');
    $mock_runner->method('getLogger')->willReturn($mock_logger);

    // Mock setCwd to return runner for method chaining.
    $mock_runner->method('setCwd')->willReturn($mock_runner);

    // Create command and inject mock runner using setRunner().
    $command = new BuildCommand();
    $command->setRunner($mock_runner);

    // Initialize application with our command.
    static::applicationInitFromCommand($command);

    // Always register CheckRequirementsCommand with mocked runner.
    // Use provided callback or default to success (exit code 0).
    $requirements_runner = $this->createMock(ProcessRunner::class);

    $current_requirements_command = '';
    $requirements_runner->method('run')
      ->willReturnCallback(function (string $command) use ($requirements_runner, &$current_requirements_command): MockObject {
        $current_requirements_command = $command;
        return $requirements_runner;
      });

    $requirements_runner->method('getOutput')->willReturn('version 1.0.0');

    // Use provided callback or default to always returning 0 (success).
    $final_requirements_callback = $requirements_exit_callback ?? fn(string $current_command): int => 0;
    $requirements_runner->method('getExitCode')
      ->willReturnCallback(function () use ($final_requirements_callback, &$current_requirements_command) {
        return $final_requirements_callback($current_requirements_command);
      });

    $check_command = new CheckRequirementsCommand();
    $check_command->setRunner($requirements_runner);
    $this->applicationGet()->add($check_command);

    // Run build with provided inputs.
    $this->applicationRun($command_inputs, [], $expect_failure);

    // Assert output.
    if (!empty($output_assertions)) {
      $this->assertApplicationAnyOutputContainsOrNot($output_assertions);
    }
  }

  /**
   * Data provider for testBuildWithMockedRunner.
   *
   * @return array<string, array{
   *   exit_code_callback: \Closure,
   *   command_inputs: array<string, mixed>,
   *   expect_failure: bool,
   *   output_assertions: array<string>,
   *   requirements_exit_callback?: ?\Closure
   *   }>
   */
  public static function dataProviderBuildCommand(): array {
    return [
      // -----------------------------------------------------------------------
      // Requirements check scenarios.
      // -----------------------------------------------------------------------
      'Build runs requirements check by default (success)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
          TuiOutput::absent([
            TuiOutput::BUILD_EXPORT_DATABASE,
          ]),
        ),
        'requirements_exit_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      ],

      'Requirements check fails - one missing (Docker)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
          ]),
          TuiOutput::absent([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
        ),
        'requirements_exit_callback' => function (string $current_command): int {
          // Docker command fails, others succeed.
          if ((str_contains($current_command, "command -v 'docker'") || str_contains($current_command, 'command -v docker'))
            && !str_contains($current_command, 'compose')
            && !str_contains($current_command, 'docker --version')) {
            return RunnerInterface::EXIT_COMMAND_NOT_FOUND;
          }
          return RunnerInterface::EXIT_SUCCESS;
        },
      ],

      'Requirements check fails - all missing' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
          ]),
          TuiOutput::absent([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
        ),
        'requirements_exit_callback' => fn(string $current_command): int => RunnerInterface::EXIT_COMMAND_NOT_FOUND,
      ],

      // -----------------------------------------------------------------------
      // Basic build scenarios.
      // -----------------------------------------------------------------------
      'Build with skip requirements check (success)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
          TuiOutput::absent([
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
            TuiOutput::BUILD_EXPORT_DATABASE,
          ]),
        ),

      ],

      // -----------------------------------------------------------------------
      // Profile flag scenarios.
      // -----------------------------------------------------------------------
      'Build with profile flag and skip requirements (success)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [
          '--profile' => TRUE,
          '--skip-requirements-check' => TRUE,
        ],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
            TuiOutput::BUILD_EXPORT_DATABASE,
          ]),
          TuiOutput::absent([
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
          ]),
        ),

      ],

      'Build with profile flag and requirements check (success)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--profile' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => TuiOutput::present([
          TuiOutput::BUILD_CHECKING_REQUIREMENTS,
          TuiOutput::BUILD_BUILDING_SITE,
          TuiOutput::BUILD_BUILD_COMPLETED,
          TuiOutput::BUILD_EXPORT_DATABASE,
        ]),
        'requirements_exit_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      ],

      'Build with profile shows export database step' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [
          '--profile' => TRUE,
          '--skip-requirements-check' => TRUE,
        ],
        'expect_failure' => FALSE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
          '* ' . TuiOutput::BUILD_EXPORT_DATABASE . ' ahoy export-db',
        ],

      ],

      // -----------------------------------------------------------------------
      // Build failure scenarios.
      // -----------------------------------------------------------------------
      'Build failure (ahoy build fails, exit code 1)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_FAILURE,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_FAILED,
          ]),
          ['* ' . TuiOutput::INSTALL_EXIT_CODE . '  1'],
          TuiOutput::absent([
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
        ),

      ],

      'Build failure with profile (ahoy build fails, exit code 1)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_FAILURE,
        'command_inputs' => [
          '--profile' => TRUE,
          '--skip-requirements-check' => TRUE,
        ],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_FAILED,
          ]),
          ['* ' . TuiOutput::INSTALL_EXIT_CODE . '  1'],
          TuiOutput::absent([
            TuiOutput::BUILD_BUILD_COMPLETED,
            TuiOutput::BUILD_EXPORT_DATABASE,
          ]),
        ),

      ],

      'Build failure (ahoy build fails, exit code 2)' => [
        'exit_code_callback' => fn(string $current_command): int => 2,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_FAILED,
          ]),
          ['* ' . TuiOutput::INSTALL_EXIT_CODE . '  2'],
          TuiOutput::absent([
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
        ),

      ],

      'Build failure (ahoy build fails, exit code 127)' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_COMMAND_NOT_FOUND,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_FAILED,
          ]),
          ['* ' . TuiOutput::INSTALL_EXIT_CODE . '  127'],
          TuiOutput::absent([
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
        ),

      ],

      'Build failure shows log file path' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_FAILURE,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_FAILED,
          ]),
          ['* ' . TuiOutput::INSTALL_LOG_FILE . '   /tmp/mock.log'],
        ),

      ],

      // -----------------------------------------------------------------------
      // Success output verification scenarios.
      // -----------------------------------------------------------------------
      'Build success shows log file path' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
          ]),
          ['* ' . TuiOutput::INSTALL_LOG_FILE . ' /tmp/mock.log'],
        ),

      ],

      'Build success shows site URL' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::BUILD_BUILDING_SITE,
            TuiOutput::BUILD_BUILD_COMPLETED,
            TuiOutput::INSTALL_LOGIN,
          ]),
          ['* ' . TuiOutput::BUILD_SITE_URL . ' http://'],
        ),

      ],

      'Build success shows next steps' => [
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--skip-requirements-check' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => TuiOutput::present([
          TuiOutput::BUILD_BUILDING_SITE,
          TuiOutput::BUILD_BUILD_COMPLETED,
          TuiOutput::INSTALL_NEXT_STEPS,
          TuiOutput::INSTALL_REVIEW_DOCS,
        ]),

      ],
    ];
  }

}
