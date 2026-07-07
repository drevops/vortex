<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\AbstractInstallCommand;
use DrevOps\VortexCli\Command\Update;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the update command as a facade over the shared install flow.
 */
#[CoversClass(Update::class)]
#[CoversClass(AbstractInstallCommand::class)]
#[Group('command')]
final class UpdateTest extends TestCase {

  /**
   * The destination directory for the updated project.
   */
  protected string $sut;

  protected function setUp(): void {
    parent::setUp();
    $this->sut = dirname(__DIR__, 3) . '/.artifacts/tmp/update-test-' . getmypid();
    (new Filesystem())->mkdir($this->sut);
  }

  protected function tearDown(): void {
    (new Filesystem())->remove($this->sut);
    parent::tearDown();
  }

  #[RunInSeparateProcess]
  public function testUpdateRunsTheInstallFlow(): void {
    $root = dirname(__DIR__, 5);

    $application = new Application();
    $application->add(new Update());
    $tester = new CommandTester($application->find('update'));

    $status = $tester->execute([
      '--uri' => $root,
      '--destination' => $this->sut,
      '--prompts' => '{"name":"Star Wars"}',
      '--no-interaction' => TRUE,
    ], ['interactive' => FALSE]);

    $this->assertSame(0, $status);
    $this->assertFileExists($this->sut . '/.env');
    $readme = (string) file_get_contents($this->sut . '/README.md');
    $this->assertStringContainsString('Star Wars', $readme);
  }

  public function testTargetUri(): void {
    $update = new Update();

    // Neither option: leave resolution at its default.
    $this->assertNull($update->targetUri('', ''));

    // An explicit URI wins over "--to".
    $this->assertSame('/local/template', $update->targetUri('1.2.3', '/local/template'));

    // "--to" derives a versioned URI against the default repository.
    $this->assertSame(RepositoryDownloader::DEFAULT_REPO . '#1.2.3', $update->targetUri('1.2.3', ''));
  }

}
