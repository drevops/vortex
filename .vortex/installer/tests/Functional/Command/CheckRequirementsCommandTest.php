<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Command\CheckRequirementsCommand;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Tests\Helpers\TuiOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for CheckRequirementsCommand.
 */
#[CoversClass(CheckRequirementsCommand::class)]
class CheckRequirementsCommandTest extends FunctionalTestCase {

  /**
   * Test check requirements with mocked runner.
   */
  #[DataProvider('dataProviderCheckRequirementsCommand')]
  public function testCheckRequirementsCommand(
    \Closure $executable_finder_callback,
    \Closure $exit_code_callback,
    array $command_inputs,
    bool $expect_failure,
    array $output_assertions,
  ): void {
    // Create a mock ExecutableFinder.
    $mock_finder = $this->createMock(ExecutableFinder::class);
    $mock_finder->method('find')
      ->willReturnCallback(fn(string $name) => $executable_finder_callback($name));

    // Create a mock ProcessRunner.
    $mock_runner = $this->createMock(ProcessRunner::class);

    // Set up common default behaviors.
    $current_command = '';
    $mock_runner->method('run')
      ->willReturnCallback(function (string $command) use ($mock_runner, &$current_command): MockObject {
        $current_command = $command;
        return $mock_runner;
      });

    $mock_runner->method('getOutput')->willReturn('version 1.0.0');

    // Set up getExitCode using the provided callback.
    $mock_runner->method('getExitCode')
      ->willReturnCallback(function () use ($exit_code_callback, &$current_command) {
        return $exit_code_callback($current_command);
      });

    // Create command and inject mocks using setters.
    $command = new CheckRequirementsCommand();
    $command->setExecutableFinder($mock_finder);
    $command->setProcessRunner($mock_runner);

    // Initialize application with our command.
    static::applicationInitFromCommand($command);

    // Run check with provided inputs.
    $this->applicationRun($command_inputs, [], $expect_failure);

    if (!empty($output_assertions)) {
      $this->assertApplicationAnyOutputContainsOrNot($output_assertions);
    }
  }

  /**
   * Data provider for testCheckRequirementsCommand.
   *
   * @return array<string, array{
   *   executable_finder_callback: \Closure,
   *   exit_code_callback: \Closure,
   *   command_inputs: array<string, mixed>,
   *   expect_failure: bool,
   *   output_assertions: array<string>
   *   }>
   */
  public static function dataProviderCheckRequirementsCommand(): array {
    return [
      'Check all requirements' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => [],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          [
            '* Docker: version 1.0.0',
            '* Docker Compose: version 1.0.0',
            '* Ahoy: version 1.0.0',
            '* Pygmy: version 1.0.0',
          ],
        ),
      ],

      'All requirements missing' => [
        'executable_finder_callback' => fn(string $name): ?string => NULL,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_COMMAND_NOT_FOUND,
        'command_inputs' => [],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          [
            '* Docker:',
            '* Docker Compose:',
            '* Ahoy:',
            '* Pygmy:',
          ],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
          ]),
        ),
      ],

      'Check only Docker' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'docker'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          ['* Docker: version 1.0.0'],
          ['! Ahoy:', '! Pygmy:'],
        ),
      ],

      'Check only Docker and Ahoy' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'docker,ahoy'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          [
            '* Docker: version 1.0.0',
            '* Ahoy: version 1.0.0',
          ],
          ['! Pygmy:'],
        ),
      ],

      'Check with no-summary option' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--no-summary' => TRUE],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
          ]),
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
        ),
      ],

      'Docker missing' => [
        'executable_finder_callback' => fn(string $name): ?string => $name === 'docker' ? NULL : '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'docker'],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          ['* Docker:'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
        ),
      ],

      'Ahoy missing' => [
        'executable_finder_callback' => fn(string $name): ?string => $name === 'ahoy' ? NULL : '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'ahoy'],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          ['* Ahoy:'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_AHOY_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
        ),
      ],

      'Pygmy command not found' => [
        'executable_finder_callback' => fn(string $name): ?string => $name === 'pygmy' ? NULL : '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'pygmy'],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          ['* Pygmy:'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_PYGMY_RUNNING,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
        ),
      ],

      'Pygmy status command succeeds' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'pygmy'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          ['* Pygmy: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
        ),
      ],

      'Pygmy status fails but amazeeio containers found' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => function (string $current_command): int {
          // Pygmy status fails.
          if (str_contains($current_command, 'pygmy status')) {
            return RunnerInterface::EXIT_FAILURE;
          }
          return RunnerInterface::EXIT_SUCCESS;
        },
        'command_inputs' => ['--only' => 'pygmy'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          ['* Pygmy: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
        ),
      ],

      'Pygmy status fails and no amazeeio containers' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => function (string $current_command): int {
          // Pygmy status fails.
          if (str_contains($current_command, 'pygmy status')) {
            return RunnerInterface::EXIT_FAILURE;
          }
          // No amazeeio containers.
          if (str_contains($current_command, 'docker ps') && str_contains($current_command, 'amazeeio')) {
            return RunnerInterface::EXIT_FAILURE;
          }
          return RunnerInterface::EXIT_SUCCESS;
        },
        'command_inputs' => ['--only' => 'pygmy'],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          ['* Pygmy:'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_PYGMY_RUNNING,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
        ),
      ],

      'Docker Compose via modern syntax' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'docker-compose'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          ['* Docker Compose: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
        ),
      ],

      'Docker Compose via legacy command' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => function (string $current_command): int {
          // Modern syntax fails.
          if (str_contains($current_command, 'docker compose version')) {
            return RunnerInterface::EXIT_COMMAND_NOT_FOUND;
          }
          return RunnerInterface::EXIT_SUCCESS;
        },
        'command_inputs' => ['--only' => 'docker-compose'],
        'expect_failure' => FALSE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
          ['* Docker Compose: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
        ),
      ],

      'Docker Compose missing completely' => [
        'executable_finder_callback' => fn(string $name): ?string => $name === 'docker-compose' ? NULL : '/usr/bin/' . $name,
        'exit_code_callback' => function (string $current_command): int {
          // Modern docker compose command fails.
          if (str_contains($current_command, 'docker compose version')) {
            return RunnerInterface::EXIT_COMMAND_NOT_FOUND;
          }
          return RunnerInterface::EXIT_SUCCESS;
        },
        'command_inputs' => ['--only' => 'docker-compose'],
        'expect_failure' => TRUE,
        'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_MISSING_LABEL,
          ]),
          ['* Docker Compose:'],
          TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_COMPOSE_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_PRESENT_LABEL,
          ]),
        ),
      ],

      'Invalid requirement name' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'invalid'],
        'expect_failure' => TRUE,
        'output_assertions' => [
          '* ' . TuiOutput::CHECK_REQUIREMENTS_UNKNOWN . ' invalid',
          '* Available: docker, docker-compose, ahoy',
        ],
      ],

      'Mixed valid and invalid requirements' => [
        'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
        'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
        'command_inputs' => ['--only' => 'docker,invalid'],
        'expect_failure' => TRUE,
        'output_assertions' => [
          '* ' . TuiOutput::CHECK_REQUIREMENTS_UNKNOWN . ' invalid',
          '* Available: docker, docker-compose, ahoy',
        ],
      ],
    ];
  }

}
