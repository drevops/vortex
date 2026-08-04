<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Command;

use AlexSkrypnyk\File\File;
use DrevOps\VortexCli\Command\DoctorCommand;
use DrevOps\VortexCli\Runner\ProcessRunner;
use DrevOps\VortexCli\Runner\RunnerInterface;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexCli\Tests\Helpers\TuiOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for DoctorCommand.
 */
#[CoversClass(DoctorCommand::class)]
class DoctorCommandTest extends FunctionalTestCase {

  /**
   * Test diagnostics with mocked runner.
   */
  #[DataProvider('dataProviderDoctorCommand')]
  public function testDoctorCommand(
    \Closure $executable_finder_callback,
    \Closure $exit_code_callback,
    array $command_inputs,
    bool $expect_failure,
    array $output_assertions,
    ?\Closure $before = NULL,
    ?\Closure $output_callback = NULL,
  ): void {
    if ($before instanceof \Closure) {
      $command_inputs = $before($command_inputs, self::$tmp);
    }

    $command = $this->doctorCommand($executable_finder_callback, $exit_code_callback, $output_callback);

    // Initialize application with our command.
    static::applicationInitFromCommand($command);

    // Run check with provided inputs.
    $this->applicationRun($command_inputs, [], $expect_failure);

    if (!empty($output_assertions)) {
      $this->assertApplicationAnyOutputContainsOrNot($output_assertions);
    }
  }

