<?php

namespace DrevOps\VortexInstaller\Tests\Helpers;

use DrevOps\VortexInstaller\Runner\RunnerInterface;

class TuiOutput {

  // Build command output.
  const BUILD_ASSEMBLE_DOCKER = 'resolving provenance for metadata';

  const BUILD_ASSEMBLE_COMPOSER = 'Downloading drupal/core';

  const BUILD_ASSEMBLE_YARN = 'yarn install';

  const BUILD_PROVISION_START = '[INFO] Started site provisioning.';

  const BUILD_PROVISION_END = '[INFO] Finished site provisioning';

  const BUILD_PROVISION_PROJECT_INFO = '[INFO] Project information';

  const BUILD_PROVISION_TYPE_DB = '[INFO] Provisioning site from the database dump file.';

  const BUILD_PROVISION_TYPE_PROFILE = '[INFO] Provisioning site from the profile.';

  const CHECK_REQUIREMENTS_CHECKING_DOCKER = 'Checking Docker';

  const CHECK_REQUIREMENTS_CHECKING_DOCKER_COMPOSE = 'Checking Docker Compose';

  const CHECK_REQUIREMENTS_CHECKING_AHOY = 'Checking Ahoy';

  const CHECK_REQUIREMENTS_CHECKING_PYGMY = 'Checking Pygmy';

  const CHECK_REQUIREMENTS_DOCKER_AVAILABLE = 'Docker is available';

  const CHECK_REQUIREMENTS_DOCKER_MISSING = 'Docker is missing';

  const CHECK_REQUIREMENTS_DOCKER_COMPOSE_AVAILABLE = 'Docker Compose is available';

  const CHECK_REQUIREMENTS_DOCKER_COMPOSE_MISSING = 'Docker Compose is missing';

  const CHECK_REQUIREMENTS_AHOY_AVAILABLE = 'Ahoy is available';

  const CHECK_REQUIREMENTS_AHOY_MISSING = 'Ahoy is missing';

  const CHECK_REQUIREMENTS_PYGMY_RUNNING = 'Pygmy is running';

  const CHECK_REQUIREMENTS_PYGMY_NOT_RUNNING = 'Pygmy is not running';

  const CHECK_REQUIREMENTS_ALL_MET = 'All requirements met';

  const CHECK_REQUIREMENTS_MISSING = 'Missing requirements';

  const INSTALL_STARTING = 'Starting project installation';

  const INSTALL_DOWNLOADING = 'Downloading Vortex';

  const INSTALL_CUSTOMIZING = 'Customizing Vortex for your project';

  const INSTALL_PREPARING_DESTINATION = 'Preparing destination directory';

  const INSTALL_COPYING_FILES = 'Copying files to the destination directory';

  const INSTALL_PREPARING_DEMO = 'Preparing demo content';

  const INSTALL_BUILDING = 'Building site';

  const INSTALL_BUILD_SUCCESS = 'Build completed successfully';

  const INSTALL_BUILD_FAILED = 'Build failed';

  const INSTALL_LOGIN = 'Login:';

  const INSTALL_LOG_FILE = 'Log file:';

  const INSTALL_NEXT_STEPS = 'Next steps:';

  // Handler-specific post-build messages.
  const POSTBUILD_SETUP_GHA = 'Setup GitHub Actions:';

  const POSTBUILD_SETUP_CIRCLECI = 'Setup CircleCI:';

  const POSTBUILD_SETUP_ACQUIA = 'Setup Acquia hosting:';

  const POSTBUILD_SETUP_LAGOON = 'Setup Lagoon hosting:';

  const INSTALL_EXIT_CODE = 'Exit code:';

  // Footer: Site is ready (build succeeded).
  const FOOTER_SITE_READY = 'Site is ready';

  const FOOTER_GET_SITE_INFO = 'Get site info:';

  const FOOTER_AHOY_LOGIN = 'ahoy login';

