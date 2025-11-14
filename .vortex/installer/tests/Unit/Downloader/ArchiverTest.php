<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Downloader\Archiver;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Archiver::class)]
class ArchiverTest extends UnitTestCase {

  protected Archiver $archiver;

  protected function setUp(): void {
    parent::setUp();
    $this->archiver = new Archiver();
  }

  #[DataProvider('providerDetectFormat')]
  public function testDetectFormat(string $creator, string $expected): void {
    $archive_path = $this->$creator();
    $format = $this->archiver->detectFormat($archive_path);
    $this->assertEquals($expected, $format);
  }

  public function testDetectFormatInvalidFile(): void {
    $temp_file = self::$tmp . '/test_invalid.txt';
    File::dump($temp_file, 'This is not an archive');
    $format = $this->archiver->detectFormat($temp_file);
    $this->assertNull($format);
  }

  public function testDetectFormatNonExistentFile(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to read archive file');
    $this->archiver->detectFormat('/non/existent/file.tar.gz');
  }

  #[DataProvider('providerValidateValidArchive')]
  public function testValidateValidArchive(string $creator): void {
    $archive_path = $this->$creator();
    $this->archiver->validate($archive_path);
    $this->expectNotToPerformAssertions();
  }

  #[DataProvider('providerValidateInvalid')]
  public function testValidateInvalid(?string $path, ?string $content, string $expectedMessage): void {
    if ($path === NULL) {
      $path = self::$tmp . '/test_invalid_' . uniqid() . '.txt';
      if ($content !== NULL) {
        File::dump($path, $content);
      }
    }

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($expectedMessage);
    $this->archiver->validate($path);
  }

  #[DataProvider('providerExtract')]
  public function testExtract(string $creator, bool $strip, string $expectedPath): void {
    $archive_path = $this->$creator();
    $destination = self::$tmp . '/test_extract_' . uniqid();
    File::mkdir($destination);

    $this->archiver->extract($archive_path, $destination, $strip);

    $this->assertFileExists($destination . $expectedPath);
    $this->assertEquals('Test content', file_get_contents($destination . $expectedPath));

    if ($strip) {
      $this->assertFileDoesNotExist($destination . '/test_archive');
    }
  }

  #[DataProvider('providerExtractErrors')]
  public function testExtractErrors(?string $extension, ?string $content, bool $strip, ?string $creator, string $expectedMessage): void {
    if ($creator !== NULL) {
      $archive_path = $this->$creator();
    }
    else {
      $archive_path = self::$tmp . '/test_invalid_' . uniqid() . $extension;
      File::dump($archive_path, $content);
    }

    $destination = self::$tmp . '/test_extract_' . uniqid();
    File::mkdir($destination);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($expectedMessage);
    $this->archiver->extract($archive_path, $destination, $strip);
  }

  /**
   * Data provider for testDetectFormat().
   *
   * @return array<string, array<string, string|callable>>
   *   Test data.
   */
  public static function providerDetectFormat(): array {
    return [
      'tar.gz' => [
        'creator' => 'createTestTarGz',
        'expected' => 'tar.gz',
      ],
      'tar' => [
        'creator' => 'createTestTar',
        'expected' => 'tar',
      ],
      'zip' => [
        'creator' => 'createTestZip',
        'expected' => 'zip',
      ],
    ];
  }

  /**
   * Data provider for testValidateValidArchive().
   *
   * @return array<string, array<string, string>>
   *   Test data.
   */
  public static function providerValidateValidArchive(): array {
    return [
      'tar.gz' => [
        'creator' => 'createTestTarGz',
      ],
      'zip' => [
        'creator' => 'createTestZip',
      ],
    ];
  }

  /**
   * Data provider for testValidateInvalid().
   *
   * @return array<string, array<string, string|null>>
   *   Test data.
   */
  public static function providerValidateInvalid(): array {
    return [
      'non-existent file' => [
        'path' => '/non/existent/file.tar.gz',
        'content' => NULL,
        'expectedMessage' => 'Archive file does not exist',
      ],
      'empty file' => [
        'path' => NULL,
        'content' => '',
        'expectedMessage' => 'Archive is empty',
      ],
      'invalid archive' => [
        'path' => NULL,
        'content' => 'This is not an archive',
        'expectedMessage' => 'File does not appear to be a valid archive',
      ],
    ];
  }