  /**
   * Data provider for testDoctorCommand.
   *
   * @return \Iterator<string, array{executable_finder_callback: \Closure, exit_code_callback: \Closure, command_inputs: array<string, mixed>, expect_failure: bool, output_assertions: array<string>, before?: (\Closure | null)}>
   */
  public static function dataProviderDoctorCommand(): \Iterator {
    yield 'Check all requirements' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => [],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          [
            '* Docker: version 1.0.0',
            '* Docker Compose: version 1.0.0',
            '* Ahoy: version 1.0.0',
            '* Pygmy: version 1.0.0',
          ],
      ),
    ];
    yield 'All requirements missing' => [
      'executable_finder_callback' => fn(string $name): ?string => NULL,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_COMMAND_NOT_FOUND,
      'command_inputs' => [],
      'expect_failure' => TRUE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          [
            '* Docker:',
            '* Docker Compose:',
            '* Ahoy:',
            '* Pygmy:',
          ],
          TuiOutput::absent([
            TuiOutput::DOCTOR_PRESENT_LABEL,
            TuiOutput::DOCTOR_ALL_MET,
          ]),
      ),
    ];
    yield 'Check only Docker' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'docker'],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          ['* Docker: version 1.0.0'],
          ['! Ahoy:', '! Pygmy:'],
      ),
    ];
    yield 'Check only Docker and Ahoy' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'docker,ahoy'],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          [
            '* Docker: version 1.0.0',
            '* Ahoy: version 1.0.0',
          ],
          ['! Pygmy:'],
      ),
    ];
    yield 'Check with no-summary option' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--no-summary' => TRUE],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
          ]),
          TuiOutput::absent([
            TuiOutput::DOCTOR_PRESENT_LABEL,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
      ),
    ];
    yield 'Docker missing' => [
      'executable_finder_callback' => fn(string $name): ?string => $name === 'docker' ? NULL : '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'docker'],
      'expect_failure' => TRUE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          ['* Docker:'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_DOCKER_AVAILABLE,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
      ),
    ];
    yield 'Ahoy missing' => [
      'executable_finder_callback' => fn(string $name): ?string => $name === 'ahoy' ? NULL : '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'ahoy'],
      'expect_failure' => TRUE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          ['* Ahoy:'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_AHOY_AVAILABLE,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
      ),
    ];
    yield 'Pygmy command not found' => [
      'executable_finder_callback' => fn(string $name): ?string => $name === 'pygmy' ? NULL : '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'pygmy'],
      'expect_failure' => TRUE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          ['* Pygmy:'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_PYGMY_RUNNING,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
      ),
    ];
    yield 'Pygmy status command succeeds' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'pygmy'],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          ['* Pygmy: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
      ),
    ];
    yield 'Pygmy status fails but amazeeio containers found' => [
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
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          ['* Pygmy: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
      ),
      'before' => NULL,
      // The container list is read, so a running Pygmy shows up in its output
      // rather than in an exit code.
      'output_callback' => fn(string $current_command): string => str_contains($current_command, 'docker') ? 'amazeeio-haproxy' : 'version 1.0.0',
    ];
    yield 'Pygmy status fails and no amazeeio containers' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => function (string $current_command): int {
          // Pygmy status fails.
        if (str_contains($current_command, 'pygmy status')) {
          return RunnerInterface::EXIT_FAILURE;
        }
          return RunnerInterface::EXIT_SUCCESS;
      },
      'command_inputs' => ['--only' => 'pygmy'],
      'expect_failure' => TRUE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          ['* Pygmy:'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_PYGMY_RUNNING,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
      ),
      'before' => NULL,
      // Containers are running, but none of them belong to Pygmy.
      'output_callback' => fn(string $current_command): string => str_contains($current_command, 'docker') ? 'some-other-container' : 'version 1.0.0',
    ];
    yield 'Docker Compose via modern syntax' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'docker-compose'],
      'expect_failure' => FALSE,
      'output_assertions' => array_merge(
          TuiOutput::present([
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          ['* Docker Compose: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
      ),
    ];
    yield 'Docker Compose via legacy command' => [
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
            TuiOutput::DOCTOR_ALL_MET,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
          ['* Docker Compose: version 1.0.0'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
      ),
    ];
    yield 'Docker Compose missing completely' => [
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
            TuiOutput::DOCTOR_MISSING,
            TuiOutput::DOCTOR_MISSING_LABEL,
          ]),
          ['* Docker Compose:'],
          TuiOutput::absent([
            TuiOutput::DOCTOR_DOCKER_COMPOSE_AVAILABLE,
            TuiOutput::DOCTOR_PRESENT_LABEL,
          ]),
      ),
    ];
    yield 'Invalid requirement name' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'invalid'],
      'expect_failure' => TRUE,
      'output_assertions' => [
        '* ' . TuiOutput::DOCTOR_UNKNOWN . ' invalid',
        '* ' . TuiOutput::DOCTOR_AVAILABLE,
      ],
    ];
    yield 'Mixed valid and invalid requirements' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => ['--only' => 'docker,invalid'],
      'expect_failure' => TRUE,
      'output_assertions' => [
        '* ' . TuiOutput::DOCTOR_UNKNOWN . ' invalid',
        '* ' . TuiOutput::DOCTOR_AVAILABLE,
      ],
    ];
    yield 'Valid destination directory' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => [],
      'expect_failure' => FALSE,
      'output_assertions' => TuiOutput::present([TuiOutput::DOCTOR_ALL_MET]),
      'before' => function (array $inputs, string $tmp): array {
          $dir = $tmp . '/valid_dest_' . uniqid();
          File::mkdir($dir);
          $inputs['--destination'] = $dir;
          return $inputs;
      },
    ];
    yield 'Destination is file instead of directory' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => [],
      'expect_failure' => TRUE,
      'output_assertions' => TuiOutput::present([TuiOutput::DESTINATION_NOT_EXIST]),
      'before' => function (array $inputs, string $tmp): array {
          $file = $tmp . '/test_file_' . uniqid() . '.txt';
          File::dump($file, 'test content');
          $inputs['--destination'] = $file;
          return $inputs;
      },
    ];
    yield 'Destination directory does not exist' => [
      'executable_finder_callback' => fn(string $name): string => '/usr/bin/' . $name,
      'exit_code_callback' => fn(string $current_command): int => RunnerInterface::EXIT_SUCCESS,
      'command_inputs' => [],
      'expect_failure' => TRUE,
      'output_assertions' => TuiOutput::present([TuiOutput::DESTINATION_NOT_EXIST]),
      'before' => function (array $inputs, string $tmp): array {
          $inputs['--destination'] = $tmp . '/non_existent_' . uniqid();
          return $inputs;
      },
    ];
  }

  /**
   * Every checked tool reports an install instruction when it is absent.
   *
   * Pins the reported surface so the diagnostics cannot quietly shrink to a
   * bare present/absent list.
   */
  public function testReportsInstallInstructionsForEveryMissingTool(): void {
    $command = $this->doctorCommand(fn(string $name): ?string => NULL, fn(string $command): int => RunnerInterface::EXIT_COMMAND_NOT_FOUND);

    static::applicationInitFromCommand($command);
    $this->applicationRun([], [], TRUE);

    $this->assertSame([], $command->getPresent(), 'No tool should be reported as present when none are installed');

    $missing = $command->getMissing();
    $this->assertSame(['Docker', 'Docker Compose', 'Ahoy', 'Pygmy'], array_keys($missing), 'Every checked tool should be reported as missing');

    foreach ($missing as $tool => $instruction) {
      $this->assertNotEmpty($instruction, sprintf('Tool "%s" should report an install instruction', $tool));
    }
  }

  /**
   * A tool that is installed but not running is reported as missing.
   *
   * Being on PATH is not the same as being usable, and conflating the two
   * would hide the most common local failure.
   */
  public function testDistinguishesInstalledFromRunning(): void {
    $command = $this->doctorCommand(
      fn(string $name): string => '/usr/bin/' . $name,
      fn(string $command): int => str_contains($command, 'pygmy status') || str_contains($command, 'amazeeio') ? RunnerInterface::EXIT_FAILURE : RunnerInterface::EXIT_SUCCESS,
    );

    static::applicationInitFromCommand($command);
    $this->applicationRun(['--only' => 'pygmy'], [], TRUE);

    $this->assertArrayNotHasKey('Pygmy', $command->getPresent(), 'Pygmy on PATH but not running should not be reported as present');
    $this->assertSame(['Pygmy' => 'Run: pygmy up'], $command->getMissing(), 'Pygmy on PATH but not running should be reported as missing');
  }

  /**
   * Build a command with the executable finder and process runner mocked.
   */
  protected function doctorCommand(\Closure $executable_finder_callback, \Closure $exit_code_callback, ?\Closure $output_callback = NULL): DoctorCommand {
    $mock_finder = $this->createMock(ExecutableFinder::class);
    $mock_finder->method('find')->willReturnCallback(fn(string $name) => $executable_finder_callback($name));

    $mock_runner = $this->createMock(ProcessRunner::class);
    $current_command = '';
    $mock_runner->method('run')->willReturnCallback(function (string $command) use ($mock_runner, &$current_command): MockObject {
      $current_command = $command;
      return $mock_runner;
    });
    // Bound by reference so each stub sees the command being run, not the empty
    // string the runner started with.
    $mock_runner->method('getOutput')->willReturnCallback(function () use ($output_callback, &$current_command): string {
      return $output_callback instanceof \Closure ? $output_callback($current_command) : 'version 1.0.0';
    });
    $mock_runner->method('getExitCode')->willReturnCallback(function () use ($exit_code_callback, &$current_command) {
      return $exit_code_callback($current_command);
    });

    $command = new DoctorCommand();
    $command->setExecutableFinder($mock_finder);
    $command->setProcessRunner($mock_runner);

    return $command;
  }

}
