<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use CzProject\GitPhp\RunnerResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Git;
use CzProject\GitPhp\GitRepository;

/**
 * Class GitTest.
 *
 * GitTest fixture class.
 */
#[CoversClass(Git::class)]
class GitTest extends UnitTestCase {

  /**
   * Create a temporary git repository for testing.
   *
   * @param bool $with_remote
   *   Whether to add a remote to the repository.
   * @param bool $with_commits
   *   Whether to add commits to the repository.
   *
   * @return array{string, \DrevOps\VortexInstaller\Utils\Git}
   *   Array with temp directory path and Git object.
   */
  protected function createTempGitRepo(bool $with_remote = FALSE, bool $with_commits = FALSE): array {
    $temp_dir = sys_get_temp_dir() . '/git_test_' . uniqid();
    mkdir($temp_dir);

    // Initialize the git repository and create our Git wrapper.
    Git::init($temp_dir);
    $repo = new Git($temp_dir);

    if ($with_commits) {
      // Set git config locally for this repository to avoid CI issues.
      $repo->run('config', 'user.name', 'Test User');
      $repo->run('config', 'user.email', 'test@example.com');

      // Create a test file and make initial commit.
      file_put_contents($temp_dir . '/test.txt', 'test content');
      $repo->addAllChanges();
      $repo->commit('Initial commit');

      // Add another file and commit.
      file_put_contents($temp_dir . '/another.txt', 'another test');
      $repo->addAllChanges();
      $repo->commit('Second commit');
    }

    if ($with_remote) {
      // Add test remotes.
      $repo->addRemote('origin', 'https://github.com/owner/repo.git');
      $repo->addRemote('upstream', 'https://github.com/upstream/repo.git');
    }

    return [$temp_dir, $repo];
  }

  /**
   * Clean up temporary git repository.
   */
  protected function cleanupTempGitRepo(string $temp_dir): void {
    if (is_dir($temp_dir)) {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($temp_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
      );

      foreach ($iterator as $path) {
        $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
      }
      rmdir($temp_dir);
    }
  }

  #[DataProvider('dataProviderExtractOwnerRepo')]
  public function testExtractOwnerRepo(string $uri, ?string $expected): void {
    $this->assertSame($expected, Git::extractOwnerRepo($uri));
  }

  public static function dataProviderExtractOwnerRepo(): array {
    return [
      ['git@github.com:owner/repo.git', 'owner/repo'],
      ['ssh://git@github.com/owner/repo.git', 'owner/repo'],
      ['https://github.com/owner/repo.git', 'owner/repo'],
      ['git://github.com/owner/repo.git', 'owner/repo'],
      ['https://github.com/owner/repo', 'owner/repo'],
      ['git@bitbucket.org:myteam/myproject.git', 'myteam/myproject'],
      ['ssh://git@gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'],
      ['https://gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'],
      ['file:///local/path/to/repo.git', NULL],
      ['/absolute/path/to/repo', NULL],
      ['invalid_string', NULL],
    ];
  }

  public function testInit(): void {
    $temp_dir = sys_get_temp_dir() . '/git_test_init_' . uniqid();
    mkdir($temp_dir);

    $repo = Git::init($temp_dir);

    $this->assertInstanceOf(GitRepository::class, $repo);
    $this->assertTrue(is_dir($temp_dir . '/.git'));

    $this->cleanupTempGitRepo($temp_dir);
  }

  public function testRun(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, TRUE);

    try {
      // Test that run method works and adds --no-pager.
      $result = $repo->run('status', '--porcelain');
      $this->assertInstanceOf(RunnerResult::class, $result);

      // Test with another command.
      $result = $repo->run('log', '--oneline', '--max-count=1');
      $this->assertInstanceOf(RunnerResult::class, $result);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

  public function testListRemotesEmpty(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, FALSE);

    try {
      $remotes = $repo->listRemotes();
      $this->assertEmpty($remotes);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

  public function testListRemotesWithRemotes(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(TRUE, FALSE);

    try {
      $remotes = $repo->listRemotes();
      $this->assertArrayHasKey('origin', $remotes);
      $this->assertArrayHasKey('upstream', $remotes);
      $this->assertEquals('https://github.com/owner/repo.git', $remotes['origin']);
      $this->assertEquals('https://github.com/upstream/repo.git', $remotes['upstream']);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

  public function testGetTrackedFilesNonGitDirectory(): void {
    $temp_dir = sys_get_temp_dir() . '/non_git_' . uniqid();
    mkdir($temp_dir);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The directory is not a Git repository.');

    try {
      Git::getTrackedFiles($temp_dir);
    }
    finally {
      rmdir($temp_dir);
    }
  }

  public function testGetTrackedFilesEmptyRepo(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, FALSE);

    try {
      $tracked = Git::getTrackedFiles($temp_dir);
      $this->assertEmpty($tracked);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

  public function testGetTrackedFilesWithFiles(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, TRUE);

    try {
      $tracked = Git::getTrackedFiles($temp_dir);
      $this->assertCount(2, $tracked);
      $this->assertContains($temp_dir . DIRECTORY_SEPARATOR . 'test.txt', $tracked);
      $this->assertContains($temp_dir . DIRECTORY_SEPARATOR . 'another.txt', $tracked);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

  public function testGetLastShortCommitId(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, TRUE);

    try {
      $short_id = $repo->getLastShortCommitId();
      $this->assertEquals(7, strlen($short_id));
      $this->assertMatchesRegularExpression('/^[0-9a-f]{7}$/', $short_id);
    }
    finally {
      $this->cleanupTempGitRepo($temp_dir);
    }
  }

}
