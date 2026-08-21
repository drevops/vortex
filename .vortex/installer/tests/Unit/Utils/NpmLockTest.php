<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\NpmLock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(NpmLock::class)]
class NpmLockTest extends UnitTestCase {

  public function testSyncWithoutLockFile(): void {
    $manifest_file = $this->createPair(['devDependencies' => ['keep' => '^1.0.0']], NULL);

    NpmLock::sync($manifest_file);

    $this->assertFileDoesNotExist(dirname($manifest_file) . '/package-lock.json');
  }

  #[DataProvider('dataProviderSync')]
  public function testSync(array $manifest, array $lock, array $expected_root, array $expected_paths): void {
    $manifest_file = $this->createPair($manifest, $lock);

    NpmLock::sync($manifest_file);

    $actual = $this->readLock($manifest_file);

    $this->assertSame($expected_root, $actual['packages']['']);
    $this->assertSame($expected_paths, array_keys($actual['packages']));
  }

  public static function dataProviderSync(): \Iterator {
    yield 'dependency removed from the manifest is pruned with its orphans' => [
      ['devDependencies' => ['keep' => '^1.0.0']],
      [
        'packages' => [
          '' => ['name' => 'test', 'devDependencies' => ['keep' => '^1.0.0', 'drop' => '^2.0.0']],
          'node_modules/deep-orphan' => ['version' => '1.0.0'],
          'node_modules/drop' => ['version' => '2.0.0', 'dependencies' => ['orphan' => '^1.0.0', 'shared' => '^1.0.0']],
          'node_modules/drop/node_modules/nested' => ['version' => '1.0.0'],
          'node_modules/keep' => ['version' => '1.0.0', 'dependencies' => ['shared' => '^1.0.0']],
          'node_modules/orphan' => ['version' => '1.0.0', 'dependencies' => ['deep-orphan' => '^1.0.0']],
          'node_modules/shared' => ['version' => '1.0.0'],
        ],
      ],
      ['name' => 'test', 'devDependencies' => ['keep' => '^1.0.0']],
      ['', 'node_modules/keep', 'node_modules/shared'],
    ];
    yield 'a nested copy shadows the hoisted one' => [
      ['dependencies' => ['keep' => '^1.0.0']],
      [
        'packages' => [
          '' => ['dependencies' => ['keep' => '^1.0.0']],
          'node_modules/keep' => ['version' => '1.0.0', 'dependencies' => ['shared' => '^2.0.0']],
          'node_modules/keep/node_modules/shared' => ['version' => '2.0.0'],
          'node_modules/shared' => ['version' => '1.0.0'],
        ],
      ],
      ['dependencies' => ['keep' => '^1.0.0']],
      ['', 'node_modules/keep', 'node_modules/keep/node_modules/shared'],
    ];
    yield 'a dependency of a nested copy falls back to the hoisted tree' => [
      ['dependencies' => ['keep' => '^1.0.0']],
      [
        'packages' => [
          '' => ['dependencies' => ['keep' => '^1.0.0']],
          'node_modules/keep' => ['version' => '1.0.0', 'dependencies' => ['nested' => '^1.0.0']],
          'node_modules/keep/node_modules/nested' => ['version' => '1.0.0', 'dependencies' => ['hoisted' => '^1.0.0']],
          'node_modules/hoisted' => ['version' => '1.0.0'],
        ],
      ],
      ['dependencies' => ['keep' => '^1.0.0']],
      ['', 'node_modules/keep', 'node_modules/keep/node_modules/nested', 'node_modules/hoisted'],
    ];
    yield 'every dependency block is followed' => [
      [
        'dependencies' => ['runtime' => '^1.0.0'],
        'devDependencies' => ['dev' => '^1.0.0'],
        'optionalDependencies' => ['optional' => '^1.0.0'],
        'peerDependencies' => ['peer' => '^1.0.0'],
      ],
      [
        'packages' => [
          '' => ['name' => 'test'],
          'node_modules/dev' => ['version' => '1.0.0'],
          'node_modules/optional' => ['version' => '1.0.0'],
          'node_modules/peer' => ['version' => '1.0.0'],
          'node_modules/runtime' => ['version' => '1.0.0'],
          'node_modules/unreferenced' => ['version' => '1.0.0'],
        ],
      ],
      [
        'name' => 'test',
        'dependencies' => ['runtime' => '^1.0.0'],
        'devDependencies' => ['dev' => '^1.0.0'],
        'optionalDependencies' => ['optional' => '^1.0.0'],
        'peerDependencies' => ['peer' => '^1.0.0'],
      ],
      ['', 'node_modules/dev', 'node_modules/optional', 'node_modules/peer', 'node_modules/runtime'],
    ];
    yield 'a block absent from the manifest is dropped from the lock' => [
      ['dependencies' => ['runtime' => '^1.0.0']],
      [
        'packages' => [
          '' => ['dependencies' => ['runtime' => '^1.0.0'], 'devDependencies' => ['dev' => '^1.0.0']],
          'node_modules/dev' => ['version' => '1.0.0'],
          'node_modules/runtime' => ['version' => '1.0.0'],
        ],
      ],
      ['dependencies' => ['runtime' => '^1.0.0']],
      ['', 'node_modules/runtime'],
    ];
    yield 'a manifest entry the lock does not carry is added' => [
      ['dependencies' => ['added' => '^1.0.0', 'changed' => '^2.0.0']],
      [
        'packages' => [
          '' => ['dependencies' => ['changed' => '^1.0.0']],
          'node_modules/changed' => ['version' => '1.0.0'],
        ],
      ],
      ['dependencies' => ['added' => '^1.0.0', 'changed' => '^2.0.0']],
      ['', 'node_modules/changed'],
    ];
    yield 'a workspace is a root of its own' => [
      ['dependencies' => ['app' => '*']],
      [
        'packages' => [
          '' => ['dependencies' => ['app' => '*']],
          'node_modules/app' => ['resolved' => 'packages/app', 'link' => TRUE],
          'node_modules/workspace-only' => ['version' => '1.0.0'],
          'node_modules/unreferenced' => ['version' => '1.0.0'],
          'packages/app' => ['version' => '1.0.0', 'dependencies' => ['workspace-only' => '^1.0.0']],
        ],
      ],
      ['dependencies' => ['app' => '*']],
      ['', 'node_modules/app', 'node_modules/workspace-only', 'packages/app'],
    ];
    yield 'an unresolvable optional dependency is skipped' => [
      ['dependencies' => ['keep' => '^1.0.0']],
      [
        'packages' => [
          '' => ['dependencies' => ['keep' => '^1.0.0']],
          'node_modules/keep' => ['version' => '1.0.0', 'optionalDependencies' => ['never-installed' => '^1.0.0']],
        ],
      ],
      ['dependencies' => ['keep' => '^1.0.0']],
      ['', 'node_modules/keep'],
    ];
  }

