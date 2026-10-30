<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Diff;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\InstallReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InstallReport::class)]
#[CoversClass(Diff::class)]
class InstallReportTest extends UnitTestCase {

  /**
   * Time of the installation used across the tests.
   */
  const TIMESTAMP = 1793347200;

  public function testWriteWithoutEntries(): void {
    $report = new InstallReport(self::$sut);

    $this->assertTrue($report->isEmpty());
    $this->assertSame([], $report->summary());
    $this->assertNull($report->write('1.40.0', '1.41.0', self::TIMESTAMP));
    $this->assertDirectoryDoesNotExist(self::$sut . '/' . InstallReport::DIR);
  }

  public function testAddRejectsUnknownStatus(): void {
    $report = new InstallReport(self::$sut);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown install report status "copied".');

    $report->add('composer.json', 'copied');
  }

  public function testAddChangeRejectsPathWithoutStatus(): void {
    $report = new InstallReport(self::$sut);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('No status is recorded for "composer.json".');

    $report->addChange('composer.json', 'a', 'b');
  }

  public function testAddReplacesStatusOfSamePath(): void {
    $report = new InstallReport(self::$sut);
    $report->add('composer.json', InstallReport::STATUS_ADDED);
    $report->add('composer.json', InstallReport::STATUS_UPDATED);

    $this->assertSame(['composer.json' => InstallReport::STATUS_UPDATED], $report->getEntries());
  }

  public function testAddDiscardsChangeOfPreviousStatus(): void {
    File::dump(self::$sut . '/composer.json', 'installed');

    $report = new InstallReport(self::$sut);
    $report->add('composer.json', InstallReport::STATUS_UPDATED);
    $report->addChange('composer.json', 'installed', 'next');
    $report->add('composer.json', InstallReport::STATUS_FAILED);

    $file = (string) $report->write(NULL, '1.41.0', self::TIMESTAMP);

    $this->assertFileContainsString($file, '| `failed` | [composer.json](../composer.json) |  |');
    $this->assertFileDoesNotExist(substr($file, 0, -3) . '/composer.json.diff', 'A change recorded for an earlier status is not written.');
  }

  public function testGetEntriesSortsByPath(): void {
    $report = new InstallReport(self::$sut);
    $report->add('phpstan.neon', InstallReport::STATUS_UPDATED);
    $report->add('.circleci/config.yml', InstallReport::STATUS_ADDED);
    $report->add('composer.json', InstallReport::STATUS_OVERWRITTEN);

    $this->assertSame(['.circleci/config.yml', 'composer.json', 'phpstan.neon'], array_keys($report->getEntries()));
  }

  /**
   * @param array<string, string> $entries
   *   Statuses keyed by path.
   * @param array<string, int> $expected
   *   Expected counts keyed by status.
   */
  #[DataProvider('dataProviderSummary')]
  public function testSummary(array $entries, array $expected): void {
    $report = new InstallReport(self::$sut);

    foreach ($entries as $path => $status) {
      $report->add($path, $status);
    }

    $this->assertSame($expected, $report->summary());
  }

  public static function dataProviderSummary(): \Iterator {
    yield 'single status' => [
      ['a' => InstallReport::STATUS_ADDED, 'b' => InstallReport::STATUS_ADDED],
      [InstallReport::STATUS_ADDED => 2],
    ];
    yield 'render order, not insertion order' => [
      ['a' => InstallReport::STATUS_KEPT, 'b' => InstallReport::STATUS_UPDATED, 'c' => InstallReport::STATUS_ADDED],
      [InstallReport::STATUS_ADDED => 1, InstallReport::STATUS_UPDATED => 1, InstallReport::STATUS_KEPT => 1],
    ];
    yield 'every status' => [
      [
        'a' => InstallReport::STATUS_ADDED,
        'b' => InstallReport::STATUS_UPDATED,
        'c' => InstallReport::STATUS_UNCHANGED,
        'd' => InstallReport::STATUS_OVERWRITTEN,
        'e' => InstallReport::STATUS_REMOVED,
        'f' => InstallReport::STATUS_KEPT,
        'g' => InstallReport::STATUS_UNCHANGED,
        'h' => InstallReport::STATUS_FAILED,
      ],
      [
        InstallReport::STATUS_ADDED => 1,
        InstallReport::STATUS_UPDATED => 1,
        InstallReport::STATUS_UNCHANGED => 2,
        InstallReport::STATUS_OVERWRITTEN => 1,
        InstallReport::STATUS_REMOVED => 1,
        InstallReport::STATUS_KEPT => 1,
        InstallReport::STATUS_FAILED => 1,
      ],
    ];
  }

