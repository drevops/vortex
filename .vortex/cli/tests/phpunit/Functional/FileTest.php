<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the relocated File async machinery.
 */
#[CoversClass(File::class)]
#[Group('file')]
final class FileTest extends TestCase {

  /**
   * The working directory for the test.
   */
  protected string $dir;

  protected function setUp(): void {
    parent::setUp();
    $this->dir = dirname(__DIR__, 3) . '/.artifacts/tmp/file-test-' . getmypid();
    (new Filesystem())->mkdir($this->dir);
  }

  protected function tearDown(): void {
    (new Filesystem())->remove($this->dir);
    parent::tearDown();
  }

  public function testIsInternal(): void {
    $this->assertTrue(File::isInternal('/LICENSE'));
    $this->assertTrue(File::isInternal('./LICENSE'));
    $this->assertFalse(File::isInternal('/README.md'));
  }

  public function testAsyncReplaceAndTokenRemoval(): void {
    file_put_contents($this->dir . '/test.txt', "Hello YOURSITE\n#;< FEATURE\nremove me\n#;> FEATURE\nkeep me\n");

    File::replaceContentAsync('YOURSITE', 'Acme');
    File::removeTokenAsync('FEATURE');
    File::runDirectoryTasks($this->dir);

    $result = (string) file_get_contents($this->dir . '/test.txt');
    $this->assertStringContainsString('Hello Acme', $result);
    $this->assertStringNotContainsString('remove me', $result);
    $this->assertStringNotContainsString('#;<', $result);
    $this->assertStringContainsString('keep me', $result);
  }

}
