<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

use Composer\Console\Application;
use DrevOps\Installer\Command\InstallCommand;
use DrevOps\Installer\Tests\Traits\ConsoleTrait;
use DrevOps\Installer\Tests\Traits\TuiTrait;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Base class for functional tests.
 */
abstract class FunctionalTestBase extends TestCase {

  use TuiTrait;
  use ConsoleTrait;

  /**
   * TUI answer to indicate that the user did not provide any input.
   */
  const TUI_ANSWER_NOTHING = 'NOTHING';

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
   * The file system.
   */
  protected Filesystem $fs;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tuiSetUp();
    $this->consoleInitApplicationTester(InstallCommand::class);
    //    $this->initTester();

    $this->initLocations((string) getcwd() . '/../../');

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  /**
   * Initialize the Composer command tester.
   */
  protected function initTester(): void {
    // @see https://github.com/composer/composer/issues/12107
    if (!defined('STDIN')) {
      define('STDIN', fopen('php://stdin', 'r'));
    }

    $application = new Application();
    $application->setAutoExit(FALSE);
    $application->setCatchExceptions(FALSE);
    $application->setCatchErrors(FALSE);

    $this->tester = new ApplicationTester($application);

    // Composer autoload uses per-project Composer binary, if the
    // `composer/composer` is included in the project as a dependency.
    //
    // When a test creates SUT, the Composer binary used is from the SUT's
    // `vendor` directory. The Customizer may remove the
    // `vendor/composer/composer` directory as a part of the cleanup, resulting
    // in the Composer autoloader having an empty path to the Composer binary.
    //
    // This is extremely difficult to debug, because there is no clear error
    // message apart from `Could not open input file`.
    //
    // To prevent this, we set the `COMPOSER_BINARY` environment variable to the
    // Composer binary path found in the system.
    // @see \Composer\EventDispatcher::doDispatch().
    $composer_bin = shell_exec(escapeshellcmd('which composer'));
    if ($composer_bin === FALSE) {
      throw new \RuntimeException('Composer binary not found');
    }
    putenv('COMPOSER_BINARY=' . trim((string) $composer_bin));
  }

