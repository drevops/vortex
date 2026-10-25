<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the Storybook script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class StorybookTest extends UnitTestCase {

  protected const DRUSH_GENERATE = './vendor/bin/drush -y storybook:generate-all-stories --omit-server-url --force';

  protected const WATCH = "env SHELL=/bin/sh './web/themes/custom/your_site_theme/node_modules/.bin/chokidar' './web/themes/custom/your_site_theme/components/**/*.stories.twig' -c './vendor/bin/drush -y storybook:generate-all-stories --omit-server-url --force'";

  protected const PKILL_SERVER = "pkill -f '[n]ode .*storybook dev'";

  protected const PKILL_WATCHER = "pkill -f '[n]ode .*chokidar .*storybook:generate-all-stories'";

  protected const SERVER = "exec env STORYBOOK_SERVER_URL='http://star-wars.docker.amazee.io' npm --prefix='./web/themes/custom/your_site_theme' run 'storybook' -- --port '6006' --quiet --ci";

  protected function setUp(): void {
    parent::setUp();

    self::envUnsetPrefix('VORTEX_STORYBOOK_');
    self::envUnsetMultiple(['VORTEX_HOST_STORYBOOK_PORT', 'STORYBOOK_SERVER_URL', 'WEBROOT']);

    $this->envSetMultiple([
      'DRUPAL_THEME' => 'your_site_theme',
      'LOCALDEV_URL' => 'star-wars.docker.amazee.io',
    ]);
  }

  #[DataProvider('dataProviderStoriesAndBuild')]
  public function testStoriesAndBuild(array $argv, array $env_vars, array $commands, array $expected): void {
    $GLOBALS['argv'] = $argv;
    $this->envSetMultiple($env_vars);
    $this->mockPassthruMultiple($commands);

    $output = $this->runScript('src/vortex-storybook');

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderStoriesAndBuild(): array {
    return [
      'generate stories' => [
        ['vortex-storybook', 'stories'],
        [],
        [
          ['cmd' => self::DRUSH_GENERATE],
        ],
        [
          '* Generating stories.',
          '* Generated stories.',
        ],
      ],

      'generate and watch stories' => [
        ['vortex-storybook', 'stories', '--watch'],
        [],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => self::WATCH],
        ],
        [
          '* Generated stories.',
        ],
      ],

      'build the library' => [
        ['vortex-storybook', 'build'],
        // A URL set for the development server does not reach the static
        // build.
        ['STORYBOOK_SERVER_URL' => 'http://localhost:6006'],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => "env STORYBOOK_SERVER_URL='' npm --prefix='./web/themes/custom/your_site_theme' run 'storybook-build'"],
        ],
        [
          '* [INFO] Started Storybook build.',
          '* Generated stories.',
          '* Building the Storybook application.',
          '* Built the Storybook application.',
          '* Storybook library: http://star-wars.docker.amazee.io/storybook',
          '* Finished Storybook build.',
        ],
      ],

      'build the library from custom locations' => [
        ['vortex-storybook', 'build'],
        [
          'VORTEX_STORYBOOK_DIR' => './frontend/storybook',
          'VORTEX_STORYBOOK_BUILD_SCRIPT' => 'build-library',
          'VORTEX_STORYBOOK_BUILD_SERVER_URL' => 'https://cms.example.com',
          'VORTEX_STORYBOOK_LIBRARY_PATH' => '/components',
          'LOCALDEV_URL' => 'https://star-wars.docker.amazee.io, star-wars-alt.docker.amazee.io',
        ],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => "env STORYBOOK_SERVER_URL='https://cms.example.com' npm --prefix='./frontend/storybook' run 'build-library'"],
        ],
        [
          '* Built the Storybook application.',
          '* Storybook library: https://star-wars.docker.amazee.io/components',
        ],
      ],

      'build the library without a local URL' => [
        ['vortex-storybook', 'build'],
        ['LOCALDEV_URL' => ''],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => "env STORYBOOK_SERVER_URL='' npm --prefix='./web/themes/custom/your_site_theme' run 'storybook-build'"],
        ],
        [
          '* Storybook library: /storybook',
        ],
      ],
    ];
  }

  #[DataProvider('dataProviderFailure')]
  public function testFailure(array $argv, array $commands, string $expected): void {
    $GLOBALS['argv'] = $argv;
    $this->mockPassthruMultiple($commands);

    $this->runScriptError('src/vortex-storybook', $expected);
  }

  public static function dataProviderFailure(): array {
    return [
      'stories generation fails' => [
        ['vortex-storybook', 'stories'],
        [
          ['cmd' => self::DRUSH_GENERATE, 'result_code' => 1],
        ],
        'Drush command failed: storybook:generate-all-stories --omit-server-url --force',
      ],

      'watcher fails' => [
        ['vortex-storybook', 'stories', '--watch'],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => self::WATCH, 'result_code' => 1],
        ],
        'Generated stories.',
      ],

      'build fails' => [
        ['vortex-storybook', 'build'],
        [
          ['cmd' => self::DRUSH_GENERATE],
          ['cmd' => "env STORYBOOK_SERVER_URL='' npm --prefix='./web/themes/custom/your_site_theme' run 'storybook-build'", 'result_code' => 1],
        ],
        'Failed to build the Storybook application.',
      ],

      'unknown action' => [
        ['vortex-storybook', 'publish'],
        [],
        "Unknown action 'publish'. Use 'dev', 'build' or 'stories'.",
      ],

      'missing action' => [
        ['vortex-storybook'],
        [],
        "Unknown action ''. Use 'dev', 'build' or 'stories'.",
      ],
    ];
  }

  #[DataProvider('dataProviderDev')]
  public function testDev(array $argv, array $env_vars, string $server_command, string $url, array $requests, array $statuses, int $close_exit_code, ?int $quit_code, array $expected): void {
    $GLOBALS['argv'] = $argv;
    $this->envSetMultiple($env_vars);

    $this->mockPassthruMultiple([
      ['cmd' => self::PKILL_SERVER, 'result_code' => 1],
      ['cmd' => self::PKILL_WATCHER, 'result_code' => 1],
      ['cmd' => self::DRUSH_GENERATE],
    ]);
    $this->mockProcesses(['exec ' . self::WATCH, $server_command], $statuses, [$close_exit_code, 0]);
    $this->mockRequestMultiple(array_map(fn(array $response): array => ['url' => $url, 'method' => 'GET', 'response' => $response], $requests));
    $this->mockSleep();

    if ($quit_code === NULL) {
      $output = $this->runScript('src/vortex-storybook');
    }
    else {
      try {
        $this->runScript('src/vortex-storybook', $quit_code);
        $output = '';
      }
      catch (QuitErrorException $e) {
        $output = $e->getOutput();
        $this->assertStringContainsOrNot($output, $expected);

        throw $e;
      }
    }

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderDev(): array {
    $ready = ['status' => 200];
    $refused = ['ok' => FALSE, 'status' => 0, 'error' => 'Connection refused'];

    return [
      'start the development server' => [
        ['vortex-storybook', 'dev'],
        ['VORTEX_HOST_STORYBOOK_PORT' => '55197'],
        self::SERVER,
        'http://localhost:6006',
        [$ready],
        [],
        0,
        NULL,
        [
          '* [INFO] Started Storybook development server.',
          '* Generated stories.',
          '* Starting the Storybook development server.',
          '* Storybook is ready at http://localhost:55197',
          '! Storybook development server did not start.',
          '! Storybook development server exited.',
        ],
      ],

      'start the development server with custom settings' => [
        ['vortex-storybook', 'dev', '--smoke-test'],
        [
          'VORTEX_STORYBOOK_PORT' => '7007',
          'VORTEX_STORYBOOK_DEV_SCRIPT' => 'dev',
          'STORYBOOK_SERVER_URL' => 'https://cms.example.com',
        ],
        "exec env STORYBOOK_SERVER_URL='https://cms.example.com' npm --prefix='./web/themes/custom/your_site_theme' run 'dev' -- --port '7007' --quiet --ci '--smoke-test'",
        'http://localhost:7007',
        [$ready],
        [],
        0,
        NULL,
        [
          '* Storybook is ready at http://localhost:7007',
        ],
      ],

      'development server becomes ready after a retry' => [
        ['vortex-storybook', 'dev'],
        [],
        self::SERVER,
        'http://localhost:6006',
        [$refused, $ready],
        [['running' => TRUE, 'exitcode' => -1]],
        0,
        NULL,
        [
          '* Storybook is ready at http://localhost:6006',
        ],
      ],

      'development server fails after it was ready' => [
        ['vortex-storybook', 'dev'],
        [],
        self::SERVER,
        'http://localhost:6006',
        [$ready],
        [],
        2,
        2,
        [
          '* Storybook is ready at http://localhost:6006',
          '! Storybook development server did not start.',
        ],
      ],

      'development server does not start' => [
        ['vortex-storybook', 'dev'],
        [],
        self::SERVER,
        'http://localhost:6006',
        [$refused],
        [['running' => FALSE, 'exitcode' => 1]],
        -1,
        1,
        [
          '* Storybook development server did not start.',
          '! Storybook is ready',
        ],
      ],

      'development server exits before it is ready' => [
        ['vortex-storybook', 'dev', '--smoke-test'],
        [],
        self::SERVER . " '--smoke-test'",
        'http://localhost:6006',
        [$refused],
        [['running' => FALSE, 'exitcode' => 0]],
        -1,
        NULL,
        [
          '* Storybook development server exited.',
          '! Storybook is ready',
          '! Storybook development server did not start.',
        ],
      ],
    ];
  }

  public function testDevServerFailsToLaunch(): void {
    $GLOBALS['argv'] = ['vortex-storybook', 'dev'];

    $this->mockPassthruMultiple([
      ['cmd' => self::PKILL_SERVER, 'result_code' => 1],
      ['cmd' => self::PKILL_WATCHER, 'result_code' => 1],
      ['cmd' => self::DRUSH_GENERATE],
    ]);
    $this->mockProcesses(['exec ' . self::WATCH, self::SERVER], [], [], [1]);

    $this->runScriptError('src/vortex-storybook', 'Unable to start the Storybook development server.');
  }

  /**
   * Mock the process control functions used to run background processes.
   *
   * @param array<int, string> $commands
   *   Commands expected by proc_open(), in call order.
   * @param array<int, array{running: bool, exitcode: int}> $statuses
   *   Values returned by proc_get_status(), in call order.
   * @param array<int, int> $close_exit_codes
   *   Values returned by proc_close(), in call order.
   * @param array<int, int> $failed
   *   Positions of the proc_open() calls that fail to start a process.
   */
  protected function mockProcesses(array $commands, array $statuses, array $close_exit_codes, array $failed = []): void {
    $this->addMockResponses('proc_open', array_map(fn(string $command, int $index): array => ['cmd' => $command, 'fail' => in_array($index, $failed, TRUE)], $commands, array_keys($commands)));
    $this->registerMock('proc_open', 'DrevOps\\VortexTooling', function (string $command): mixed {
      /** @var array{cmd: string, fail: bool} $response */
      $response = $this->getNextMockResponse('proc_open');

      if ($response['cmd'] !== $command) {
        throw new \RuntimeException(sprintf('proc_open() called with unexpected command. Expected "%s", got "%s".', $response['cmd'], $command));
      }

      return $response['fail'] ? FALSE : fopen('php://memory', 'r');
    });

    $this->addMockResponses('proc_get_status', $statuses);
    $this->registerMock('proc_get_status', 'DrevOps\\VortexTooling', fn(): array => $this->getNextMockResponse('proc_get_status'));

    $this->addMockResponses('proc_close', array_map(fn(int $code): array => ['code' => $code], $close_exit_codes));
    $this->registerMock('proc_close', 'DrevOps\\VortexTooling', function (): int {
      /** @var array{code: int} $response */
      $response = $this->getNextMockResponse('proc_close');

      return $response['code'];
    });

    $this->registerMock('proc_terminate', 'DrevOps\\VortexTooling', fn(): bool => TRUE);
  }

}