  /**
   * Data provider for testExtract().
   *
   * @return array<string, array<string, string|bool>>
   *   Test data.
   */
  public static function providerExtract(): array {
    return [
      'tar.gz without strip' => [
        'creator' => 'createTestTarGz',
        'strip' => FALSE,
        'expectedPath' => '/test_archive/test_file.txt',
      ],
      'tar.gz with strip' => [
        'creator' => 'createTestTarGz',
        'strip' => TRUE,
        'expectedPath' => '/test_file.txt',
      ],
      'zip without strip' => [
        'creator' => 'createTestZip',
        'strip' => FALSE,
        'expectedPath' => '/test_archive/test_file.txt',
      ],
      'zip with strip' => [
        'creator' => 'createTestZip',
        'strip' => TRUE,
        'expectedPath' => '/test_file.txt',
      ],
    ];
  }

  /**
   * Data provider for testExtractErrors().
   *
   * @return array<string, array<string, string|bool|null>>
   *   Test data.
   */
  public static function providerExtractErrors(): array {
    return [
      'unsupported format' => [
        'extension' => '.rar',
        'content' => 'Rar! fake content',
        'strip' => FALSE,
        'creator' => NULL,
        'expectedMessage' => 'Unsupported archive format',
      ],
      'invalid tar.gz archive' => [
        'extension' => '.tar.gz',
        'content' => "\x1f\x8b" . 'invalid tar content',
        'strip' => FALSE,
        'creator' => NULL,
        'expectedMessage' => 'Failed to extract tar archive',
      ],
      'invalid zip archive' => [
        'extension' => '.zip',
        'content' => "\x50\x4b\x03\x04invalid zip content",
        'strip' => FALSE,
        'creator' => NULL,
        'expectedMessage' => 'Failed to extract ZIP archive',
      ],
      'multiple top-level directories with strip' => [
        'extension' => NULL,
        'content' => NULL,
        'strip' => TRUE,
        'creator' => 'createTestMultipleTopLevel',
        'expectedMessage' => 'Expected single top-level directory in archive',
      ],
    ];
  }

  protected function createTestTarGz(): string {
    $temp_dir = self::$tmp . '/test_archive_' . uniqid();
    $archive_dir = $temp_dir . '/test_archive';
    File::mkdir($archive_dir);

    File::dump($archive_dir . '/test_file.txt', 'Test content');

    $archive_path = self::$tmp . '/test_archive_' . uniqid() . '.tar.gz';

    $phar = new \PharData($temp_dir . '/test.tar');
    $phar->buildFromDirectory($temp_dir);
    $phar->compress(\Phar::GZ);

    rename($temp_dir . '/test.tar.gz', $archive_path);

    return $archive_path;
  }

  protected function createTestTar(): string {
    $temp_dir = self::$tmp . '/test_archive_' . uniqid();
    $archive_dir = $temp_dir . '/test_archive';
    File::mkdir($archive_dir);

    File::dump($archive_dir . '/test_file.txt', 'Test content');

    $archive_path = self::$tmp . '/test_archive_' . uniqid() . '.tar';

    $phar = new \PharData($archive_path);
    $phar->buildFromDirectory($temp_dir);

    return $archive_path;
  }

  protected function createTestZip(): string {
    $temp_dir = self::$tmp . '/test_archive_' . uniqid();
    $archive_dir = $temp_dir . '/test_archive';
    File::mkdir($archive_dir);

    File::dump($archive_dir . '/test_file.txt', 'Test content');

    $archive_path = self::$tmp . '/test_archive_' . uniqid() . '.zip';

    $zip = new \ZipArchive();
    $zip->open($archive_path, \ZipArchive::CREATE);
    $zip->addFile($archive_dir . '/test_file.txt', 'test_archive/test_file.txt');
    $zip->close();

    return $archive_path;
  }

  protected function createTestMultipleTopLevel(): string {
    $temp_dir = self::$tmp . '/test_archive_' . uniqid();
    File::mkdir($temp_dir);
    File::mkdir($temp_dir . '/dir1');
    File::mkdir($temp_dir . '/dir2');
    File::dump($temp_dir . '/dir1/file1.txt', 'Test 1');
    File::dump($temp_dir . '/dir2/file2.txt', 'Test 2');

    $archive_path = self::$tmp . '/test_archive_' . uniqid() . '.tar';
    $phar = new \PharData($archive_path);
    $phar->buildFromDirectory($temp_dir);

    return $archive_path;
  }

}
