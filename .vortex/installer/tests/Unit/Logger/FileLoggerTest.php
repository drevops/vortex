<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Logger;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Logger\FileLogger;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for FileLogger class.
 */
#[CoversClass(FileLogger::class)]
class FileLoggerTest extends UnitTestCase {

  /**
   * Test enable and disable methods.
   */
  #[DataProvider('dataProviderEnableDisable')]
  public function testEnableDisable(bool $initial_state, bool $after_enable, bool $after_disable): void {
    $logger = new FileLogger();

    if (!$initial_state) {
      $logger->disable();
    }

    $this->assertEquals($initial_state, $logger->isEnabled());

    $result = $logger->enable();
    $this->assertEquals($after_enable, $logger->isEnabled());
    $this->assertInstanceOf(FileLogger::class, $result, 'enable() should return self for method chaining');

    $result = $logger->disable();
    $this->assertEquals($after_disable, $logger->isEnabled());
    $this->assertInstanceOf(FileLogger::class, $result, 'disable() should return self for method chaining');
  }

  /**
   * Test setDir and getDir methods.
   */
  #[DataProvider('dataProviderDirectoryManagement')]
  public function testDirectoryManagement(string $dir, bool $test_default): void {
    $logger = new FileLogger();

    // Test default directory uses getcwd().
    if ($test_default) {
      $this->assertEquals(getcwd(), $logger->getDir());
    }
    else {
      // Test setDir sets custom directory.
      $result = $logger->setDir($dir);
      $this->assertEquals($dir, $logger->getDir());
      $this->assertInstanceOf(FileLogger::class, $result, 'setDir() should return self for method chaining');

      // Test getDir returns the set directory.
      $this->assertEquals($dir, $logger->getDir());
    }
  }

  /**
   * Test open method with enabled logging.
   */
  #[DataProvider('dataProviderOpen')]
  public function testOpen(string $command, array $args, bool $enabled, ?string $expected_pattern, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $logger = new FileLogger();
    $logger->setDir(self::$tmp);

    if (!$enabled) {
      $logger->disable();
    }

    $result = $logger->open($command, $args);

    if (!$enabled) {
      $this->assertFalse($result, 'open() should return FALSE when logger is disabled');
      $this->assertNull($logger->getPath(), 'getPath() should return NULL when logger is disabled');
    }
    else {
      $this->assertTrue($result, 'open() should return TRUE when logger is enabled');
      $path = $logger->getPath();
      $this->assertNotNull($path, 'getPath() should return path after successful open()');

      if ($expected_pattern !== NULL) {
        $this->assertMatchesRegularExpression($expected_pattern, $path, 'Log file path should match expected pattern');
      }

      // Verify log directory was created.
      $log_dir = dirname($path);
      $this->assertDirectoryExists($log_dir, 'Log directory should be created');

      // Verify log file was created.
      $this->assertFileExists($path, 'Log file should be created');

      $logger->close();
      File::remove($path);
    }
  }

  /**
   * Test write method.
   */
  #[DataProvider('dataProviderWrite')]
  public function testWrite(string $content, bool $is_open, int $expected_writes): void {
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);

    if ($is_open) {
      $logger->open('test-command');
      $path = $logger->getPath();
      $this->assertNotNull($path);
    }

    // Write content multiple times.
    for ($i = 0; $i < $expected_writes; $i++) {
      $logger->write($content);
    }

