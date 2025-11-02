<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests helper methods used in the testing framework. Tests for tests.
 */
#[Group('smoke')]
class HelpersTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->forceVolumesUnmounted();
  }

  #[Group('p0')]
  public function testSyncToContainer(): void {
    $this->prepareFixtureContainer();

    $this->logSubstep('Preparing test files on the host and in the container');
    File::dump('file1.txt', 'file1.txt content on host');
    File::dump('dir1/file11.txt', 'dir1/file11.txt content on host');
    File::dump('dir1/file12.txt', 'dir1/file12.txt content on host');
    File::dump('dir2/file21.txt', 'dir2/file21.txt content on host');
    File::dump('dir2/file22.txt', 'dir2/file22.txt content on host');

    $this->cmd('docker compose exec -T cli bash -c "echo \"file1.txt content in container\" > /app/file1.txt"');
    $this->cmd('docker compose exec -T cli bash -c "mkdir /app/dir1"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file11.txt content in container\" > /app/dir1/file11.txt"');
    $this->cmdFail('docker compose exec -T cli bash -c "test -f /app/dir1/file12.txt"');
    $this->cmd('docker compose exec -T cli bash -c "mkdir /app/dir2"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file21.txt content in container\" > /app/dir2/file21.txt"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file22.txt content in container\" > /app/dir2/file22.txt"');

    $this->logSubstep('Syncing files to the container');
    $this->syncToContainer(['file1.txt', 'dir1/file11.txt', 'dir2']);

    $this->logSubstep('Verifying test files exist in the container');
    $this->cmd('docker compose exec -T cli bash -c "test -f /app/file1.txt"');
    $this->cmd('docker compose exec -T cli bash -c "cat /app/file1.txt|grep \"file1.txt content on host\""');
    $this->cmd('docker compose exec -T cli bash -c "test -f /app/dir1/file11.txt"');
    $this->cmd('docker compose exec -T cli bash -c "cat /app/dir1/file11.txt|grep \"dir1/file11.txt content on host\""');
    $this->cmdFail('docker compose exec -T cli bash -c "test -f /app/dir1/file12.txt"');
    $this->cmd('docker compose exec -T cli bash -c "test -f /app/dir2/file21.txt"');
    $this->cmd('docker compose exec -T cli bash -c "cat /app/dir2/file21.txt|grep \"dir2/file21.txt content on host\""');
    $this->cmd('docker compose exec -T cli bash -c "test -f /app/dir2/file22.txt"');
    $this->cmd('docker compose exec -T cli bash -c "cat /app/dir2/file22.txt|grep \"dir2/file22.txt content on host\""');
  }

  #[Group('p0')]
  public function testSyncToHost(): void {
    $this->prepareFixtureContainer();

    $this->logSubstep('Preparing test files on the host and in the container');
    File::dump('file1.txt', 'file1.txt content on host');
    File::dump('dir2/file21.txt', 'dir2/file21.txt content on host');

    $this->cmd('docker compose exec -T cli bash -c "echo \"file1.txt content in container\" > /app/file1.txt"');
    $this->cmd('docker compose exec -T cli bash -c "mkdir /app/dir1"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file11.txt content in container\" > /app/dir1/file11.txt"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file12.txt content in container\" > /app/dir1/file12.txt"');
    $this->cmd('docker compose exec -T cli bash -c "mkdir /app/dir2"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file21.txt content in container\" > /app/dir2/file21.txt"');
    $this->cmd('docker compose exec -T cli bash -c "echo \"file22.txt content in container\" > /app/dir2/file22.txt"');

    $this->logSubstep('Syncing files to the host');
    $this->syncToHost(['file1.txt', 'dir1/file11.txt', 'dir2']);

    $this->logSubstep('Verifying test files exist on the host');

    $this->assertFileExists('file1.txt');
    $this->assertFileContainsString('file1.txt', 'file1.txt content in container');
    $this->assertFileExists('dir1/file11.txt');
    $this->assertFileContainsString('dir1/file11.txt', 'file11.txt content in container');
    $this->assertFileDoesNotExist('dir1/file12.txt');
    $this->assertFileExists('dir2/file21.txt');
    $this->assertFileContainsString('dir2/file21.txt', 'file21.txt content in container');
    $this->assertFileExists('dir2/file22.txt');
    $this->assertFileContainsString('dir2/file22.txt', 'file22.txt content in container');
  }

  protected function prepareFixtureContainer(): void {
    $this->logStepStart();

    File::dump('docker-compose.yml', <<<EOF
services:
  cli:
    image: uselagoon/php-8.3-cli-drupal:25.8.0
EOF
    );

    $this->logSubstep('Starting Docker Compose build');
    $this->cmd('docker compose up -d --force-recreate --build --renew-anon-volumes', txt: 'Stack images should be built and stack should start successfully');

    $this->logStepFinish();
  }

}
