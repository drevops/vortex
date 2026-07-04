<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\VortexCli\Handler\Name;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the collection -> process -> apply pipeline through a handler.
 */
#[CoversClass(Name::class)]
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

  public function testNameProcessAppliesReplacement(): void {
    file_put_contents($this->dir . '/index.php', "// Project YOURSITE\n");

    $config = (new ConfigLoader())->loadFiles([dirname(__DIR__, 3) . '/config/vortex.yml']);
    $engine = new Engine($config, new HandlerRegistry(['DrevOps\\VortexCli\\Handler']));

    $engine->run(['name' => 'Acme Site'], new Context($this->dir, [], FALSE));
    File::runDirectoryTasks($this->dir);

    $result = (string) file_get_contents($this->dir . '/index.php');
    $this->assertStringContainsString('Project Acme Site', $result);
    $this->assertStringNotContainsString('YOURSITE', $result);
  }

}
