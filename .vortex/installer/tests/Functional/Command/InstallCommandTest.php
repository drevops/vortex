<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Logger\FileLoggerInterface;
use DrevOps\VortexInstaller\Command\BuildCommand;
use DrevOps\VortexInstaller\Command\CheckRequirementsCommand;
use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Tests\Helpers\TuiOutput;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for InstallCommand.
 */
#[CoversClass(InstallCommand::class)]
class InstallCommandTest extends FunctionalTestCase {

  /**
   * Test install command with mocked runner.
   */
  #[DataProvider('dataProviderInstallCommand')]
  public function testInstallCommand(
    array $command_inputs,
    \Closure $install_executable_finder_find_callback,
    \Closure $build_runner_exit_callback,
    \Closure $check_requirements_runner_exit_callback,
    bool $expect_failure,
    array $output_assertions,
    bool $download_should_fail = FALSE,
  ): void {
    // 1. Mock ExecutableFinder for InstallCommand (requirements checking).
    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')
      ->willReturnCallback(fn(string $name) => $install_executable_finder_find_callback($name));

    // 2. Mock ProcessRunner for BuildCommand (runs 'ahoy build').
    $build_runner = $this->createMock(ProcessRunner::class);
    $build_runner_command = '';
    $build_runner->method('run')
      ->willReturnCallback(function (string $command) use ($build_runner, &$build_runner_command): MockObject {
        $build_runner_command = $command;
        return $build_runner;
      });
    $build_runner->method('getExitCode')
      ->willReturnCallback(function () use ($build_runner_exit_callback, &$build_runner_command) {
        return $build_runner_exit_callback($build_runner_command);
      });
    // Mock other BuildCommand runner methods.
    $build_runner->method('getOutput')->willReturnCallback(fn(bool $as_array = FALSE): array|string => $as_array ? ['Mock build output line 1', 'Mock build output line 2'] : 'Mock build output');
    $build_runner->method('getCommand')->willReturn('ahoy build');
    $mock_logger = $this->createMock(FileLoggerInterface::class);
    $mock_logger->method('getPath')->willReturn('/tmp/mock.log');
    $build_runner->method('getLogger')->willReturn($mock_logger);
    $build_runner->method('setCwd')->willReturn($build_runner);
    // Mock ExecutableFinder for BuildCommand's ProcessRunner.
    $build_runner->method('getExecutableFinder')->willReturn($executable_finder);

    // 3. Mock ProcessRunner for CheckRequirementsCommand.
    $check_requirements_runner = $this->createMock(ProcessRunner::class);
    $check_requirements_runner_command = '';
    $check_requirements_runner->method('run')
      ->willReturnCallback(function (string $command) use ($check_requirements_runner, &$check_requirements_runner_command): MockObject {
        $check_requirements_runner_command = $command;
        return $check_requirements_runner;
      });
    $check_requirements_runner->method('getOutput')->willReturn('version 1.0.0');
    $check_requirements_runner->method('getExitCode')
      ->willReturnCallback(function () use ($check_requirements_runner_exit_callback, &$check_requirements_runner_command) {
        return $check_requirements_runner_exit_callback($check_requirements_runner_command);
      });
    // Mock ExecutableFinder for CheckRequirementsCommand's ProcessRunner.
    $check_requirements_runner->method('getExecutableFinder')->willReturn($executable_finder);

    // Create and configure InstallCommand.
    $install_command = new InstallCommand();
    $install_command->setExecutableFinder($executable_finder);

    if ($download_should_fail) {
      $mock_downloader = $this->createMock(RepositoryDownloader::class);
      $mock_downloader->method('download')->willThrowException(new \RuntimeException('Failed to download Vortex.'));
      $install_command->setRepositoryDownloader($mock_downloader);
    }
    else {
      // Download from root as a real repository. This is long, but there is
      // no other way to test the rest of the installation process without
      // having all files in place.
      $command_inputs['--' . InstallCommand::OPTION_URI] = File::dir(static::$root);
    }

    // Initialize application and register mocked commands.
    static::applicationInitFromCommand($install_command);

    $check_command = new CheckRequirementsCommand();
    $check_command->setExecutableFinder($executable_finder);
    $check_command->setProcessRunner($check_requirements_runner);
    $this->applicationGet()->add($check_command);

    $build_command = new BuildCommand();
    $build_command->setProcessRunner($build_runner);
    $this->applicationGet()->add($build_command);

    $command_inputs['--' . InstallCommand::OPTION_DESTINATION] = self::$sut;

    $this->applicationRun($command_inputs, [], $expect_failure);

    if (!empty($output_assertions)) {
      $this->assertApplicationAnyOutputContainsOrNot($output_assertions);
    }
  }

