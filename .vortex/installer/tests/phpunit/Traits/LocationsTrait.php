<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\FileDiff;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Trait ConsoleTrait.
 *
 * Helpers to work with Console.
 */
trait LocationsTrait {

  /**
   * Path to the fixtures directory from the repository root.
   */
  const FIXTURES_DIR = '.vortex/installer/tests/phpunit/Fixtures';

  /**
   * Path to the root directory of this project.
   */
  protected static string $root;

  /**
   * Path to the fixtures directory from the root of this project.
   */
  protected static string $fixtures;

  /**
   * Main build directory where the rest of the directories located.
   *
   * The "build" in this context is a place to store assets produced by a single
   * test run.
   */
  protected static string $build;

  /**
   * Directory used as a source in the operations.
   *
   * Could be a copy of the current repository with custom adjustments or a
   * fixture repository.
   */
  protected static string $repo;

  /**
   * Directory where the test will run.
   */
  protected static string $sut;

  /**
   * Initialize the locations.
   *
   * @param string $cwd
   *   The current working directory.
   * @param callable|null $cb
   *   Callback to run after initialization.
   * @param \PHPUnit\Framework\TestCase|null $test
   *   The test instance to pass to the callback.
   */
  protected static function locationsInit(string $cwd, ?callable $cb = NULL, ?TestCase $test = NULL): void {
    static::$root = File::dir($cwd, TRUE);
    static::$build = File::dir(rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vortex-' . microtime(TRUE), TRUE);
    static::$sut = File::dir(static::$build . '/star_wars', TRUE);
    static::$repo = File::dir(static::$build . '/local_repo', TRUE);

    if ($cb !== NULL && $cb instanceof \Closure) {
      \Closure::bind($cb, $test, self::class)();
    }
  }

  protected static function locationsTearDown():void {
    (new Filesystem())->remove(static::$build);
  }

  protected function locationsFixtureDir(?string $name = NULL): string {
    $path = File::dir(static::$root . DIRECTORY_SEPARATOR . static::FIXTURES_DIR);

    // Set the fixtures directory based on the passed name.
    if ($name) {
      $path .= DIRECTORY_SEPARATOR . $name;
    }
    else {
      // Set the fixtures directory based on the test name.
      $fixture_dir = $this->name();
      $fixture_dir = str_contains($fixture_dir, '::') ? explode('::', $fixture_dir)[1] : $fixture_dir;
      $fixture_dir = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $fixture_dir));
      $fixture_dir = str_replace('test_', '', $fixture_dir);
      $path .= DIRECTORY_SEPARATOR . $fixture_dir;
    }

    // Further adjust the fixtures directory name if the test uses a
    // data provider with named data sets.
    if (!empty($this->dataName()) && !is_numeric($this->dataName())) {
      $path .= DIRECTORY_SEPARATOR . Converter::machine((string) $this->dataName());
    }

