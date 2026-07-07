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

/**
 * Tests the default router command.
 */
#[CoversClass(RouterCommand::class)]
#[Group('command')]
final class RouterTest extends TestCase {

  public function testTargets(): void {
    $router = new RouterCommand();

    // An empty (or missing) directory routes to install.
    $this->assertSame('install', $router->target('/no/such/directory'));

    // An existing, populated directory routes to configure.
    $this->assertSame('configure', $router->target(dirname(__DIR__, 3)));
  }

  public function testDelegatesToConfigure(): void {
    // The working directory (the CLI package) is populated, so the router
    // delegates to configure, which collects and prints the answers as JSON.
    $application = new Application();
    $application->add(new RouterCommand());
    $application->add(new ConfigureCommand());

    $tester = new CommandTester($application->find('route'));
    $exit = $tester->execute(['--prompts' => '{"name":"Acme"}'], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('Acme', $data['name']);
  }

}
