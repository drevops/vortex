<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait GitTrait.
 *
 * Helpers to work with Git repositories.
 */
trait GitTrait {

  use AssertArrayTrait;

  /**
   * Init Git repository.
   *
   * @param string $path
   *   Path to the repository directory.
   */
  protected function gitInitRepo(string $path): GitRepository {
    (new Filesystem())->mkdir($path);

    return (new Git())->init($path);
  }

  /**
   * Checkout branch.
   *
   * @param string $path
   *   Path to repository.
   * @param string $branch
   *   Branch name.
   */
  protected function gitCheckout(string $path, string $branch): void {
    try {
      (new Git())->open($path)->checkout($branch);
    }
    catch (GitException $gitException) {
      $allowed_fails = [
        sprintf("error: pathspec '%s' did not match any file(s) known to git", $branch),
      ];

      if ($gitException->getRunnerResult()) {
        $output = $gitException->getRunnerResult()->getErrorOutput();
      }

      // Re-throw exception if it is not one of the allowed ones.
      if (!isset($output) || empty(array_intersect($output, $allowed_fails))) {
        throw $gitException;
      }
    }
  }

  /**
   * Create branch.
   *
   * @param string $path
   *   Path to repository.
   * @param string $branch
   *   Branch name.
   */
  protected function gitCreateBranch(string $path, string $branch): void {
    (new Git())->open($path)->createBranch($branch);
  }

  /**
   * Reset git repo at path.
   *
   * @param string $path
   *   Path to the repo.
   */
  protected function gitReset($path): void {
    $repo = (new Git())->open($path);
    $repo->run('reset', ['--hard']);
    $repo->run('clean', ['-dfx']);
  }

  /**
   * Get all commit hashes in the repository.
   *
   * @param string $path
   *   Path to the repository directory.
   * @param string $format
   *   Format of commits.
   *
   * @return array<string>
   *   Array of commit hashes, sorted from the earliest to the latest commit.
   *
   * @throws \Exception
   */
  protected function gitGetAllCommits(string $path, string $format = '%s'): array {
    $commits = [];

    try {
      $commits = (new Git())->open($path)->run(['log', '--format=' . $format])->getOutput();
    }
    catch (\Exception $exception) {
      // Different versions of Git may produce these expected messages.
      $expected_error_messages = [
        "fatal: bad default revision 'HEAD'",
        "fatal: your current branch 'master' does not have any commits yet",
      ];

      if (!in_array(trim($exception->getMessage()), $expected_error_messages)) {
        throw $exception;
      }
    }

    return array_reverse(array_filter($commits));
  }

  /**
   * Get a range of commits.
   *
   * @param array<int> $range
   *   Array of commit indexes, stating from 1.
   * @param string $path
   *   Path to the repository directory.
   *
   * @return array<string>
   *   Array of commit hashes, ordered by keys in the $range.
   *
   * @throws \Exception
   */
  protected function gitGetCommitsRange(array $range, string $path): array {
    $ret = [];

    $commits = $this->gitGetAllCommits($path);

    array_walk($range, static function (&$v): void {
      --$v;
    });

    foreach ($range as $key) {
      $ret[] = $commits[$key];
    }

    return $ret;
  }

  /**
   * Create fixture tag with specified name and optional annotation.
   *
   * Annotated tags and lightweight tags have a different object
   * representation in git, therefore may need to be created explicitly for
   * some tests.
   *
   * @param string $path
   *   Optional path to the repository directory.
   * @param string $name
   *   Tag name.
   * @param bool $annotate
   *   Optional flag to add random annotation to the tag. Defaults to FALSE.
   */
  protected function gitAddTag(string $path, string $name, bool $annotate = FALSE): void {
    $repo = (new Git())->open($path);

    if ($annotate) {
      $repo->createTag($name, ['--message="Annotation for tag ' . $name . '"', '-a']);
    }
    else {
      $repo->createTag($name);
    }
  }

  /**
   * Assert if path is a Git repository.
   */
  protected function gitAssertIsRepository(?string $path = NULL): void {
    $path = $path ?: File::cwd();

    $this->assertDirectoryExists($path, sprintf('Directory %s does not exist', $path));

    $git_dir = $path . DIRECTORY_SEPARATOR . '.git';
    $this->assertDirectoryExists($git_dir, sprintf('Directory %s exists, but it is not a git repository', $path));

    // Run git status to verify it's a valid git repository.
    $command = sprintf('git --work-tree=%s --git-dir=%s status 2>&1',
      escapeshellarg($path),
      escapeshellarg($git_dir)
    );

    $output = shell_exec($command);

    $this->assertStringNotContainsStringIgnoringCase(
      'not a git repository',
      $output ?: '',
      sprintf('Directory %s exists, but it is not a git repository', $path)
    );
  }

  /**
   * Assert current Git branch.
   *
   * @param string $path
   *   Path to repository.
   * @param string $branch
   *   Branch name to assert.
   */
  protected function gitAssertCurrentBranch(string $path, string $branch): void {
    $current = (new Git())->open($path)->getCurrentBranchName();
    $this->assertStringContainsString($branch, $current, sprintf('Current branch is "%s"', $branch));
  }

