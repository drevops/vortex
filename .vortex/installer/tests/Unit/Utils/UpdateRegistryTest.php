<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\UpdateRegistry;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateRegistry::class)]
class UpdateRegistryTest extends UnitTestCase {

  public function testWriteWithoutEntries(): void {
    $registry = new UpdateRegistry(self::$sut);

    $this->assertTrue($registry->isEmpty());
    $this->assertNull($registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22'));
    $this->assertFileDoesNotExist(self::$sut . '/' . UpdateRegistry::FILE);
  }

  public function testWriteRendersBothDiffs(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('phpstan.neon', "level: 5\n", "level: 8\n", "level: 9\n");

    $file = $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFalse($registry->isEmpty());
    $this->assertEquals(self::$sut . '/' . UpdateRegistry::FILE, $file);
    $this->assertFileContainsString((string) $file, '# Vortex update registry');
    $this->assertFileContainsString((string) $file, '## 1.40.0 to 1.41.0, 2026-09-07 09:31:22');
    $this->assertFileContainsString((string) $file, '### phpstan.neon');
    $this->assertFileContainsString((string) $file, 'Project change that the update replaced:');
    $this->assertFileContainsString((string) $file, 'Change that the update brings:');
    $this->assertFileContainsString((string) $file, '-level: 5');
    $this->assertFileContainsString((string) $file, '+level: 8');
    $this->assertFileContainsString((string) $file, '+level: 9');
    $this->assertFileContainsString((string) $file, '```diff');
  }

  public function testWriteOmitsUnchangedUpdateDiff(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('phpstan.neon', "level: 5\n", "level: 8\n", "level: 5\n");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileContainsString($file, 'The update ships this file unchanged.');
    $this->assertFileNotContainsString($file, 'Change that the update brings:');
  }

  public function testWriteNotesBinaryContent(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('logo.png', "\x89PNG\0old", "\x89PNG\0project", "\x89PNG\0new");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileContainsString($file, '### logo.png');
    $this->assertFileContainsString($file, 'Binary file. Recover the project copy from version control.');
    $this->assertFileNotContainsString($file, '```diff');
  }

  public function testWriteNotesOversizedContent(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('package-lock.json', '{}', str_repeat('a', UpdateRegistry::MAX_DIFF_BYTES + 1), '{}');

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileContainsString($file, '### package-lock.json');
    $this->assertFileContainsString($file, 'File is too large to diff. Recover the project copy from version control.');
    $this->assertFileNotContainsString($file, '```diff');
  }

  public function testWriteRendersProjectAuthoredPath(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('phpstan.neon', NULL, "level: 8\n", "level: 9\n");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileContainsString($file, 'The version the project runs did not ship this file.');
    $this->assertFileContainsString($file, '-level: 8');
    $this->assertFileContainsString($file, '+level: 9');
    $this->assertFileNotContainsString($file, 'Change that the update brings:');
  }

  public function testWriteDiffsAgainstAnEmptyInstalledFile(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('.env.local.example', '', "ADDED=1\n", "SHIPPED=1\n");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileNotContainsString($file, 'The version the project runs did not ship this file.', 'A file shipped empty is not reported as never shipped.');
    $this->assertFileContainsString($file, 'Project change that the update replaced:');
    $this->assertFileContainsString($file, 'Change that the update brings:');
  }

  public function testWriteWidensFenceAroundContentWithFences(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('README.md', "# Title\n```php\n\$a = 1;\n```\n", "# Title\n```php\n\$a = 2;\n```\n", "# Title\n```php\n\$a = 3;\n```\n");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertFileContainsString($file, '````diff', 'The fence outgrows the longest backtick run in the diff.');
    $this->assertFileContainsString($file, PHP_EOL . '````' . PHP_EOL, 'The closing fence matches the opening one.');
    $this->assertStringNotContainsString('```' . PHP_EOL . '```diff', File::read($file), 'No entry opens with a fence the diff can close.');
  }

  public function testWriteSortsEntriesByPath(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('phpstan.neon', "a\n", "b\n", "c\n");
    $registry->add('.circleci/config.yml', "a\n", "b\n", "c\n");

    $file = (string) $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $this->assertLessThan(
      (int) strpos(File::read($file), '### phpstan.neon'),
      (int) strpos(File::read($file), '### .circleci/config.yml'),
      'Entries are rendered in path order.'
    );
  }

  public function testWriteAppendsToExistingRegistry(): void {
    $registry = new UpdateRegistry(self::$sut);
    $registry->add('phpstan.neon', "level: 5\n", "level: 8\n", "level: 9\n");
    $registry->write('1.40.0', '1.41.0', '2026-09-07 09:31:22');

    $next = new UpdateRegistry(self::$sut);
    $next->add('behat.yml', "a\n", "b\n", "c\n");
    $file = (string) $next->write('1.41.0', '1.42.0', '2026-09-08 10:00:00');

    $content = File::read($file);

    $this->assertEquals(1, substr_count($content, '# Vortex update registry'), 'The heading is written once.');
    $this->assertFileContainsString($file, '## 1.40.0 to 1.41.0, 2026-09-07 09:31:22');
    $this->assertFileContainsString($file, '## 1.41.0 to 1.42.0, 2026-09-08 10:00:00');
    $this->assertFileContainsString($file, '### phpstan.neon');
    $this->assertFileContainsString($file, '### behat.yml');
  }

}
