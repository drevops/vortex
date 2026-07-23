<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\Doctor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Tests the Doctor command.
 */
#[CoversClass(Doctor::class)]
#[Group('command')]
final class DoctorTest extends TestCase {

  public function testAllPresent(): void {
    $tester = $this->tester(static fn(string $tool): string => '/usr/bin/' . $tool);

    $exit = $tester->execute([], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $this->assertStringContainsString('[OK] git', $tester->getDisplay());
    $this->assertStringContainsString('[OK] docker', $tester->getDisplay());
  }

  public function testMissingFails(): void {
    $tester = $this->tester(static fn(string $tool): ?string => $tool === 'git' ? '/usr/bin/git' : NULL);

    $exit = $tester->execute([], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('[MISSING] docker', $tester->getDisplay());
  }

  /**
   * Build a tester with a stubbed executable finder.
   *
   * @param callable $resolver
   *   Maps a tool name to a path or NULL.
   */
  protected function tester(callable $resolver): CommandTester {
    $finder = $this->createMock(ExecutableFinder::class);
    $finder->method('find')->willReturnCallback($resolver);

    $command = new Doctor();
    $command->setExecutableFinder($finder);

    $application = new Application();
    $application->add($command);

    return new CommandTester($application->find('doctor'));
  }

}