  const FOOTER_AHOY_INFO = 'ahoy info';

  // Footer: Ready to build (build skipped).
  const FOOTER_READY_TO_BUILD = 'Ready to build';

  const FOOTER_BUILD_THE_SITE = 'Build the site:';

  const FOOTER_AHOY_BUILD = 'ahoy build';

  const FOOTER_AHOY_BUILD_PROFILE = 'VORTEX_PROVISION_TYPE=profile ahoy build';

  const FOOTER_EXPORT_DATABASE = 'Export database after build:';

  const FOOTER_AHOY_EXPORT_DB = 'ahoy export-db db.sql';

  // Footer: Build encountered errors (build failed).
  const FOOTER_BUILD_ERRORS = 'Build encountered errors';

  const FOOTER_BUILD_FAILED_MESSAGE = 'Vortex was installed, but the build process failed.';

  const FOOTER_TROUBLESHOOTING = 'Troubleshooting:';

  const FOOTER_CHECK_LOGS = 'ahoy logs';

  const FOOTER_DIAGNOSTICS = 'ahoy doctor';

  // Footer: Installation complete.
  const FOOTER_FINISHED_INSTALLING = 'Finished installing Vortex';

  const FOOTER_GIT_ADD = 'git add -A';

  const FOOTER_GIT_COMMIT = 'git commit -m "Initial commit."';

  const INSTALL_ERROR_MISSING_GIT = 'Installation failed with an error: Missing required command: git.';

  const INSTALL_ERROR_MISSING_TAR = 'Installation failed with an error: Missing required command: tar.';

  const INSTALL_ERROR_MISSING_COMPOSER = 'Installation failed with an error: Missing required command: Composer.';

  const INSTALL_ERROR_DOWNLOAD_FAILED = 'Installation failed with an error: Failed to download Vortex.';

  const BUILD_CHECKING_REQUIREMENTS = 'Checking requirements';

  const BUILD_BUILDING_SITE = 'Building site';

  const BUILD_BUILD_COMPLETED = 'Build completed';

  const BUILD_BUILD_FAILED = 'Build failed';

  const BUILD_EXPORT_DATABASE = 'Export database:';

  const BUILD_SITE_URL = 'Site URL:';

  const BUILD_REVIEW_DOCS = 'Review hosting/provisioning docs';

  // Check requirements labels.
  const CHECK_REQUIREMENTS_PRESENT_LABEL = 'Present:';

  const CHECK_REQUIREMENTS_MISSING_LABEL = 'Missing:';

  const CHECK_REQUIREMENTS_UNKNOWN = 'Unknown requirements:';

  const CHECK_REQUIREMENTS_AVAILABLE = 'Available: docker, docker-compose, ahoy, pygmy';

  const DESTINATION_NOT_EXIST = 'Destination directory does not exist:';

  const DESTINATION_MUST_BE_STRING = 'Destination must be a string.';

  /**
   * Mark constants as present (should contain in output).
   *
   * @param array<string> $constants
   *   Array of constant values.
   *
   * @return array<string>
   *   Array with '* ' prefix added to each constant.
   */
  public static function present(array $constants): array {
    return array_map(fn($c) => '* ' . $c, $constants);
  }

  /**
   * Mark constants as absent (should NOT contain in output).
   *
   * @param array<string> $constants
   *   Array of constant values.
   *
   * @return array<string>
   *   Array with '! ' prefix added to each constant.
   */
  public static function absent(array $constants): array {
    return array_map(fn($c) => '! ' . $c, $constants);
  }

  /**
   * Echo constants as output lines.
   *
   * @param array<string> $constants
   *   Array of constant values to echo.
   */
  public static function echo(array $constants): void {
    foreach ($constants as $constant) {
      echo $constant . PHP_EOL;
    }
  }

