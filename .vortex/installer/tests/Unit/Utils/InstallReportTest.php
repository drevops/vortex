<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\InstallReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InstallReport::class)]
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

  public function testAddReplacesStatusOfSamePath(): void {
    $report = new InstallReport(self::$sut);
    $report->add('composer.json', InstallReport::STATUS_ADDED);
    $report->add('composer.json', InstallReport::STATUS_UPDATED);

    $this->assertSame(['composer.json' => InstallReport::STATUS_UPDATED], $report->getEntries());
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
      ],
      [
        InstallReport::STATUS_ADDED => 1,
        InstallReport::STATUS_UPDATED => 1,
        InstallReport::STATUS_UNCHANGED => 2,
        InstallReport::STATUS_OVERWRITTEN => 1,
        InstallReport::STATUS_REMOVED => 1,
        InstallReport::STATUS_KEPT => 1,
      ],
    ];
  }

  public function testWriteRendersReport(): void {
    $report = new InstallReport(self::$sut);
    $report->add('phpstan.neon', InstallReport::STATUS_OVERWRITTEN);
    $report->add('composer.json', InstallReport::STATUS_UPDATED);
    $report->add('jest.config.js', InstallReport::STATUS_REMOVED);

    $file = $report->write('1.40.0', '1.41.0', self::TIMESTAMP);

    $this->assertSame(sprintf('%s/.logs/vortex-install-%s.txt', self::$sut, date('Y-m-d-His', self::TIMESTAMP)), $file);
    $this->assertFalse($report->isEmpty());

    $content = File::read((string) $file);

    $this->assertStringStartsWith('Vortex installation report' . PHP_EOL, $content);
    $this->assertStringContainsString('From: 1.40.0' . PHP_EOL, $content);
    $this->assertStringContainsString('To:   1.41.0' . PHP_EOL, $content);
    $this->assertStringContainsString('Date: ' . date('Y-m-d H:i:s', self::TIMESTAMP) . PHP_EOL, $content);

    foreach (InstallReport::STATUSES as $status => $description) {
      $this->assertStringContainsString(sprintf('  %s%s', str_pad($status, 13), $description), $content, 'Every status is described.');
    }

    $this->assertStringContainsString('Summary:' . PHP_EOL . '  updated      1' . PHP_EOL . '  overwritten  1' . PHP_EOL . '  removed      1' . PHP_EOL, $content);
    $this->assertStringContainsString('Files:' . PHP_EOL . '  updated      composer.json' . PHP_EOL . '  removed      jest.config.js' . PHP_EOL . '  overwritten  phpstan.neon' . PHP_EOL, $content);
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

    $this->assertFileNotContainsString($file, 'From:');
    $this->assertFileContainsString($file, 'To:   1.41.0');
  }

  public static function dataProviderWriteOmitsUnknownPreviousVersion(): \Iterator {
    yield 'fresh installation' => [NULL];
    yield 'empty version' => [''];
  }

}
