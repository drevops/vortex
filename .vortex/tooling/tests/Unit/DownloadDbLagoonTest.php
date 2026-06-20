<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class DownloadDbLagoonTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_PROJECT', 'myproject');
    $this->envSet('LAGOON_PROJECT', 'myproject');
    $this->envSet('VORTEX_DOWNLOAD_DB_ENVIRONMENT', 'main');
    $this->envSet('VORTEX_DOWNLOAD_DB_SSH_FILE', '/home/user/.ssh/id_rsa');
    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_SSH_HOST', 'ssh.lagoon.amazeeio.cloud');
    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_SSH_PORT', '32222');
    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_DB_FILE', 'db.sql');
  }

  public function testMissingProject(): void {
    $this->envUnset('VORTEX_DOWNLOAD_DB_LAGOON_PROJECT');
    $this->envUnset('LAGOON_PROJECT');

    $this->runScriptError('src/download-db-lagoon', 'Missing required value for VORTEX_DOWNLOAD_DB_LAGOON_PROJECT, LAGOON_PROJECT');
  }

  public function testSuccess(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    $ssh_opts = [
      '-o', 'UserKnownHostsFile=/dev/null',
      '-o', 'StrictHostKeyChecking=no',
      '-o', 'LogLevel=error',
      '-o', 'IdentitiesOnly=yes',
      '-p', '32222',
      '-i', '/home/user/.ssh/id_rsa',
    ];
    $ssh_opts_escaped = implode(' ', array_map(escapeshellarg(...), $ssh_opts));
    $ssh_opts_string = implode(' ', $ssh_opts);

    $remote_file = 'db_20240101.sql';
    $ssh_user = 'myproject-main';
    $ssh_host = 'ssh.lagoon.amazeeio.cloud';

    $remote_cmd = <<<BASH
    if [ ! -f "/tmp/{$remote_file}" ] || [ "" = "1" ] ; then
      [ -n "db_*.sql" ] && rm -f "/tmp/db_*.sql" && echo "Removed previously created DB dumps."
      echo "      > Creating a database dump /tmp/{$remote_file}."
      /app/vendor/bin/drush --root=./web sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump='--disable-ssl --no-tablespaces' > "/tmp/{$remote_file}"
    else
      echo "      > Using existing dump /tmp/{$remote_file}."
    fi
    BASH;

    $this->mockPassthruMultiple([
      // setup-ssh.
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      // Ssh command.
      [
        'cmd' => sprintf(
          'ssh %s %s service=cli container=cli %s',
          $ssh_opts_escaped,
          escapeshellarg($ssh_user . '@' . $ssh_host),
          escapeshellarg($remote_cmd)
        ),
        'result_code' => 0,
      ],
      // Rsync command.
      [
        'cmd' => sprintf(
          'rsync -e %s %s %s',
          escapeshellarg('ssh ' . $ssh_opts_string),
          escapeshellarg($ssh_user . '@' . $ssh_host . ':/tmp/' . $remote_file),
          escapeshellarg($db_dir . '/db.sql')
        ),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-lagoon');

    $this->assertStringContainsString('Started database dump download from Lagoon.', $output);
    $this->assertStringContainsString('Discovering or creating a database dump on Lagoon.', $output);
    $this->assertStringContainsString('Downloading a database dump.', $output);
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testFreshDump(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    $this->envSet('VORTEX_DOWNLOAD_DB_FRESH', '1');

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    $ssh_opts = [
      '-o', 'UserKnownHostsFile=/dev/null',
      '-o', 'StrictHostKeyChecking=no',
      '-o', 'LogLevel=error',
      '-o', 'IdentitiesOnly=yes',
      '-p', '32222',
      '-i', '/home/user/.ssh/id_rsa',
    ];
    $ssh_opts_escaped = implode(' ', array_map(escapeshellarg(...), $ssh_opts));
    $ssh_opts_string = implode(' ', $ssh_opts);

    $remote_file = 'db_20240101.sql';
    $ssh_user = 'myproject-main';
    $ssh_host = 'ssh.lagoon.amazeeio.cloud';

    $remote_cmd = <<<BASH
    if [ ! -f "/tmp/{$remote_file}" ] || [ "1" = "1" ] ; then
      [ -n "db_*.sql" ] && rm -f "/tmp/db_*.sql" && echo "Removed previously created DB dumps."
      echo "      > Creating a database dump /tmp/{$remote_file}."
      /app/vendor/bin/drush --root=./web sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump='--disable-ssl --no-tablespaces' > "/tmp/{$remote_file}"
    else
      echo "      > Using existing dump /tmp/{$remote_file}."
    fi
    BASH;

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'ssh %s %s service=cli container=cli %s',
          $ssh_opts_escaped,
          escapeshellarg($ssh_user . '@' . $ssh_host),
          escapeshellarg($remote_cmd)
        ),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'rsync -e %s %s %s',
          escapeshellarg('ssh ' . $ssh_opts_string),
          escapeshellarg($ssh_user . '@' . $ssh_host . ':/tmp/' . $remote_file),
          escapeshellarg($db_dir . '/db.sql')
        ),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-lagoon');

    $this->assertStringContainsString('Database dump refresh requested.', $output);
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testRsyncFails(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    $ssh_opts = [
      '-o', 'UserKnownHostsFile=/dev/null',
      '-o', 'StrictHostKeyChecking=no',
      '-o', 'LogLevel=error',
      '-o', 'IdentitiesOnly=yes',
      '-p', '32222',
      '-i', '/home/user/.ssh/id_rsa',
    ];
    $ssh_opts_escaped = implode(' ', array_map(escapeshellarg(...), $ssh_opts));
    $ssh_opts_string = implode(' ', $ssh_opts);

    $remote_file = 'db_20240101.sql';
    $ssh_user = 'myproject-main';
    $ssh_host = 'ssh.lagoon.amazeeio.cloud';

    $remote_cmd = <<<BASH
    if [ ! -f "/tmp/{$remote_file}" ] || [ "" = "1" ] ; then
      [ -n "db_*.sql" ] && rm -f "/tmp/db_*.sql" && echo "Removed previously created DB dumps."
      echo "      > Creating a database dump /tmp/{$remote_file}."
      /app/vendor/bin/drush --root=./web sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump='--disable-ssl --no-tablespaces' > "/tmp/{$remote_file}"
    else
      echo "      > Using existing dump /tmp/{$remote_file}."
    fi
    BASH;

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'ssh %s %s service=cli container=cli %s',
          $ssh_opts_escaped,
          escapeshellarg($ssh_user . '@' . $ssh_host),
          escapeshellarg($remote_cmd)
        ),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'rsync -e %s %s %s',
          escapeshellarg('ssh ' . $ssh_opts_string),
          escapeshellarg($ssh_user . '@' . $ssh_host . ':/tmp/' . $remote_file),
          escapeshellarg($db_dir . '/db.sql')
        ),
        'result_code' => 1,
      ],
    ]);

    $this->runScriptError('src/download-db-lagoon', 'Failed to download database dump from Lagoon');
  }

  public function testSetupSshFails(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    $this->mockPassthru([
      'cmd' => self::$srcDir . '/setup-ssh',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/download-db-lagoon', 'Failed to setup SSH');
  }

  public function testDirectoryCreation(): void {
    // Don't pre-create directory.
    $db_dir = self::$tmp . '/new-dir';
    $this->envSet('VORTEX_DOWNLOAD_DB_LAGOON_DB_DIR', $db_dir);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    $ssh_opts = [
      '-o', 'UserKnownHostsFile=/dev/null',
      '-o', 'StrictHostKeyChecking=no',
      '-o', 'LogLevel=error',
      '-o', 'IdentitiesOnly=yes',
      '-p', '32222',
      '-i', '/home/user/.ssh/id_rsa',
    ];
    $ssh_opts_escaped = implode(' ', array_map(escapeshellarg(...), $ssh_opts));
    $ssh_opts_string = implode(' ', $ssh_opts);

    $remote_file = 'db_20240101.sql';
    $ssh_user = 'myproject-main';
    $ssh_host = 'ssh.lagoon.amazeeio.cloud';

    $remote_cmd = <<<BASH
    if [ ! -f "/tmp/{$remote_file}" ] || [ "" = "1" ] ; then
      [ -n "db_*.sql" ] && rm -f "/tmp/db_*.sql" && echo "Removed previously created DB dumps."
      echo "      > Creating a database dump /tmp/{$remote_file}."
      /app/vendor/bin/drush --root=./web sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump='--disable-ssl --no-tablespaces' > "/tmp/{$remote_file}"
    else
      echo "      > Using existing dump /tmp/{$remote_file}."
    fi
    BASH;

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'ssh %s %s service=cli container=cli %s',
          $ssh_opts_escaped,
          escapeshellarg($ssh_user . '@' . $ssh_host),
          escapeshellarg($remote_cmd)
        ),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'rsync -e %s %s %s',
          escapeshellarg('ssh ' . $ssh_opts_string),
          escapeshellarg($ssh_user . '@' . $ssh_host . ':/tmp/' . $remote_file),
          escapeshellarg($db_dir . '/db.sql')
        ),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-lagoon');

    $this->assertStringContainsString('Creating directory for database dumps.', $output);
    $this->assertTrue(is_dir($db_dir));
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testSshFileFalseDisablesIdentity(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    $this->envSet('VORTEX_DOWNLOAD_DB_SSH_FILE', 'false');

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101');

    // No -i flag when SSH file is 'false'.
    $ssh_opts = [
      '-o', 'UserKnownHostsFile=/dev/null',
      '-o', 'StrictHostKeyChecking=no',
      '-o', 'LogLevel=error',
      '-o', 'IdentitiesOnly=yes',
      '-p', '32222',
    ];
    $ssh_opts_escaped = implode(' ', array_map(escapeshellarg(...), $ssh_opts));
    $ssh_opts_string = implode(' ', $ssh_opts);

    $remote_file = 'db_20240101.sql';
    $ssh_user = 'myproject-main';
    $ssh_host = 'ssh.lagoon.amazeeio.cloud';

    $remote_cmd = <<<BASH
    if [ ! -f "/tmp/{$remote_file}" ] || [ "" = "1" ] ; then
      [ -n "db_*.sql" ] && rm -f "/tmp/db_*.sql" && echo "Removed previously created DB dumps."
      echo "      > Creating a database dump /tmp/{$remote_file}."
      /app/vendor/bin/drush --root=./web sql:dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,watchdog,cache* --extra-dump='--disable-ssl --no-tablespaces' > "/tmp/{$remote_file}"
    else
      echo "      > Using existing dump /tmp/{$remote_file}."
    fi
    BASH;

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'ssh %s %s service=cli container=cli %s',
          $ssh_opts_escaped,
          escapeshellarg($ssh_user . '@' . $ssh_host),
          escapeshellarg($remote_cmd)
        ),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          'rsync -e %s %s %s',
          escapeshellarg('ssh ' . $ssh_opts_string),
          escapeshellarg($ssh_user . '@' . $ssh_host . ':/tmp/' . $remote_file),
          escapeshellarg($db_dir . '/db.sql')
        ),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-lagoon');

    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

}