  /**
   * Create a successful build runner callback.
   *
   * Simulates a successful build with database provisioning.
   *
   * @return \Closure
   *   Closure that echoes build output and returns success exit code.
   */
  public static function buildRunnerSuccess(): \Closure {
    return function (string $command): int {
      self::echo([
        self::BUILD_ASSEMBLE_DOCKER,
        self::BUILD_ASSEMBLE_COMPOSER,
        self::BUILD_ASSEMBLE_YARN,
        self::BUILD_PROVISION_START,
        self::BUILD_PROVISION_PROJECT_INFO,
        self::BUILD_PROVISION_TYPE_DB,
        self::BUILD_PROVISION_END,
      ]);
      return RunnerInterface::EXIT_SUCCESS;
    };
  }

  /**
   * Create a successful build runner callback with profile provisioning.
   *
   * Simulates a successful build using install profile instead of database.
   *
   * @return \Closure
   *   Closure that echoes build output and returns success exit code.
   */
  public static function buildRunnerSuccessProfile(): \Closure {
    return function (string $command): int {
      self::echo([
        self::BUILD_ASSEMBLE_DOCKER,
        self::BUILD_ASSEMBLE_COMPOSER,
        self::BUILD_ASSEMBLE_YARN,
        self::BUILD_PROVISION_START,
        self::BUILD_PROVISION_PROJECT_INFO,
        self::BUILD_PROVISION_TYPE_PROFILE,
        self::BUILD_PROVISION_END,
      ]);
      return RunnerInterface::EXIT_SUCCESS;
    };
  }

  /**
   * Create a failed build runner callback.
   *
   * Simulates a build that starts but fails during provisioning.
   *
   * @return \Closure
   *   Closure that echoes partial build output and returns failure exit code.
   */
  public static function buildRunnerFailure(): \Closure {
    return function (string $command): int {
      self::echo([
        self::BUILD_ASSEMBLE_DOCKER,
        self::BUILD_ASSEMBLE_COMPOSER,
        self::BUILD_ASSEMBLE_YARN,
        self::BUILD_PROVISION_START,
      ]);
      return RunnerInterface::EXIT_FAILURE;
    };
  }

  /**
   * Create a successful check requirements callback.
   *
   * Simulates all requirements checks passing.
   *
   * @return \Closure
   *   Closure that echoes requirements check output and returns success.
   */
  public static function checkRequirementsSuccess(): \Closure {
    return function (string $command): int {
      self::echo([
        self::CHECK_REQUIREMENTS_CHECKING_DOCKER,
        self::CHECK_REQUIREMENTS_DOCKER_AVAILABLE,
        self::CHECK_REQUIREMENTS_CHECKING_DOCKER_COMPOSE,
        self::CHECK_REQUIREMENTS_DOCKER_COMPOSE_AVAILABLE,
        self::CHECK_REQUIREMENTS_CHECKING_AHOY,
        self::CHECK_REQUIREMENTS_AHOY_AVAILABLE,
        self::CHECK_REQUIREMENTS_CHECKING_PYGMY,
        self::CHECK_REQUIREMENTS_PYGMY_RUNNING,
        self::CHECK_REQUIREMENTS_ALL_MET,
      ]);
      return RunnerInterface::EXIT_SUCCESS;
    };
  }

  /**
   * Create a failed check requirements callback.
   *
   * Simulates requirements checks with missing tools.
   *
   * @return \Closure
   *   Closure that echoes requirements check output and returns failure.
   */
  public static function checkRequirementsFailure(): \Closure {
    return function (string $command): int {
      self::echo([
        self::CHECK_REQUIREMENTS_CHECKING_DOCKER,
        self::CHECK_REQUIREMENTS_DOCKER_AVAILABLE,
        self::CHECK_REQUIREMENTS_CHECKING_DOCKER_COMPOSE,
        self::CHECK_REQUIREMENTS_DOCKER_COMPOSE_MISSING,
        self::CHECK_REQUIREMENTS_MISSING,
      ]);
      return RunnerInterface::EXIT_FAILURE;
    };
  }

}