    if ($is_open) {
      $logger->close();
      $path = $logger->getPath();

      // Verify content was written.
      $written_content = file_get_contents((string) $path);
      $expected_content = str_repeat($content, $expected_writes);
      $this->assertEquals($expected_content, $written_content, 'Written content should match expected content');

      File::remove((string) $path);
    }
    else {
      // When logger is not open, write() should be a no-op.
      // We can't directly verify this, but we ensure no errors occur.
      // @phpstan-ignore-next-line
      $this->assertTrue(TRUE, 'write() should not throw error when logger is not open');
    }
  }

  /**
   * Test close method.
   */
  public function testClose(): void {
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);

    // Test close when no file is open (should be no-op).
    $logger->close();
    // @phpstan-ignore-next-line
    $this->assertTrue(TRUE, 'close() should not throw error when no file is open');

    // Test close after opening.
    $logger->open('test-command');
    $path = $logger->getPath();
    $this->assertNotNull($path);

    $logger->close();

    // Verify file is closed by attempting to write (should be no-op).
    $logger->write('should not be written');

    // File should still exist but content should not be written after close.
    $content = file_get_contents($path);
    $this->assertEquals('', $content, 'No content should be written after close()');

    // Test multiple close calls (idempotent).
    $logger->close();
    $logger->close();
    // @phpstan-ignore-next-line
    $this->assertTrue(TRUE, 'Multiple close() calls should not throw error');

    File::remove($path);
  }

  /**
   * Test getPath method.
   */
  public function testGetPath(): void {
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);

    // Test getPath before open() is called.
    $this->assertNull($logger->getPath(), 'getPath() should return NULL before open() is called');

    // Test getPath after open().
    $logger->open('test-command');
    $path = $logger->getPath();
    // @phpstan-ignore-next-line
    $this->assertNotNull($path, 'getPath() should return path after open()');
    $this->assertStringContainsString('test-command', (string) $path, 'Path should contain command name');

    $logger->close();
    File::remove((string) $path);

    // Test getPath when logging is disabled before open.
    $logger2 = new FileLogger();
    $logger2->setDir(self::$tmp);
    $logger2->disable();
    $result = $logger2->open('test-command-disabled');
    $this->assertFalse($result, 'open() should return FALSE when disabled');
    $this->assertNull($logger2->getPath(), 'getPath() should return NULL when logging is disabled');
  }

  /**
   * Test buildFilename method.
   */
  #[DataProvider('dataProviderBuildFilename')]
  public function testBuildFilename(string $command, array $args, string $expected): void {
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);

    $logger->open($command, $args);
    $path = $logger->getPath();

    if ($path !== NULL) {
      $filename = basename($path, '.log');
      // Remove timestamp suffix (format: -YYYY-MM-DD-HHMMSS).
      $filename = (string) preg_replace('/-\d{4}-\d{2}-\d{2}-\d{6}$/', '', $filename);

      $this->assertEquals($expected, $filename, 'Filename should match expected pattern');

      $logger->close();
      File::remove($path);
    }
  }

  /**
   * Data provider for enable/disable tests.
   */
  public static function dataProviderEnableDisable(): array {
    return [
      'initially enabled' => [
        'initial_state' => TRUE,
        'after_enable' => TRUE,
        'after_disable' => FALSE,
      ],
      'initially disabled' => [
        'initial_state' => FALSE,
        'after_enable' => TRUE,
        'after_disable' => FALSE,
      ],
    ];
  }

  /**
   * Data provider for directory paths.
   */
  public static function dataProviderDirectoryManagement(): array {
    return [
      'default directory (cwd)' => [
        'dir' => '',
        'test_default' => TRUE,
      ],
      'absolute path' => [
        'dir' => '/tmp/test-dir',
        'test_default' => FALSE,
      ],
      'relative path' => [
        'dir' => './test-dir',
        'test_default' => FALSE,
      ],
    ];
  }

  /**
   * Data provider for open scenarios.
   */
  public static function dataProviderOpen(): array {
    return [
      'simple command, enabled' => [
        'command' => 'test-command',
        'args' => [],
        'enabled' => TRUE,
        'expected_pattern' => '/test-command-\d{4}-\d{2}-\d{2}-\d{6}\.log$/',
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with positional args' => [
        'command' => 'install',
        'args' => ['project', 'arg2'],
        'enabled' => TRUE,
        'expected_pattern' => '/install-project-arg2-\d{4}-\d{2}-\d{2}-\d{6}\.log$/',
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option args (filtered)' => [
        'command' => 'test',
        'args' => ['positional', '--option=value', '-f'],
        'enabled' => TRUE,
        'expected_pattern' => '/test-positional-\d{4}-\d{2}-\d{2}-\d{6}\.log$/',
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with special characters' => [
        'command' => 'test:command',
        'args' => ['arg/with/slashes', 'arg with spaces'],
        'enabled' => TRUE,
        'expected_pattern' => '/test-command-arg-with-slashes-arg-with-spaces-\d{4}-\d{2}-\d{2}-\d{6}\.log$/',
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'disabled logger' => [
        'command' => 'test-disabled',
        'args' => [],
        'enabled' => FALSE,
        'expected_pattern' => NULL,
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
    ];
  }

  /**
   * Data provider for write content.
   */
  public static function dataProviderWrite(): array {
    return [
      'single write, logger open' => [
        'content' => 'Test log entry',
        'is_open' => TRUE,
        'expected_writes' => 1,
      ],
      'multiple writes, logger open' => [
        'content' => 'Line of text',
        'is_open' => TRUE,
        'expected_writes' => 3,
      ],
      'empty content, logger open' => [
        'content' => '',
        'is_open' => TRUE,
        'expected_writes' => 1,
      ],
      'multiline content, logger open' => [
        'content' => "Line 1\nLine 2\nLine 3\n",
        'is_open' => TRUE,
        'expected_writes' => 1,
      ],
      'write when logger not open (no-op)' => [
        'content' => 'Should not be written',
        'is_open' => FALSE,
        'expected_writes' => 1,
      ],
    ];
  }

  /**
   * Data provider for filename building.
   */
  public static function dataProviderBuildFilename(): array {
    return [
      'command only' => [
        'command' => 'test-command',
        'args' => [],
        'expected' => 'test-command',
      ],
      'command with positional args' => [
        'command' => 'install',
        'args' => ['project', 'theme'],
        'expected' => 'install-project-theme',
      ],
      'command with options (filtered)' => [
        'command' => 'run',
        'args' => ['script', '--verbose', '-f', 'value'],
        'expected' => 'run-script-value',
      ],
      'special characters sanitized' => [
        'command' => 'test/command:name',
        'args' => ['arg@with#special', 'arg with spaces'],
        'expected' => 'test-command-name-arg-with-special-arg-with-spaces',
      ],
      'multiple consecutive hyphens collapsed' => [
        'command' => 'test---command',
        'args' => ['arg***value'],
        'expected' => 'test-command-arg-value',
      ],
      'empty result fallback' => [
        'command' => '---',
        'args' => ['--option', '-f'],
        'expected' => 'runner',
      ],
    ];
  }

}
