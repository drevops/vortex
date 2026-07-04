<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\Customize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the Customize command end to end.
 */
#[CoversClass(Customize::class)]
#[Group('command')]
final class CustomizeTest extends TestCase {

  public function testNonInteractive(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":"Acme Site","profile":"minimal"}'], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('Acme Site', $data['name']);
    $this->assertSame('acme_site', $data['machine_name']);
    $this->assertSame('minimal', $data['profile']);
  }

  public function testDerivesAndDefaults(): void {
    $tester = $this->tester();

    $tester->execute(['--prompts' => '{"name":"  Acme  "}'], ['interactive' => FALSE]);

    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('Acme', $data['name']);
    $this->assertSame('acme', $data['machine_name']);
    $this->assertSame('standard', $data['profile']);
  }

  public function testRequiredNameFails(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":""}'], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('site name is required', $tester->getDisplay());
  }

  /**
   * Build a tester for the Customize command.
   */
  protected function tester(): CommandTester {
    $application = new Application();
    $application->add(new Customize());

    return new CommandTester($application->find('customize'));
  }

}
