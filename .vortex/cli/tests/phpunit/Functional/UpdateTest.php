<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\Update;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the Update command.
 */
#[CoversClass(Update::class)]
#[Group('command')]
final class UpdateTest extends TestCase {

  public function testCollectsInUpdateMode(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":"Acme"}', '--to' => '1.40'], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    // The supplied answer wins over any discovered value.
    $this->assertSame('Acme', $data['name']);
  }

  public function testInvalidNameFails(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":""}'], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('site name is required', $tester->getDisplay());
  }

  /**
   * Build a tester for the Update command.
   */
  protected function tester(): CommandTester {
    $application = new Application();
    $application->add(new Update());

    return new CommandTester($application->find('update'));
  }

}