  public function testWriteRendersReport(): void {
    File::dump(self::$sut . '/phpstan.neon', "level: 9\n");
    File::dump(self::$sut . '/composer.json', '{}');

    $report = new InstallReport(self::$sut);
    $report->add('phpstan.neon', InstallReport::STATUS_OVERWRITTEN);
    $report->addChange('phpstan.neon', "level: 8\n", "level: 9\n");
    $report->add('composer.json', InstallReport::STATUS_UNCHANGED);
    $report->add('jest.config.js', InstallReport::STATUS_REMOVED);
    $report->addChange('jest.config.js', "module.exports = {};\n", NULL);

    $file = $report->write('1.40.0', '1.41.0', self::TIMESTAMP);

    $name = 'vortex-install-' . date('Y-m-d-His', self::TIMESTAMP);
    $this->assertSame(self::$sut . '/.logs/' . $name . '.md', $file);
    $this->assertFalse($report->isEmpty());

    $content = File::read($file);

    $this->assertStringStartsWith('# Vortex installation report' . PHP_EOL, $content);
    $this->assertStringContainsString('- **From:** 1.40.0' . PHP_EOL, $content);
    $this->assertStringContainsString('- **To:** 1.41.0' . PHP_EOL, $content);
    $this->assertStringContainsString('- **Date:** ' . date('Y-m-d H:i:s', self::TIMESTAMP) . PHP_EOL, $content);

    foreach (InstallReport::STATUSES as $status => $description) {
      $this->assertStringContainsString(sprintf('| `%s` | %s |', $status, $description), $content, 'Every status is described.');
    }

    $this->assertStringContainsString('| `unchanged` | 1 |' . PHP_EOL . '| `overwritten` | 1 |' . PHP_EOL . '| `removed` | 1 |' . PHP_EOL, $content);

    $expected_rows = [
      '| `unchanged` | [composer.json](../composer.json) |  |',
      sprintf('| `removed` | `jest.config.js` | [diff](%s/jest.config.js.diff) |', $name),
      sprintf('| `overwritten` | [phpstan.neon](../phpstan.neon) | [diff](%s/phpstan.neon.diff) |', $name),
    ];
    $this->assertStringContainsString(implode(PHP_EOL, $expected_rows) . PHP_EOL, $content, 'Rows are sorted by path and link to the file and its diff.');

    $diff_dir = self::$sut . '/.logs/' . $name;
    $this->assertStringEqualsFile($diff_dir . '/phpstan.neon.diff', implode(PHP_EOL, ['--- a/phpstan.neon', '+++ b/phpstan.neon', '@@ -1 +1 @@', '-level: 8', '+level: 9']) . PHP_EOL);
    $this->assertStringEqualsFile($diff_dir . '/jest.config.js.diff', implode(PHP_EOL, ['--- a/jest.config.js', '+++ /dev/null', '@@ -1 +1,0 @@', '-module.exports = {};']) . PHP_EOL);
    $this->assertFileDoesNotExist($diff_dir . '/composer.json.diff', 'An unchanged file has no diff.');
  }

  public function testWriteMirrorsNestedPathsAndEncodesLinks(): void {
    File::dump(self::$sut . '/.github/workflows/build test.yml', "b\n");

    $report = new InstallReport(self::$sut);
    $report->add('.github/workflows/build test.yml', InstallReport::STATUS_UPDATED);
    $report->addChange('.github/workflows/build test.yml', "a\n", "b\n");

    $file = (string) $report->write(NULL, '1.41.0', self::TIMESTAMP);
    $name = 'vortex-install-' . date('Y-m-d-His', self::TIMESTAMP);

    $this->assertFileExists(self::$sut . '/.logs/' . $name . '/.github/workflows/build test.yml.diff');
    $this->assertFileContainsString($file, sprintf('[.github/workflows/build test.yml](../.github/workflows/build%%20test.yml) | [diff](%s/.github/workflows/build%%20test.yml.diff)', $name));
  }