  /**
   * Data provider for testInstallCommand.
   *
   * @return array<string, array{
   *   command_inputs: array<string, mixed>,
   *   install_executable_finder_find_callback: \Closure,
   *   build_runner_exit_callback: \Closure,
   *   check_requirements_runner_exit_callback: \Closure,
   *   expect_failure: bool,
   *   output_assertions: array<string>,
   *   download_should_fail?: bool
   *   }>
   */
  public static function dataProviderInstallCommand(): array {
    return [
      'Install without build flag, skips build' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => FALSE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
            TuiOutput::INSTALL_COPYING_FILES,
            TuiOutput::INSTALL_PREPARING_DEMO,
            TuiOutput::FOOTER_FINISHED_INSTALLING,
            TuiOutput::FOOTER_GIT_ADD,
            TuiOutput::FOOTER_GIT_COMMIT,
            TuiOutput::FOOTER_READY_TO_BUILD,
            TuiOutput::FOOTER_BUILD_THE_SITE,
            TuiOutput::FOOTER_AHOY_BUILD,
            TuiOutput::POSTBUILD_SETUP_GHA,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_BUILDING,
            TuiOutput::FOOTER_SITE_READY,
          ]),
        ],
      ],

      'Install with config JSON string succeeds' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_CONFIG => '{"VORTEX_PROJECT_NAME":"test_project"}',
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => FALSE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
          ]),
        ],
      ],

      'Install with no-cleanup flag succeeds' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_NO_CLEANUP => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => FALSE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
          ]),
        ],
      ],

      // -----------------------------------------------------------------------
      // Install command fails requirements check.
      // -----------------------------------------------------------------------
      'Requirements of install command check fails, missing git' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => function (string $command): ?string {
          // Git command fails.
          if (str_contains($command, 'git')) {
            return NULL;
          }
          return '/usr/bin/' . $command;
        },
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_ERROR_MISSING_GIT,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_STARTING,
          ]),
        ],
      ],

      'Requirements of install command check fails, missing tar' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => function (string $command): ?string {
          // Tar command fails.
          if (str_contains($command, 'tar')) {
            return NULL;
          }
          return '/usr/bin/' . $command;
        },

        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_ERROR_MISSING_TAR,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_STARTING,
          ]),
        ],
      ],

      'Requirements of install command check fails, missing composer' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => function (string $command): ?string {
          // Composer command fails.
          if (str_contains($command, 'composer')) {
            return NULL;
          }
          return '/usr/bin/' . $command;
        },

        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_ERROR_MISSING_COMPOSER,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_STARTING,
          ]),
        ],
      ],

      'Requirements of install command check fails, multiple missing tools' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => function (string $command): ?string {
          // Both git and curl commands fails.
          if (str_contains($command, 'git')) {
            return NULL;
          }

          if (str_contains($command, 'curl')) {
            return NULL;
          }

          return '/usr/bin/' . $command;
        },
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_ERROR_MISSING_GIT,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_STARTING,
          ]),
        ],
      ],

      // -----------------------------------------------------------------------
      // Download failures.
      // -----------------------------------------------------------------------
      'Download fails' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'check_requirements_runner_exit_callback' => fn(string $command): int => RunnerInterface::EXIT_SUCCESS,
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_ERROR_DOWNLOAD_FAILED,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
          ]),
        ],
        'download_should_fail' => TRUE,
      ],

      // -----------------------------------------------------------------------
      // Sub-commands: build with check-requirements.
      // -----------------------------------------------------------------------
      'Install with build flag succeeds' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_BUILD => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => TuiOutput::buildRunnerSuccess(),
        'check_requirements_runner_exit_callback' => TuiOutput::checkRequirementsSuccess(),
        'expect_failure' => FALSE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
            TuiOutput::INSTALL_COPYING_FILES,
            TuiOutput::INSTALL_PREPARING_DEMO,
            TuiOutput::INSTALL_BUILDING,
            TuiOutput::INSTALL_BUILD_SUCCESS,
            TuiOutput::FOOTER_FINISHED_INSTALLING,
            TuiOutput::FOOTER_GIT_ADD,
            TuiOutput::FOOTER_GIT_COMMIT,
            TuiOutput::FOOTER_SITE_READY,
            TuiOutput::FOOTER_GET_SITE_INFO,
            TuiOutput::FOOTER_AHOY_INFO,
            TuiOutput::INSTALL_LOGIN,
            TuiOutput::FOOTER_AHOY_LOGIN,
            TuiOutput::POSTBUILD_SETUP_GHA,
          ]),
          ...TuiOutput::absent([
            TuiOutput::FOOTER_READY_TO_BUILD,
            TuiOutput::FOOTER_BUILD_ERRORS,
          ]),
        ],
      ],

      'Install with build flag and profile starter succeeds' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_BUILD => TRUE,
          InstallCommand::OPTION_CONFIG => '{"VORTEX_STARTER":"install_profile_core"}',
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => TuiOutput::buildRunnerSuccessProfile(),
        'check_requirements_runner_exit_callback' => TuiOutput::checkRequirementsSuccess(),
        'expect_failure' => FALSE,
        'output_assertions' => [
          // Install command output - should be present.
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
            TuiOutput::INSTALL_COPYING_FILES,
            TuiOutput::INSTALL_PREPARING_DEMO,
            TuiOutput::INSTALL_BUILDING,
          ]),
          // Check requirements output - should be present.
          ...TuiOutput::present([
            TuiOutput::CHECK_REQUIREMENTS_CHECKING_DOCKER,
            TuiOutput::CHECK_REQUIREMENTS_CHECKING_DOCKER_COMPOSE,
            TuiOutput::CHECK_REQUIREMENTS_CHECKING_AHOY,
            TuiOutput::CHECK_REQUIREMENTS_CHECKING_PYGMY,
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_COMPOSE_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_AHOY_AVAILABLE,
            TuiOutput::CHECK_REQUIREMENTS_PYGMY_RUNNING,
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
          ]),
          // Build output (profile) - should be present.
          ...TuiOutput::present([
            TuiOutput::BUILD_ASSEMBLE_DOCKER,
            TuiOutput::BUILD_ASSEMBLE_COMPOSER,
            TuiOutput::BUILD_ASSEMBLE_YARN,
            TuiOutput::BUILD_PROVISION_START,
            TuiOutput::BUILD_PROVISION_PROJECT_INFO,
            TuiOutput::BUILD_PROVISION_TYPE_PROFILE,
            TuiOutput::BUILD_PROVISION_END,
          ]),
          // Final install output - should be present.
          ...TuiOutput::present([
            TuiOutput::INSTALL_BUILD_SUCCESS,
            TuiOutput::FOOTER_FINISHED_INSTALLING,
            TuiOutput::FOOTER_GIT_ADD,
            TuiOutput::FOOTER_GIT_COMMIT,
            TuiOutput::FOOTER_SITE_READY,
            TuiOutput::FOOTER_GET_SITE_INFO,
            TuiOutput::FOOTER_AHOY_INFO,
            TuiOutput::INSTALL_LOGIN,
            TuiOutput::FOOTER_AHOY_LOGIN,
            TuiOutput::POSTBUILD_SETUP_GHA,
          ]),
          // Negative assertions - should be absent.
          ...TuiOutput::absent([
            TuiOutput::BUILD_PROVISION_TYPE_DB,
            TuiOutput::INSTALL_BUILD_FAILED,
            TuiOutput::INSTALL_EXIT_CODE,
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_DOCKER_COMPOSE_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_AHOY_MISSING,
            TuiOutput::CHECK_REQUIREMENTS_PYGMY_NOT_RUNNING,
            TuiOutput::FOOTER_READY_TO_BUILD,
            TuiOutput::FOOTER_BUILD_ERRORS,
          ]),
        ],
      ],

      'Install with build flag fails' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_BUILD => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => TuiOutput::buildRunnerFailure(),
        'check_requirements_runner_exit_callback' => TuiOutput::checkRequirementsSuccess(),
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
            TuiOutput::INSTALL_COPYING_FILES,
            TuiOutput::INSTALL_PREPARING_DEMO,
            TuiOutput::INSTALL_BUILDING,
            TuiOutput::INSTALL_BUILD_FAILED,
            TuiOutput::FOOTER_FINISHED_INSTALLING,
            TuiOutput::FOOTER_GIT_ADD,
            TuiOutput::FOOTER_GIT_COMMIT,
            TuiOutput::FOOTER_BUILD_ERRORS,
            TuiOutput::FOOTER_BUILD_FAILED_MESSAGE,
            TuiOutput::FOOTER_TROUBLESHOOTING,
            TuiOutput::FOOTER_CHECK_LOGS,
            TuiOutput::FOOTER_AHOY_BUILD,
            TuiOutput::FOOTER_DIAGNOSTICS,
          ]),
          ...TuiOutput::absent([
            TuiOutput::INSTALL_BUILD_SUCCESS,
            TuiOutput::FOOTER_SITE_READY,
            TuiOutput::FOOTER_READY_TO_BUILD,
          ]),
        ],
      ],

      'Install with build flag and requirements of check-requirements command check fails' => [
        'command_inputs' => self::tuiOptions([
          InstallCommand::OPTION_NO_INTERACTION => TRUE,
          InstallCommand::OPTION_BUILD => TRUE,
        ]),
        'install_executable_finder_find_callback' => fn(string $command): string => '/usr/bin/' . $command,
        'build_runner_exit_callback' => TuiOutput::buildRunnerSuccess(),
        'check_requirements_runner_exit_callback' => TuiOutput::checkRequirementsFailure(),
        'expect_failure' => TRUE,
        'output_assertions' => [
          ...TuiOutput::present([
            TuiOutput::INSTALL_STARTING,
            TuiOutput::INSTALL_DOWNLOADING,
            TuiOutput::INSTALL_CUSTOMIZING,
            TuiOutput::INSTALL_PREPARING_DESTINATION,
            TuiOutput::INSTALL_COPYING_FILES,
            TuiOutput::INSTALL_PREPARING_DEMO,
            TuiOutput::INSTALL_BUILDING,
            TuiOutput::BUILD_CHECKING_REQUIREMENTS,
            TuiOutput::CHECK_REQUIREMENTS_MISSING,
          ]),
          ...TuiOutput::absent([
            TuiOutput::CHECK_REQUIREMENTS_ALL_MET,
            TuiOutput::INSTALL_BUILD_SUCCESS,
          ]),
        ],
      ],
    ];
  }

}
