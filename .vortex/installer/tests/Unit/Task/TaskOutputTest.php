<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Task;

use DrevOps\VortexInstaller\Task\TaskOutput;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for TaskOutput class.
 */
#[CoversClass(TaskOutput::class)]
class TaskOutputTest extends UnitTestCase {

  /**
   * Test constructor accepts OutputInterface.
   */
  public function testConstructor(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $this->assertInstanceOf(TaskOutput::class, $output);
  }

  /**
   * Test write method dims single message.
   */
  public function testWriteSingleMessage(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->write('Test message');

    $content = $wrapped->fetch();
    // The message should be dimmed (wrapped with ANSI codes).
    $this->assertStringContainsString('Test message', $content);
  }

  /**
   * Test write method dims iterable messages.
   */
  public function testWriteIterableMessages(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->write(['Line 1', 'Line 2']);

    $content = $wrapped->fetch();
    $this->assertStringContainsString('Line 1', $content);
    $this->assertStringContainsString('Line 2', $content);
  }

  /**
   * Test writeln method dims single message.
   */
  public function testWritelnSingleMessage(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->writeln('Test message');

    $content = $wrapped->fetch();
    $this->assertStringContainsString('Test message', $content);
  }

  /**
   * Test writeln method dims iterable messages.
   */
  public function testWritelnIterableMessages(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->writeln(['Line 1', 'Line 2']);

    $content = $wrapped->fetch();
    $this->assertStringContainsString('Line 1', $content);
    $this->assertStringContainsString('Line 2', $content);
  }

  /**
   * Test verbosity delegation.
   */
  public function testVerbosityDelegation(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $output->getVerbosity());

    $this->assertFalse($output->isQuiet());
    $this->assertTrue($output->isVerbose());
    $this->assertTrue($output->isVeryVerbose());
    $this->assertTrue($output->isDebug());
  }

  /**
   * Test decoration delegation.
   */
  public function testDecorationDelegation(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $output->setDecorated(TRUE);
    $this->assertTrue($output->isDecorated());

    $output->setDecorated(FALSE);
    $this->assertFalse($output->isDecorated());
  }

  /**
   * Test formatter delegation.
   */
  public function testFormatterDelegation(): void {
    $wrapped = new BufferedOutput();
    $output = new TaskOutput($wrapped);

    $formatter = new OutputFormatter();
    $output->setFormatter($formatter);

    $this->assertSame($formatter, $output->getFormatter());
  }

}
