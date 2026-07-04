<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\Customize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the collection -> apply pipeline (reverse-order process + flush).
 */
#[CoversClass(Customize::class)]
#[Group('process')]
final class ProcessTest extends TestCase {

  /**
   * The working directory for the test.
   */
  protected string $dir;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = dirname(__DIR__, 3) . '/.artifacts/tmp/process-test-' . getmypid();
    (new Filesystem())->mkdir($this->dir);
  }

  protected function tearDown(): void {
    (new Filesystem())->remove($this->dir);
    parent::tearDown();
  }

  public function testGeneralSectionAppliesReplacements(): void {
    file_put_contents($this->dir . '/content.txt', 'YOURSITE your_site your-site YourSite YOURORG your_org your-site-domain.example');
    (new Filesystem())->mkdir($this->dir . '/your_site');
    file_put_contents($this->dir . '/your_site/keep.txt', 'content');

    $this->apply('{"name":"Acme Site"}');

    $result = (string) file_get_contents($this->dir . '/content.txt');
    $this->assertSame('Acme Site acme_site acme-site AcmeSite Acme Site Org acme_site_org acme-site.com', $result);
    $this->assertFileExists($this->dir . '/acme_site/keep.txt');
    $this->assertDirectoryDoesNotExist($this->dir . '/your_site');
  }

  /**
   * Run the customize command with --apply on the working directory.
   *
   * @param string $prompts
   *   The answers as JSON.
   */
  protected function apply(string $prompts): void {
    $application = new Application();
    $application->add(new Customize());
    $tester = new CommandTester($application->find('customize'));

    $tester->execute(['--prompts' => $prompts, '--apply' => TRUE, '--dir' => $this->dir], ['interactive' => FALSE]);
  }

}
