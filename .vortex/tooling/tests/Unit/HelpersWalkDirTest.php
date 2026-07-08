<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for walk_dir() function.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\walk_dir')]
#[Group('helpers')]
class HelpersWalkDirTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testWalkDirVisitsAllEntries(): void {
    $dir = self::$tmp . '/walk_' . uniqid();
    $this->createDirectoryStructure($dir, [
      'a.txt' => 'a',
      'sub' => [
        'b.txt' => 'b',
        'nested' => [
          'c.txt' => 'c',
        ],
      ],
    ]);

    $visited = [];
    \DrevOps\VortexTooling\walk_dir($dir, function (\SplFileInfo $item) use (&$visited, $dir): void {
      $visited[] = substr($item->getPathname(), strlen($dir) + 1);
    });

    $this->assertSame(['a.txt', 'sub', 'sub/b.txt', 'sub/nested', 'sub/nested/c.txt'], $visited);
  }

  public function testWalkDirPrunesOnFalse(): void {
    $dir = self::$tmp . '/walk_' . uniqid();
    $this->createDirectoryStructure($dir, [
      'keep' => [
        'a.txt' => 'a',
      ],
      'skip' => [
        'b.txt' => 'b',
      ],
    ]);

    $visited = [];
    \DrevOps\VortexTooling\walk_dir($dir, function (\SplFileInfo $item) use (&$visited, $dir): bool {
      $path = substr($item->getPathname(), strlen($dir) + 1);
      $visited[] = $path;

      return $path !== 'skip';
    });

    $this->assertSame(['keep', 'keep/a.txt', 'skip'], $visited);
  }

  public function testWalkDirDoesNotFollowSymlinks(): void {
    $target = self::$tmp . '/target_' . uniqid();
    $dir = self::$tmp . '/walk_' . uniqid();

    $this->createDirectoryStructure($target, ['inside.txt' => 'x']);
    $this->createDirectoryStructure($dir, ['a.txt' => 'a']);
    symlink($target, $dir . '/link');

    $visited = [];
    \DrevOps\VortexTooling\walk_dir($dir, function (\SplFileInfo $item) use (&$visited, $dir): void {
      $visited[] = substr($item->getPathname(), strlen($dir) + 1);
    });

    $this->assertSame(['a.txt', 'link'], $visited);
  }

  public function testWalkDirMissingPathIsNoop(): void {
    $visited = [];
    \DrevOps\VortexTooling\walk_dir(self::$tmp . '/missing_' . uniqid(), function (\SplFileInfo $item) use (&$visited): void {
      $visited[] = $item->getPathname();
    });

    $this->assertSame([], $visited);
  }

  public function testWalkDirPathIsFileIsNoop(): void {
    $file = self::$tmp . '/file_' . uniqid();
    file_put_contents($file, 'content');

    $visited = [];
    \DrevOps\VortexTooling\walk_dir($file, function (\SplFileInfo $item) use (&$visited): void {
      $visited[] = $item->getPathname();
    });

    $this->assertSame([], $visited);
    unlink($file);
  }

  protected function createDirectoryStructure(string $base_path, array $structure): void {
    if (!is_dir($base_path)) {
      mkdir($base_path, 0755, TRUE);
    }

    foreach ($structure as $name => $content) {
      $path = $base_path . '/' . $name;
      if (is_array($content)) {
        // It's a directory.
        mkdir($path, 0755, TRUE);
        $this->createDirectoryStructure($path, $content);
      }
      else {
        // It's a file.
        file_put_contents($path, $content);
      }
    }
  }

}