  /**
   * @param string $from
   *   Content before the installation.
   * @param string|null $to
   *   Content after the installation.
   * @param string $expected
   *   Text shown in place of the diff link.
   */
  #[DataProvider('dataProviderWriteSkipsUndiffableChanges')]
  public function testWriteSkipsUndiffableChanges(string $from, ?string $to, string $expected): void {
    $report = new InstallReport(self::$sut);
    $report->add('file.bin', InstallReport::STATUS_UPDATED);
    $report->addChange('file.bin', $from, $to);

    $file = (string) $report->write(NULL, '1.41.0', self::TIMESTAMP);

    $this->assertFileContainsString($file, sprintf('| `file.bin` | %s |', $expected));
    $this->assertFileDoesNotExist(substr($file, 0, -3) . '/file.bin.diff');
  }

  public static function dataProviderWriteSkipsUndiffableChanges(): \Iterator {
    yield 'binary before' => ["\x89PNG\0old", 'new', Diff::SKIP_BINARY];
    yield 'binary after' => ['old', "\x89PNG\0new", Diff::SKIP_BINARY];
    yield 'too large' => [str_repeat('a', Diff::MAX_BYTES + 1), 'new', Diff::SKIP_TOO_LARGE];
    yield 'too large removal' => [str_repeat('a', Diff::MAX_BYTES + 1), NULL, Diff::SKIP_TOO_LARGE];
  }

  public function testWriteKeepsReportsOfTheSameSecond(): void {
    $first = new InstallReport(self::$sut);
    $first->add('composer.json', InstallReport::STATUS_ADDED);

    $second = new InstallReport(self::$sut);
    $second->add('composer.json', InstallReport::STATUS_UNCHANGED);

    $third = new InstallReport(self::$sut);
    $third->add('composer.json', InstallReport::STATUS_UPDATED);
    $third->addChange('composer.json', "a\n", "b\n");

    $base = sprintf('%s/.logs/vortex-install-%s', self::$sut, date('Y-m-d-His', self::TIMESTAMP));

    $this->assertSame($base . '.md', $first->write(NULL, '1.41.0', self::TIMESTAMP));
    $this->assertSame($base . '-1.md', $second->write(NULL, '1.41.0', self::TIMESTAMP));
    $this->assertSame($base . '-2.md', $third->write(NULL, '1.41.0', self::TIMESTAMP));
    $this->assertFileContainsString($base . '.md', '| `added` | `composer.json` |', 'A later run leaves the earlier report intact.');
    $this->assertFileContainsString($base . '-1.md', '| `unchanged` | `composer.json` |');
    $this->assertFileExists($base . '-2/composer.json.diff', 'Each report has its own diff directory.');
  }

  public function testWriteSkipsNameTakenByDiffDirectory(): void {
    $base = sprintf('%s/.logs/vortex-install-%s', self::$sut, date('Y-m-d-His', self::TIMESTAMP));
    File::mkdir($base);

    $report = new InstallReport(self::$sut);
    $report->add('composer.json', InstallReport::STATUS_ADDED);

    $this->assertSame($base . '-1.md', $report->write(NULL, '1.41.0', self::TIMESTAMP), 'A leftover diff directory is not reused.');
  }

  /**
   * @param string|null $from
   *   Version the project ran.
   */
  #[DataProvider('dataProviderWriteOmitsUnknownPreviousVersion')]
  public function testWriteOmitsUnknownPreviousVersion(?string $from): void {
    $report = new InstallReport(self::$sut);
    $report->add('composer.json', InstallReport::STATUS_ADDED);

    $file = (string) $report->write($from, '1.41.0', self::TIMESTAMP);

    $this->assertFileNotContainsString($file, '**From:**');
    $this->assertFileContainsString($file, '- **To:** 1.41.0');
  }

  public static function dataProviderWriteOmitsUnknownPreviousVersion(): \Iterator {
    yield 'fresh installation' => [NULL];
    yield 'empty version' => [''];
  }

}