  /**
   * Create multiple fixture commits.
   *
   * @param int $count
   *   Number of commits to create.
   * @param int $offset
   *   Number of commit indices to offset.
   * @param string|null $path
   *   Optional path to the repository directory. If not provided, fixture
   *   directory is used.
   */
  protected function gitCreateFixtureCommits(int $count, int $offset = 0, ?string $path = NULL): void {
    $path = $path ?: File::cwd();

    for ($i = $offset; $i < $count + $offset; $i++) {
      $this->gitCreateFixtureCommit($i + 1, $path);
    }
  }

  /**
   * Create fixture commit with specified index.
   *
   * @param int $index
   *   Index of the commit to be used in the message.
   * @param string|null $path
   *   Optional path to the repository directory. If not provided, fixture
   *   directory is used.
   *
   * @return string
   *   Hash of created commit.
   */
  protected function gitCreateFixtureCommit(int $index, ?string $path = NULL): string {
    $path = $path ?: File::cwd();

    $filename = 'f' . $index;

    $fs = new Filesystem();
    $filepath = $path . DIRECTORY_SEPARATOR . $filename;
    $fs->mkdir(dirname($path));
    $fs->touch($filepath);

    return (new Git())->open($path)
      ->addFile($filename)
      ->commit('Commit number ' . $index)
      ->getLastCommit()->getId()->toString();
  }

  /**
   * Commit all uncommitted files.
   *
   * @param string $path
   *   Path to repository.
   * @param string $message
   *   Commit message.
   */
  protected function gitCommitAll(string $path, string $message): void {
    (new Git())->open($path)
      ->addAllChanges()
      ->commit($message);
  }

  /**
   * Assert that Git remote specified by name does not exist.
   *
   * @param string $path
   *   Path to repository.
   * @param string $remote
   *   Remote name to assert.
   */
  protected function gitAssertRemoteNotExists(string $path, string $remote): void {
    $remotes = (new Git())->open($path)->run(['remote'])->getErrorOutputAsString() ?: '';
    $this->assertStringNotContainsString($remote, $remotes, sprintf('Remote "%s" is not present"', $remote));
  }

  /**
   * Assert which git commits are present.
   *
   * @param int $count
   *   Number of commits.
   * @param string $path
   *   Path to the repo.
   * @param string $branch
   *   Branch name.
   * @param array<string> $additional_commits
   *   Array of additional commits.
   * @param bool $should_assert_files
   *   Should assert if files are present.
   *
   * @throws \Exception
   */
  protected function gitAssertFixtureCommits(int $count, string $path, string $branch, array $additional_commits = [], bool $should_assert_files = TRUE): void {
    $this->gitCheckout($path, $branch);
    $this->gitReset($path);

    $expected_commits = [];
    $expected_files = [];
    for ($i = 1; $i <= $count; $i++) {
      $expected_commits[] = sprintf('Commit number %s', $i);
      $expected_files[] = sprintf('f%s', $i);
    }
    $expected_commits = array_merge($expected_commits, $additional_commits);

    $commits = $this->gitGetAllCommits($path);
    $this->assertEquals($expected_commits, $commits, 'All fixture commits are present');

    if ($should_assert_files) {
      $this->assertFilesExist($path, $expected_files);
    }
  }

  /**
   * Assert git files are present and were committed.
   *
   * @param array<string>|string $files
   *   Path to repo.
   * @param string $path
   *   Array of files or a single file.
   * @param string $branch
   *   Optional branch name.
   */
  protected function gitAssertFilesTracked(array|string $files, ?string $path = NULL, ?string $branch = NULL): void {
    $path = $path ?: File::cwd();

    if ($branch) {
      $this->gitCheckout($path, $branch);
    }

    $files = is_array($files) ? $files : [$files];

    $tracked_files = (new Git())->open($path)->run(['ls-tree', '--name-only', '-r', 'HEAD'])->getOutput();
    $tracked_files = array_filter($tracked_files);

    $this->assertArrayContainsArray($tracked_files, $files);
  }

  /**
   * Assert git files were not committed.
   *
   * @param array<string>|string $files
   *   Path to repo.
   * @param string $path
   *   Array of files or a single file.
   * @param string $branch
   *   Optional branch name.
   */
  protected function gitAssertFilesNotTracked(array|string $files, ?string $path = NULL, ?string $branch = NULL): void {
    $path = $path ?: File::cwd();

    if ($branch) {
      $this->gitCheckout($path, $branch);
    }

    $files = is_array($files) ? $files : [$files];

    $tracked_files = (new Git())->open($path)->run(['ls-tree', '--name-only', '-r', 'HEAD'])->getOutput();
    $tracked_files = array_filter($tracked_files);

    $this->assertArrayNotContainsArray($tracked_files, $files);
  }

  /**
   * Get global default branch.
   *
   * @return string
   *   Default branch name.
   */
  protected function gitGetGlobalDefaultBranch(): string {
    return trim(shell_exec('git config --global init.defaultBranch') ?: 'master');
  }

}
