<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\RunnerResult;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Git::class)]
class GitTest extends UnitTestCase {

  #[DataProvider('dataProviderExtractOwnerRepo')]
  public function testExtractOwnerRepo(string $uri, ?string $expected): void {
    $this->assertSame($expected, Git::extractOwnerRepo($uri));
  }

  public static function dataProviderExtractOwnerRepo(): \Iterator {
    yield ['git@github.com:owner/repo.git', 'owner/repo'];
    yield ['ssh://git@github.com/owner/repo.git', 'owner/repo'];
    yield ['https://github.com/owner/repo.git', 'owner/repo'];
    yield ['git://github.com/owner/repo.git', 'owner/repo'];
    yield ['https://github.com/owner/repo', 'owner/repo'];
    yield ['git@bitbucket.org:myteam/myproject.git', 'myteam/myproject'];
    yield ['ssh://git@gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'];
    yield ['https://gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'];
    yield ['file:///local/path/to/repo.git', NULL];
    yield ['/absolute/path/to/repo', NULL];
    yield ['invalid_string', NULL];
  }

  public function testInit(): void {
    $temp_dir = static::$tmp . '/git_test_init_' . uniqid();
    File::mkdir($temp_dir);

    $repo = Git::init($temp_dir);

    $this->assertInstanceOf(GitRepository::class, $repo);
    $this->assertTrue(File::isDir($temp_dir . '/.git'));

    $this->cleanupTempGitRepo($temp_dir);
  }

  public function testRun(): void {
    [$temp_dir, $repo] = $this->createTempGitRepo(FALSE, TRUE);

    try {
      $result = $repo->run('status', '--porcelain');
      $this->assertInstanceOf(RunnerResult::class, $result);

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
    $temp_dir = static::$tmp . '/non_git_' . uniqid();
    File::mkdir($temp_dir);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The directory is not a Git repository.');

    try {
      Git::getTrackedFiles($temp_dir);
    }
    finally {
      File::remove($temp_dir);
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

  /**
   * @param bool $with_remote
   *   Whether to add a remote to the repository.
   * @param bool $with_commits
   *   Whether to add commits to the repository.
   *
   * @return array{string, \DrevOps\VortexInstaller\Utils\Git}
   *   Array with temp directory path and Git object.
   */
  protected function createTempGitRepo(bool $with_remote = FALSE, bool $with_commits = FALSE): array {
    $temp_dir = static::$tmp . '/git_test_' . uniqid();
    File::mkdir($temp_dir);

    Git::init($temp_dir);
    $repo = new Git($temp_dir);

    if ($with_commits) {
      // CI runners have no global git identity, so commits need local config.
      $repo->run('config', 'user.name', 'Test User');
      $repo->run('config', 'user.email', 'test@example.com');

      File::dump($temp_dir . '/test.txt', 'test content');
      $repo->addAllChanges();
      $repo->commit('Initial commit');

      File::dump($temp_dir . '/another.txt', 'another test');
      $repo->addAllChanges();
      $repo->commit('Second commit');
    }

    if ($with_remote) {
      $repo->addRemote('origin', 'https://github.com/owner/repo.git');
      $repo->addRemote('upstream', 'https://github.com/upstream/repo.git');
    }

    return [$temp_dir, $repo];
  }

  protected function cleanupTempGitRepo(string $temp_dir): void {
    if (File::isDir($temp_dir)) {
      File::remove($temp_dir);
    }
  }

}