    return File::dir($path);
  }

  protected static function locationsInfo(): string {
    $lines[] = '-- LOCATIONS --';
    $lines[] = 'Root       : ' . static::$root;
    $lines[] = 'Fixtures   : ' . static::$fixtures;
    $lines[] = 'Build      : ' . static::$build;
    $lines[] = 'Local repo : ' . static::$repo;
    $lines[] = 'SUT        : ' . static::$sut;
    return implode(PHP_EOL, $lines) . PHP_EOL;
  }

  protected static function locationsCopyFilesToSut(array $files, ?string $basedir = NULL, bool $append_rand = TRUE): array {
    $created = [];

    $fs = new Filesystem();
    foreach ($files as $file) {
      $basedir = $basedir ?? dirname((string) $file);
      $relative_dst = ltrim(str_replace($basedir, '', (string) $file), '/') . ($append_rand ? rand(1000, 9999) : '');
      $new_name = static::$sut . DIRECTORY_SEPARATOR . $relative_dst;
      $fs->copy($file, $new_name);
      $created[] = $new_name;
    }

    return $created;
  }

  protected function assertDirectoryContainsString(string $needle, string $directory, array $excluded = [], string $message = ''): void {
    $files = File::containsInDir($directory, $needle, $excluded);

    if (empty($files)) {
      $this->fail($message ?: sprintf('Directory should contain "%s", but it does not.', $needle));
    }
  }

  protected function assertDirectoryNotContainsString(string $needle, string $directory, array $excluded = [], string $message = ''): void {
    $files = File::containsInDir($directory, $needle, $excluded);

    if (!empty($files)) {
      $this->fail($message ?: sprintf('Directory should not contain "%s", but it does within files %s.', $needle, implode(', ', $files)));
    }
  }

  protected function assertDirectoryContainsWord(string $needle, string $directory, array $excluded = [], string $message = ''): void {
    $files = File::containsInDir($directory, '/\b' . preg_quote($needle) . '\b/i', $excluded);

    if (empty($files)) {
      $this->fail($message ?: sprintf('Directory should contain "%s" word, but it does not.', $needle));
    }
  }

  protected function assertDirectoryNotContainsWord(string $needle, string $directory, array $excluded = [], string $message = ''): void {
    $files = File::containsInDir($directory, '/\b' . preg_quote($needle) . '\b/i', $excluded);

    if (!empty($files)) {
      $this->fail($message ?: sprintf('Directory should not contain "%s" word, but it does within files %s.', $needle, implode(', ', $files)));
    }
  }

  protected function assertDirectoriesEqual(string $dir1, string $dir2, ?string $message = NULL, ?callable $match_content = NULL): void {
    $text = FileDiff::compareRendered($dir1, $dir2, $match_content);
    if (!empty($text)) {
      $this->fail($message ? $message . PHP_EOL . $text : $text);
    }
    $this->assertTrue(TRUE, $message ?: '');
  }

  /**
   * Assert that the system under test is equal to the baseline + diff.
   */
  protected function assertBaselineDiffs(string $baseline_dir, string $diff_dir, ?string $expected_dir = NULL): void {
    if (!is_dir($baseline_dir)) {
      throw new \RuntimeException('The baseline directory does not exist: ' . $baseline_dir);
    }

    // We use the .expected dir to easily assess the combined expected fixture.
    // @todo Review the performance of this approach as removing the directory
    // may slow down the tests.
    $expected_dir = $expected_dir ?: File::realpath($baseline_dir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.expected');
    if (File::exists($expected_dir)) {
      File::rmdir($expected_dir);
    }
    File::dir($expected_dir, TRUE);

    File::sync($baseline_dir, $expected_dir);
    File::sync($diff_dir, $expected_dir);

    // Do not override .ignorecontent file from the baseline directory.
    if (file_exists($baseline_dir . DIRECTORY_SEPARATOR . File::IGNORECONTENT)) {
      File::copy($baseline_dir . DIRECTORY_SEPARATOR . File::IGNORECONTENT, $expected_dir . DIRECTORY_SEPARATOR . File::IGNORECONTENT);
    }

    $dirs = [];
    $files = [];
    $links = [];
    $markers = [];

    // Find all negative markers and remove corresponding files or directories.
    foreach ((new Finder())->in($expected_dir)->ignoreDotFiles(FALSE)->files()->name('-*') as $file) {
      if ($file->isFile()) {
        $negative_marker = $file->getRealPath();
        $relative = str_replace($expected_dir . DIRECTORY_SEPARATOR, '', $negative_marker);
        $path = $expected_dir . DIRECTORY_SEPARATOR . (str_starts_with($relative, '-') ? substr($relative, 1) : $relative);
        $path = str_replace('/-', '/', $path);

        // Find and remove symlinks pointing to this directory.
        $link_finder = new Finder();
        $link_finder->in($expected_dir)->ignoreDotFiles(FALSE)->followLinks()->directories()->filter(function (\SplFileInfo $link) use ($path): bool {
          $r1 = realpath($link->getPathname());
          $r2 = realpath($path);
          return $link->isLink() && $r1 === $r2;
        });

        foreach ($link_finder as $link) {
          $links[] = $link->getPathname();
        }

        // Remove negative file or directory.
        if (is_file($path)) {
          $files[] = $path;
        }
        else {
          $dirs[] = $path;
        }

        $markers[] = $negative_marker;
      }
    }

    foreach ($links as $link) {
      @unlink($link);
    }

    foreach ($files as $file) {
      @unlink($file);
    }

    foreach ($markers as $marker) {
      @unlink($marker);
    }

    foreach ($dirs as $dir) {
      File::rmdir($dir);
    }

    $this->assertDirectoriesEqual($expected_dir, static::$sut);
  }

  protected static function updateBaselineDiffs(string $baseline_dir, string $actual_dir, ?string $diff_dir = NULL): void {
    if (!is_dir($baseline_dir)) {
      throw new \RuntimeException('The baseline directory does not exist: ' . $baseline_dir);
    }

    if (!is_dir($actual_dir)) {
      throw new \RuntimeException('The actual directory does not exist: ' . $actual_dir);
    }

    if (!is_dir($diff_dir)) {
      File::dir($diff_dir, TRUE);
    }

    $iterator = Finder::create()->in($actual_dir)->ignoreVCS(TRUE)->ignoreDotFiles(FALSE)->filter(static function (\SplFileInfo $file) use ($actual_dir, $diff_dir, $baseline_dir): bool {
      $relative_path = str_replace(realpath($actual_dir) . DIRECTORY_SEPARATOR, '', $file->getRealPath());

      $file_exists_fixture = file_exists($diff_dir . DIRECTORY_SEPARATOR . $relative_path);
      $file_exists_base = file_exists($baseline_dir . DIRECTORY_SEPARATOR . $relative_path);
      // @todo Remove the hardcoded paths below and reapply the .ignorecontent
      // file logic.
      $file_is_ignored =
        str_starts_with($relative_path, 'scripts/vortex')
        ||
        in_array($relative_path, [
          'web/themes/custom/star_wars/logo.png',
          'web/themes/custom/star_wars/screenshot.png',
          'tests/behat/fixtures/image.jpg',
        ]);

      return !$file_is_ignored && ($file_exists_fixture || !$file_exists_base || getenv('UPDATE_TEST_FIXTURES_ALL'));
    })->files()->getIterator();
    (new Filesystem())->mirror($actual_dir, static::$fixtures, $iterator);
  }

}
