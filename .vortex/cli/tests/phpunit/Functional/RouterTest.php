<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\ConfigureCommand;
use DrevOps\VortexCli\Command\RouterCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the default router command.
 */
#[CoversClass(RouterCommand::class)]
#[Group('command')]
final class RouterTest extends TestCase {

  /**
   * The directory routed on during the test.
   */
  protected string $sut;

  protected function setUp(): void {
    parent::setUp();
    $this->sut = dirname(__DIR__, 3) . '/.artifacts/tmp/router-test-' . getmypid();
    (new Filesystem())->mkdir($this->sut);
  }

  protected function tearDown(): void {
    (new Filesystem())->remove($this->sut);
    parent::tearDown();
  }

  public function testTargets(): void {
    $router = new RouterCommand();

    // A directory without a Vortex README routes to install.
    $this->assertSame('install', $router->target($this->sut));

    // A Vortex project - its README carries the badge - routes to configure.
    (new Filesystem())->dumpFile($this->sut . '/README.md', "[![Vortex](https://img.shields.io/badge/Vortex-x-blue)]()\n");
    $this->assertSame('configure', $router->target($this->sut));
  }

  public function testDelegatesToConfigure(): void {
    (new Filesystem())->dumpFile($this->sut . '/README.md', "[![Vortex](https://img.shields.io/badge/Vortex-x-blue)]()\n");

    $router = new RouterCommand();
    $router->setDirectory($this->sut);

    $application = new Application();
    $application->add($router);
    $application->add(new ConfigureCommand());

    $tester = new CommandTester($application->find('route'));
    $exit = $tester->execute(['--prompts' => '{"name":"Acme"}'], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('Acme', $data['name']);
  }

}
