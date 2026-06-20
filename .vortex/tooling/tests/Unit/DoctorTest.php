<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for doctor script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class DoctorTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    // Disable all checks by default; tests enable specific ones.
    $this->envSetMultiple([
      'VORTEX_DOCTOR_CHECK_TOOLS' => '0',
      'VORTEX_DOCTOR_CHECK_PORT' => '0',
      'VORTEX_DOCTOR_CHECK_PYGMY' => '0',
      'VORTEX_DOCTOR_CHECK_CONTAINERS' => '0',
      'VORTEX_DOCTOR_CHECK_SSH' => '0',
      'VORTEX_DOCTOR_CHECK_WEBSERVER' => '0',
      'VORTEX_DOCTOR_CHECK_BOOTSTRAP' => '0',
      'HOME' => self::$tmp,
    ]);
  }

  #[DataProvider('dataProviderDoctor')]
  public function testDoctor(array $env_vars, array $mocks, array $expected, bool $expect_error = FALSE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    foreach ($mocks as $mock) {
      if (isset($mock['shell_exec'])) {
        $this->mockShellExec($mock['shell_exec']);
      }
      elseif (isset($mock['request'])) {
        $this->mockRequestMultiple([$mock['request']]);
      }
      else {
        $this->mockPassthru($mock);
      }
    }

    if ($expect_error) {
      try {
        $this->runScript('src/doctor', 1);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }
        throw $e;
      }
      return;
    }

    $output = $this->runScript('src/doctor');

    $this->assertStringContainsOrNot($output, $expected);
  }

  public function testDoctorInfo(): void {
    $GLOBALS['argv'] = ['doctor', 'info'];

    $os_cmd = PHP_OS === 'Darwin' ? 'sw_vers' : 'lsb_release -a 2>/dev/null';

    // shell_exec calls in order:
    // 1. whoami (sanitize docker_path), 2. docker info,
    // 3. whoami (sanitize docker_info), 4. whoami (sanitize dc_path),
    // 5. whoami (sanitize pygmy_path), 6. whoami (sanitize ahoy_path).
    $this->mockShellExecMultiple([
      ['value' => 'testuser'],
      ['value' => 'Docker Server Version: 20.10.0'],
      ['value' => 'testuser'],
      ['value' => 'testuser'],
      ['value' => 'testuser'],
      ['value' => 'testuser'],
    ]);

    // Passthru calls in order.
    $this->mockPassthru(['cmd' => $os_cmd, 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker -v', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker compose version 2>/dev/null || echo "Docker Compose V2 is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker-compose version 2>/dev/null || echo "Docker Compose V1 is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'pygmy version 2>/dev/null || echo "Pygmy is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'ahoy --version 2>/dev/null || echo "Ahoy is not installed."', 'result_code' => 0]);

    try {
      $this->runScript('src/doctor', 0);
    }
    catch (QuitSuccessException $e) {
      $this->assertStringContainsOrNot($e->getOutput(), [
        '* System information report',
        '* OPERATING SYSTEM',
        '* DOCKER',
        '* Path to binary:',
        '* DOCKER COMPOSE V2',
        '* DOCKER-COMPOSE V1',
        '* PYGMY',
        '* AHOY',
        '! Docker is not running',
      ]);

      throw $e;
    }
  }

  public function testDoctorInfoDockerNotRunning(): void {
    $GLOBALS['argv'] = ['doctor', 'info'];

    $os_cmd = PHP_OS === 'Darwin' ? 'sw_vers' : 'lsb_release -a 2>/dev/null';

    // shell_exec calls — docker info returns empty (docker not running).
    // No whoami call for docker_info since sanitize_system_info is not called.
    $this->mockShellExecMultiple([
      ['value' => 'testuser'],
      ['value' => ''],
      ['value' => 'testuser'],
      ['value' => 'testuser'],
      ['value' => 'testuser'],
    ]);

    $this->mockPassthru(['cmd' => $os_cmd, 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker -v', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker compose version 2>/dev/null || echo "Docker Compose V2 is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'docker-compose version 2>/dev/null || echo "Docker Compose V1 is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'pygmy version 2>/dev/null || echo "Pygmy is not installed."', 'result_code' => 0]);
    $this->mockPassthru(['cmd' => 'ahoy --version 2>/dev/null || echo "Ahoy is not installed."', 'result_code' => 0]);

    try {
      $this->runScript('src/doctor', 0);
    }
    catch (QuitSuccessException $e) {
      $this->assertStringContainsOrNot($e->getOutput(), [
        '* System information report',
        '* Docker is not running or not installed.',
        '* DOCKER COMPOSE V2',
      ]);

      throw $e;
    }
  }

  public static function dataProviderDoctor(): array {
    $container_cmd = fn(string $s): array => ['cmd' => sprintf("docker compose ps --status=running --services 2>/dev/null | grep -q '%s'", $s), 'result_code' => 0];
    $container_fail = fn(string $s): array => ['cmd' => sprintf("docker compose ps --status=running --services 2>/dev/null | grep -q '%s'", $s), 'result_code' => 1];
    $containers_running = fn(): array => [$container_cmd('cli'), $container_cmd('php'), $container_cmd('nginx'), $container_cmd('database')];

    $pygmy_status_cmd = ['cmd' => 'pygmy status 2>/dev/null | tr -d "\\000" > /tmp/vortex_pygmy_status.txt', 'result_code' => 0];
    $pygmy_service_ok = fn(string $s): array => ['cmd' => 'grep -q "' . $s . ': Running" /tmp/vortex_pygmy_status.txt', 'result_code' => 0];

    $ssh_file = '/home/testuser/.ssh/id_rsa';
    $ssh_pygmy_cmd = fn(int $code): array => ['cmd' => sprintf("pygmy status 2>&1 | grep -q '%s'", $ssh_file), 'result_code' => $code];
    $ssh_volume_cmd = fn(int $code): array => ['cmd' => 'docker compose exec -T cli bash -c \'grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent\'', 'result_code' => $code];
    $ssh_key_cmd = fn(int $code): array => ['cmd' => 'docker compose exec -T cli bash -c "ssh-add -L | grep -q \'ssh-rsa\'"', 'result_code' => $code];

    $webserver_url = 'test.docker.amazee.io';
    $web_request = fn(int $status, string $body = ''): array => ['request' => ['url' => $webserver_url, 'method' => 'GET', 'response' => ['status' => $status, 'body' => $body]]];

    return [
      'all checks disabled' => [
        [],
        [],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] All required checks have passed.',
          '! All required tools are present.',
          '! Port 80',
          '! Pygmy',
          '! containers',
          '! SSH',
          '! Web server',
          '! Bootstrapped',
        ],
      ],

      'tools all present' => [
        ['VORTEX_DOCTOR_CHECK_TOOLS' => '1'],
        [],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] All required tools are present.',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'containers all running' => [
        ['VORTEX_DOCTOR_CHECK_CONTAINERS' => '1'],
        $containers_running(),
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] All containers are running',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'container not running' => [
        ['VORTEX_DOCTOR_CHECK_CONTAINERS' => '1'],
        [$container_fail('cli')],
        [
          '* [INFO] Checking project requirements',
          '* [FAIL] cli container is not running.',
          "* Run 'ahoy up'.",
          "* Run 'ahoy logs cli' to see error logs.",
        ],
        TRUE,
      ],

      'pygmy all running' => [
        ['VORTEX_DOCTOR_CHECK_PYGMY' => '1'],
        [
          $pygmy_status_cmd,
          $pygmy_service_ok('amazeeio-ssh-agent'),
          $pygmy_service_ok('amazeeio-mailhog'),
          $pygmy_service_ok('amazeeio-haproxy'),
          $pygmy_service_ok('amazeeio-dnsmasq'),
        ],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] Pygmy is running.',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'pygmy service not running' => [
        ['VORTEX_DOCTOR_CHECK_PYGMY' => '1'],
        [
          $pygmy_status_cmd,
          ['cmd' => 'grep -q "amazeeio-ssh-agent: Running" /tmp/vortex_pygmy_status.txt', 'result_code' => 1],
        ],
        ["* [FAIL] Pygmy service amazeeio-ssh-agent is not running. Run 'pygmy up' or 'pygmy restart' to fix."],
        TRUE,
      ],

      'ssh all passing' => [
        [
          'VORTEX_DOCTOR_CHECK_SSH' => '1',
          'VORTEX_SSH_FILE' => $ssh_file,
        ],
        [$ssh_pygmy_cmd(0), $ssh_volume_cmd(0), $ssh_key_cmd(0)],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] SSH key is available within CLI container.',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'ssh key not in pygmy' => [
        [
          'VORTEX_DOCTOR_CHECK_SSH' => '1',
          'VORTEX_SSH_FILE' => $ssh_file,
        ],
        [$ssh_pygmy_cmd(1), $ssh_volume_cmd(0)],
        [
          '* [INFO] Checking project requirements',
          '* [FAIL] SSH key is not added to pygmy.',
          "* The SSH key will not be available in CLI container. Run 'pygmy restart' and then 'ahoy up'",
          '! [ OK ] SSH key is available within CLI container.',
          '* [ OK ] All required checks have passed.',
        ],
      ],

      'ssh volume not mounted' => [
        [
          'VORTEX_DOCTOR_CHECK_SSH' => '1',
          'VORTEX_SSH_FILE' => $ssh_file,
        ],
        [$ssh_pygmy_cmd(0), $ssh_volume_cmd(1)],
        [
          '* [INFO] Checking project requirements',
          '* [FAIL] SSH key volume is not mounted into CLI container.',
          '* Make sure that your "docker-compose.yml" has the following lines for CLI service:',
          '* volumes_from:',
          '* container:amazeeio-ssh-agent',
          "* After adding these lines, run 'ahoy up'.",
          '! [ OK ] SSH key is available within CLI container.',
          '* [ OK ] All required checks have passed.',
        ],
      ],

      'ssh key not in container' => [
        [
          'VORTEX_DOCTOR_CHECK_SSH' => '1',
          'VORTEX_SSH_FILE' => $ssh_file,
        ],
        [$ssh_pygmy_cmd(0), $ssh_volume_cmd(0), $ssh_key_cmd(1)],
        [
          '* [INFO] Checking project requirements',
          "* [FAIL] SSH key was not added into container. Run 'pygmy restart'.",
          '! [ OK ] SSH key is available within CLI container.',
          '* [ OK ] All required checks have passed.',
        ],
      ],

      'webserver accessible' => [
        ['VORTEX_DOCTOR_CHECK_WEBSERVER' => '1'],
        [
          ['shell_exec' => $webserver_url],
          $web_request(200),
        ],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] Web server is running and accessible at http://' . $webserver_url . '.',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'webserver not accessible' => [
        ['VORTEX_DOCTOR_CHECK_WEBSERVER' => '1'],
        [
          ['shell_exec' => $webserver_url],
          $web_request(500),
        ],
        ['* [FAIL] Web server is not accessible at http://' . $webserver_url . '.'],
        TRUE,
      ],

      'bootstrap success' => [
        [
          'VORTEX_DOCTOR_CHECK_WEBSERVER' => '1',
          'VORTEX_DOCTOR_CHECK_BOOTSTRAP' => '1',
        ],
        [
          ['shell_exec' => $webserver_url],
          $web_request(200, '<html><meta charset="utf-8"></html>'),
        ],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] Web server is running and accessible at http://' . $webserver_url . '.',
          '* [ OK ] Bootstrapped website at http://' . $webserver_url . '.',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'bootstrap failure' => [
        [
          'VORTEX_DOCTOR_CHECK_WEBSERVER' => '1',
          'VORTEX_DOCTOR_CHECK_BOOTSTRAP' => '1',
        ],
        [
          ['shell_exec' => $webserver_url],
          $web_request(200, '<html>no bootstrap marker</html>'),
        ],
        ['* [FAIL] Website is running, but cannot be bootstrapped.'],
        TRUE,
      ],

      'webserver empty url' => [
        ['VORTEX_DOCTOR_CHECK_WEBSERVER' => '1'],
        [

          ['shell_exec' => ''],
        ],
        [
          '* [INFO] Checking project requirements',
          '! Web server is running',
          '! Web server is not accessible',
          '* [ OK ] All required checks have passed.',
        ],
      ],

      'preflight mode' => [
        [
          'VORTEX_DOCTOR_CHECK_PREFLIGHT' => '1',
        ],
        [],
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],

      'minimal check mode' => [
        [
          'VORTEX_DOCTOR_CHECK_MINIMAL' => '1',
          'VORTEX_DOCTOR_CHECK_TOOLS' => '1',
          'VORTEX_DOCTOR_CHECK_PORT' => '1',
          'VORTEX_DOCTOR_CHECK_PYGMY' => '1',
          'VORTEX_DOCTOR_CHECK_CONTAINERS' => '1',
          'VORTEX_DOCTOR_CHECK_SSH' => '1',
          'VORTEX_DOCTOR_CHECK_WEBSERVER' => '1',
          'VORTEX_DOCTOR_CHECK_BOOTSTRAP' => '1',
        ],
        $containers_running(),
        [
          '* [INFO] Checking project requirements',
          '* [ OK ] All required tools are present.',
          '* [ OK ] All containers are running',
          '! Pygmy is running.',
          '! SSH key',
          '! Web server',
          '! Bootstrapped',
          '* [ OK ] All required checks have passed.',
          '! [FAIL]',
        ],
      ],
    ];
  }

}