  /**
   * Initialize the locations.
   *
   * @param string $cwd
   *   The current working directory.
   * @param callable|null $cb
   *   Callback to run after initialization.
   */
  protected function initLocations(string $cwd, ?callable $cb = NULL): void {
    $this->fs = new Filesystem();

    static::$root = (string) realpath($cwd);
    if (!is_dir(static::$root)) {
      throw new \RuntimeException('The repository root directory does not exist: ' . static::$root);
    }

    static::$fixtures = static::$root . DIRECTORY_SEPARATOR . static::FIXTURES_DIR;
    if (!is_dir(static::$fixtures)) {
      throw new \RuntimeException('The fixtures directory does not exist: ' . static::$fixtures);
    }

    static::$build = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vortex-' . microtime(TRUE);
    static::$sut = static::$build . '/sut';
    static::$repo = static::$build . '/local_repo';

    $this->fs->mkdir(static::$build);
    $this->fs->mkdir(static::$sut);
    $this->fs->mkdir(static::$repo);

    // Set the fixtures directory based on the test name.
    $fixture_dir = $this->name();
    $fixture_dir = str_contains($fixture_dir, '::') ? explode('::', $fixture_dir)[1] : $fixture_dir;
    $fixture_dir = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $fixture_dir));
    $fixture_dir = str_replace('test_', '', $fixture_dir);
    static::$fixtures .= DIRECTORY_SEPARATOR . $fixture_dir;

    // Further adjust the fixtures directory name if the test uses a
    // data provider with named data sets.
    if ($this->usesDataProvider() && !empty($this->dataName())) {
      static::$fixtures .= DIRECTORY_SEPARATOR . $this->dataName();
    }

    // Copy the 'base' fixture to the 'local' fixture.
    if (is_dir(static::$fixtures)) {
      $base_dir = static::$fixtures . DIRECTORY_SEPARATOR . 'base';

      // Use this project's root directory as a base directory if the 'base'
      // fixture was not provided. This allows to use the current project's
      // files as a 'base' for the test.
      //
      // @note Composer uses .gitattributes to determine which files to include
      // in the package when running `create-project`, so add the files that are
      // not intended to be used in the consumer to the .gitattributes file
      // of this project.
      $allowed_files = [];
      if (!is_dir($base_dir)) {
        $base_dir = static::$root;
        // Only use the git-tracked files to replicate a "clean" project as it
        // would be seen by Composer at the code repository.
        // Make sure to commit the changes locally before running
        // the tests (even as a temporary commit).
        $allowed_files = $this->getTrackedFiles($base_dir);
      }

      $this->mirrorFiltered($base_dir, static::$repo, $allowed_files);
    }

    if ($cb !== NULL && $cb instanceof \Closure) {
      \Closure::bind($cb, $this, self::class)();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->tuiTearDown();

    // Clean up the directories if the test passed.
    if (!$this->status() instanceof Failure && !$this->status() instanceof Error && isset($this->fs)) {
      $this->fs->remove(static::$build);
    }

    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): never {
    // Print the locations information and the exception message.
    $lines[] = '-- LOCATIONS --';
    $lines[] = 'Root       : ' . static::$root;
    $lines[] = 'Fixtures   : ' . static::$fixtures;
    $lines[] = 'Build      : ' . static::$build;
    $lines[] = 'Local repo : ' . static::$repo;
    $lines[] = 'SUT        : ' . static::$sut;
    $info = implode(PHP_EOL, $lines) . PHP_EOL;

    fwrite(STDERR, 'see below' . PHP_EOL . PHP_EOL . $info . PHP_EOL . $t->getMessage() . PHP_EOL);

    parent::onNotSuccessfulTest($t);
  }

  /**
   * Run an arbitrary command.
   *
   * @param string $cmd
   *   The command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param array $env
   *   Environment variables to define for the subprocess.
   *
   * @return string
   *   Standard output from the command
   */
  protected static function runCmd(string $cmd, ?string $cwd, array $env = []): string {
    $env += $env + ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME')];

    $process = Process::fromShellCommandline($cmd, $cwd, $env);
    $process->setTimeout(300)->setIdleTimeout(300)->run();

    $code = $process->getExitCode();
    if (0 != $code) {
      throw new \RuntimeException("Exit code: {$code}\n\n" . $process->getErrorOutput() . "\n\n" . $process->getOutput());
    }

    return $process->getOutput();
  }

  /**
   * Get the tracked files in a Git repository.
   *
   * @param string $dir
   *   The directory to check.
   *
   * @return array<string>
   *   The list of tracked files.
   *
   * @throws \RuntimeException
   *   If the directory is not a Git repository.
   */
  protected function getTrackedFiles(string $dir): array {
    if (!is_dir($dir . '/.git')) {
      throw new \RuntimeException("The directory is not a Git repository.");
    }

    $tracked_files = [];
    $output = [];
    $code = 0;
    $command = sprintf("cd %s && git ls-files", escapeshellarg($dir));
    exec($command, $output, $code);
    if ($code !== 0) {
      throw new \RuntimeException("Failed to retrieve tracked files using git ls-files.");
    }

    foreach ($output as $file) {
      $tracked_files[] = $dir . DIRECTORY_SEPARATOR . $file;
    }

    return $tracked_files;
  }

  /**
   * Mirror a directory with filtering.
   *
   * @param string $src
   *   The source directory.
   * @param string $dst
   *   The destination directory.
   * @param array<string> $allowed_files
   *   The list of allowed files.
   */
  protected function mirrorFiltered(string $src, string $dst, array $allowed_files = []): void {
    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
    );

    foreach ($files as $file) {
      if (!$file instanceof \SplFileInfo) {
        continue;
      }

      if (!empty($allowed_files) && !in_array($file->getPathname(), $allowed_files, TRUE)) {
        continue;
      }

      $relative_path = substr($file->getPathname(), strlen($src) + 1);
      $target_path = $dst . DIRECTORY_SEPARATOR . $relative_path;

      if (!is_dir(dirname($target_path))) {
        mkdir(dirname($target_path), 0755, TRUE);
      }

      // Always copy the contents of the file as a regular file.
      if (is_link($file->getPathname())) {
        // Resolve the symlink to the actual file content.
        $resolved_path = realpath($file->getPathname());
        if ($resolved_path !== FALSE) {
          copy($resolved_path, $target_path);
        }
        else {
          throw new \RuntimeException("Failed to resolve symlink for: " . $file->getPathname());
        }
      }
      else {
        // Copy regular files.
        copy($file->getPathname(), $target_path);
      }
    }
  }

  /**
   * Assert that the Composer lock file is up to date.
   */
  protected function assertComposerLockUpToDate(): void {
    if (!empty(getenv('UPDATE_TEST_FIXTURES'))) {
      return;
    }

    $this->assertFileExists('composer.lock');

    static::runCmd('composer validate', static::$sut);
  }

  /**
   * Assert that the Composer JSON files match.
   *
   * @param string $expected
   *   The expected file.
   * @param string $actual
   *   The actual file.
   */
  protected function assertComposerJsonFilesEqual(string $expected, string $actual): void {
    $this->assertFileExists($expected);
    $this->assertFileExists($actual);

    $expected = json_decode((string) file_get_contents($expected), TRUE);

    // Remove test data.
    $data = json_decode((string) file_get_contents($actual), TRUE);
    if (!is_array($data)) {
      $this->fail('The actual file is not a valid JSON file.');
    }

    unset($data['minimum-stability']);

    if (!is_array($data['repositories'])) {
      $this->fail('The actual file does not contain the repositories section.');
    }
    foreach ($data['repositories'] as $key => $repository) {
      if (!is_array($repository)) {
        $this->fail('The actual file contains an invalid repository entry.');
      }
      if (array_key_exists('type', $repository) && $repository['type'] === 'path' && array_key_exists('url', $repository) && $repository['url'] === static::$root) {
        unset($data['repositories'][$key]);
      }
    }

    if (empty($data['repositories'])) {
      unset($data['repositories']);
    }
    file_put_contents($actual, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);

    $this->assertSame($expected, $actual);
  }

  /**
   * Assert successful Composer command output contains the expected strings.
   *
   * @param string|array $strings
   *   The expected strings.
   */
  protected function assertTesterSuccessOutputContains(string|array $strings): void {
    $strings = is_array($strings) ? $strings : [$strings];

    if ($this->tester->getStatusCode() !== 0) {
      $this->fail($this->tester->getDisplay());
    }
    $this->assertSame(0, $this->tester->getStatusCode(), sprintf("The Composer command should have completed successfully:\n%s", $this->tester->getInput()->__toString()));

    $output = $this->tester->getDisplay(TRUE);
    foreach ($strings as $string) {
      $this->assertStringContainsString($string, $output);
    }
  }

  /**
   * Assert that fixtures directories are equal.
   */
  protected function assertFixtureDirectoryEqualsSut(string $expected): void {
    $this->assertDirectoriesEqual(static::$fixtures . DIRECTORY_SEPARATOR . $expected, static::$sut, static function (string $content, \SplFileInfo $file): string {
      return $content;
    });
  }

  /**
   * Assert that 2 directories have the same files and content, ignoring some.
   *
   * The main purpose of this method is to allow to create before/after file
   * structures and compare them, ignoring some files or content changes. This
   * allows to create fixture hierarchies fast.
   *
   * The first directory could be updated using the files from the second
   * directory if the environment variable `UPDATE_TEST_FIXTURES` is set.
   * This is useful to update the fixtures after the changes in the code.
   *
   * Files can be excluded from the comparison completely or only checked for
   * presence and ignored for the content changes using a .gitignore-like
   * file `.ignorecontent` that can be placed in the second directory.
   *
   * The syntax for the file is similar to .gitignore with addition of
   * the content ignoring using ^ prefix:
   * Comments start with #.
   * file    Ignore file.
   * dir/    Ignore directory and all subdirectories.
   * dir/*   Ignore all files in directory, but not subdirectories.
   * ^file   Ignore content changes in file, but not the file itself.
   * ^dir/   Ignore content changes in all files and subdirectories, but check
   *         that the directory itself exists.
   * ^dir/*  Ignore content changes in all files, but not subdirectories and
   *         check that the directory itself exists.
   * !file   Do not ignore file.
   * !dir/   Do not ignore directory, including all subdirectories.
   * !dir/*  Do not ignore all files in directory, but not subdirectories.
   * !^file  Do not ignore content changes in file.
   * !^dir/  Do not ignore content changes in all files and subdirectories.
   * !^dir/* Do not ignore content changes in all files, but not subdirectories.
   *
   * This assertion method is deliberately used as a single assertion for
   * portability.
   *
   * @param string $dir1
   *   The first directory.
   * @param string $dir2
   *   The second directory.
   * @param callable|null $match_content
   *   A callback to modify the content of the files before comparison.
   */
  protected function assertDirectoriesEqual(string $dir1, string $dir2, ?callable $match_content = NULL): void {
    $rules_file = $dir1 . DIRECTORY_SEPARATOR . '.ignorecontent';

    // Initialize the rules arrays: skip, presence, include, and global.
    $rules = ['skip' => ['.ignorecontent'], 'ignore_content' => [], 'include' => [], 'global' => []];

    // Parse the .ignorecontent file.
    if (file_exists($rules_file)) {
      $lines = file($rules_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

      if ($lines === FALSE) {
        throw new \RuntimeException('Failed to read the .ignorecontent file.');
      }

      foreach ($lines as $line) {
        $line = trim($line);
        if ($line[0] === '#') {
          continue;
        }
        elseif ($line[0] === '!') {
          $rules['include'][] = $line[1] === '^' ? substr($line, 2) : substr($line, 1);
        }
        elseif ($line[0] === '^') {
          $rules['ignore_content'][] = substr($line, 1);
        }
        elseif (!str_contains($line, DIRECTORY_SEPARATOR)) {
          // Treat patterns without slashes as global patterns.
          $rules['global'][] = $line;
        }
        else {
          // Regular skip rule.
          $rules['skip'][] = $line;
        }
      }
    }

    // Match paths.
    $match_path = static function (string $path, string $pattern, bool $is_directory): bool {
      $path .= $is_directory ? DIRECTORY_SEPARATOR : '';
      // Match directory pattern (e.g., "dir/").
      if (str_ends_with($pattern, DIRECTORY_SEPARATOR)) {
        return str_starts_with($path, rtrim($pattern, DIRECTORY_SEPARATOR));
      }

      // Match direct children (e.g., "dir/*").
      if (str_contains($pattern, '/*')) {
        $parent_dir = rtrim($pattern, '/*') . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $parent_dir) && substr_count($path, DIRECTORY_SEPARATOR) === substr_count($parent_dir, DIRECTORY_SEPARATOR);
      }

      // @phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
      return fnmatch($pattern, $path);
    };

    // Get the files in the directories.
    $get_files = static function (string $dir, array $rules, callable $match_path, ?callable $match_content): array {
      $files = [];
      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS));
      foreach ($iterator as $file) {
        if (!$file instanceof \SplFileInfo) {
          continue;
        }

        $is_directory = $file->isDir();
        $path = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $path .= $is_directory ? DIRECTORY_SEPARATOR : '';

        foreach ($rules['global'] as $pattern) {
          if ($match_path(basename($path), $pattern, $is_directory)) {
            continue 2;
          }
        }

        $is_included = FALSE;
        foreach ($rules['include'] as $pattern) {
          if ($match_path($path, $pattern, $is_directory)) {
            $is_included = TRUE;
            break;
          }
        }

        if (!$is_included) {
          foreach ($rules['skip'] as $pattern) {
            if ($match_path($path, $pattern, $is_directory)) {
              continue 2;
            }
          }
        }

        $is_ignore_content = FALSE;
        if (!$is_included) {
          foreach ($rules['ignore_content'] as $pattern) {
            if ($match_path($path, $pattern, $is_directory)) {
              $is_ignore_content = TRUE;
              break;
            }
          }
        }

        if ($is_ignore_content) {
          $files[$path] = 'ignore_content';
        }
        elseif ($is_directory) {
          $files[$path] = 'ignore_content';
        }
        else {
          $content = file_get_contents($file->getPathname());
          if (is_callable($match_content)) {
            $content = $match_content($content, $file);
          }
          $files[$path] = md5($content);
        }
      }
      ksort($files);

      return $files;
    };

    $dir1_files = $get_files($dir1, $rules, $match_path, $match_content);
    $dir2_files = $get_files($dir2, $rules, $match_path, $match_content);

    // Allow updating the test fixtures.
    if (getenv('UPDATE_TEST_FIXTURES')) {
      $allowed_files = array_keys($dir2_files);
      $finder = new Finder();
      $finder->files()->in($dir2)->ignoreDotFiles(FALSE)->filter(static function (\SplFileInfo $file) use ($allowed_files, $dir2): bool {
        $relative_path = str_replace(realpath($dir2) . DIRECTORY_SEPARATOR, '', $file->getRealPath());

        return in_array($relative_path, $allowed_files);
      });

      $this->fs->mirror($dir2, $dir1, $finder->getIterator(), [
        'override' => TRUE,
      ]);

      return;
    }

    $diffs = [
      'only_in_dir1' => array_diff_key($dir1_files, $dir2_files),
      'only_in_dir2' => array_diff_key($dir2_files, $dir1_files),
      'different_files' => [],
    ];

    // Compare files where content is not ignored.
    foreach ($dir1_files as $file => $hash) {
      if (isset($dir2_files[$file]) && $hash !== $dir2_files[$file] && !in_array($file, $rules['ignore_content'])) {
        $diffs['different_files'][] = $file;
      }
    }

    // If differences exist, throw assertion error.
    if (!empty($diffs['only_in_dir1']) || !empty($diffs['only_in_dir2']) || !empty($diffs['different_files'])) {
      $message = sprintf("Differences between directories %s and %s:%s", $dir1, $dir2, PHP_EOL);

      if (!empty($diffs['only_in_dir1'])) {
        $message .= "Files only in dir1:\n";
        foreach (array_keys($diffs['only_in_dir1']) as $file) {
          $message .= sprintf('  %s%s', $file, PHP_EOL);
        }
      }

      if (!empty($diffs['only_in_dir2'])) {
        $message .= "Files only in dir2:\n";
        foreach (array_keys($diffs['only_in_dir2']) as $file) {
          $message .= sprintf('  %s%s', $file, PHP_EOL);
        }
      }

      if (!empty($diffs['different_files'])) {
        $message .= "Files that differ in content:\n";
        foreach ($diffs['different_files'] as $file) {
          $message .= sprintf('  %s%s', $file, PHP_EOL);
        }
      }

      throw new AssertionFailedError($message);
    }

    // @phpstan-ignore-next-line
    $this->assertTrue(TRUE);
  }

  protected function runInstall(array $answers = [], ?string $dst = NULL): void {
    putenv(Config::REPO_URI . '=' . static::$root);
    static::tuiInput($answers);

    $dst = $dst ?? static::$sut;
    $args[] = InstallCommand::$defaultName;
    if ($dst) {
      $args[InstallCommand::ARG_DESTINATION] = $dst;
    }
    $this->consoleApplicationRun($args);
  }

  const MAX_QUESTIONS = 25;

  protected static function fill(int $skip = self::MAX_QUESTIONS, ...$values): array {
    $suffix_length = max(self::MAX_QUESTIONS - $skip - count($values), 0);

    return array_merge(array_fill(0, $skip, NULL), $values, array_fill(0, $suffix_length, NULL));
  }

}