  public function testSyncWritesTheFileTheWayNpmWritesIt(): void {
    $manifest_file = $this->createPair(
      ['dependencies' => ['keep' => '^1.0.0']],
      [
        'name' => 'test',
        'lockfileVersion' => 3,
        'packages' => [
          '' => ['dependencies' => ['keep' => '^1.0.0']],
          'node_modules/keep' => ['version' => '1.0.0', 'resolved' => 'https://registry.npmjs.org/keep/-/keep-1.0.0.tgz'],
        ],
      ]
    );

    NpmLock::sync($manifest_file);

    $contents = (string) file_get_contents(dirname($manifest_file) . '/package-lock.json');

    $this->assertStringContainsString("\n  \"lockfileVersion\": 3,", $contents);
    $this->assertStringContainsString("\n    \"node_modules/keep\": {", $contents);
    $this->assertStringContainsString('https://registry.npmjs.org/keep/-/keep-1.0.0.tgz', $contents);
    $this->assertStringEndsWith("}\n", $contents);
  }

  public function testSyncPreservesEmptyObjects(): void {
    $manifest_file = $this->createPair(['dependencies' => ['keep' => '^1.0.0']], NULL);

    File::dump(dirname($manifest_file) . '/package-lock.json', '{"packages":{"":{"dependencies":{"keep":"^1.0.0"}},"node_modules/keep":{"version":"1.0.0","bin":{}}}}');

    NpmLock::sync($manifest_file);

    $this->assertStringContainsString('"bin": {}', (string) file_get_contents(dirname($manifest_file) . '/package-lock.json'));
  }

  #[DataProvider('dataProviderSyncThrows')]
  public function testSyncThrows(?string $manifest_contents, ?string $lock_contents, string $message): void {
    $dir = File::mkdir(self::$tmp . '/' . uniqid('npmlock_'));

    File::dump($dir . '/package.json', $manifest_contents ?? '{}');
    File::dump($dir . '/package-lock.json', $lock_contents ?? '{}');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches($message);

    NpmLock::sync($dir . '/package.json');
  }

  public static function dataProviderSyncThrows(): \Iterator {
    yield 'lock without a packages key' => [NULL, '{"lockfileVersion":1,"dependencies":{}}', '/Unsupported lock file format/'];
    yield 'lock without a root entry' => [NULL, '{"packages":{"node_modules/keep":{}}}', '/Unsupported lock file format/'];
    yield 'manifest with invalid JSON' => ['{"name":}', NULL, '/Unable to parse a JSON file/'];
    yield 'lock with invalid JSON' => [NULL, '{"packages":}', '/Unable to parse a JSON file/'];
    yield 'manifest that is not an object' => ['[]', NULL, '/a JSON object is required/'];
    yield 'manifest with a dependency block that is not an object' => ['{"devDependencies":[]}', '{"packages":{"":{}}}', '/Unable to read the "devDependencies" block/'];
  }

  public function testSyncThrowsWhenLockIsNotWritable(): void {
    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
      $this->markTestSkipped('File permissions do not restrict the root user.');
    }

    $manifest_file = $this->createPair(
      ['dependencies' => ['keep' => '^1.0.0']],
      ['packages' => ['' => ['dependencies' => ['keep' => '^1.0.0']]]]
    );

    chmod(dirname($manifest_file) . '/package-lock.json', 0444);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Unable to write a JSON file/');

    NpmLock::sync($manifest_file);
  }

  /**
   * Write a manifest and its lock file into a directory of their own.
   *
   * @return string
   *   Path to the manifest.
   */
  protected function createPair(array $manifest, ?array $lock): string {
    $dir = File::mkdir(self::$tmp . '/' . uniqid('npmlock_'));

    File::dump($dir . '/package.json', (string) json_encode($manifest, JSON_PRETTY_PRINT));

    if ($lock !== NULL) {
      File::dump($dir . '/package-lock.json', (string) json_encode($lock, JSON_PRETTY_PRINT));
    }

    return $dir . '/package.json';
  }

  protected function readLock(string $manifest_file): array {
    return (array) json_decode((string) file_get_contents(dirname($manifest_file) . '/package-lock.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
