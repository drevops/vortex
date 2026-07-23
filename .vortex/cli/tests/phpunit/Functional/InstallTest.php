<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\AbstractInstallCommand;
use DrevOps\VortexCli\Command\Install;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the end-to-end install: download the local template, customize, copy.
 */
#[CoversClass(Install::class)]
#[CoversClass(AbstractInstallCommand::class)]
#[Group('install')]
final class InstallTest extends TestCase {

  /**
   * The destination directory for the installed project.
   */
  protected string $sut;

  protected function setUp(): void {
    parent::setUp();
    $this->sut = dirname(__DIR__, 3) . '/.artifacts/tmp/install-test-' . getmypid();
    (new Filesystem())->mkdir($this->sut);
  }

  protected function tearDown(): void {
    (new Filesystem())->remove($this->sut);
    parent::tearDown();
  }

  #[RunInSeparateProcess]
  public function testInstall(): void {
    $root = dirname(__DIR__, 5);

    $application = new Application();
    $application->add(new Install());
    $tester = new CommandTester($application->find('install'));

    $status = $tester->execute([
      '--uri' => $root,
      '--destination' => $this->sut,
      '--prompts' => '{"name":"Star Wars"}',
      '--no-interaction' => TRUE,
    ], ['interactive' => FALSE]);

    $this->assertSame(0, $status);
    $this->assertDirectoryDoesNotExist($this->sut . '/.vortex', 'Vortex internal files are removed.');
    $this->assertFileExists($this->sut . '/.env');

    $readme = (string) file_get_contents($this->sut . '/README.md');
    $this->assertStringContainsString('Star Wars', $readme);
    $this->assertStringNotContainsString('YOURSITE', $readme);
    $this->assertStringNotContainsString('your_site', $readme);
  }

}
