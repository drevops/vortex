<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Artifact class.
 */
#[CoversClass(Artifact::class)]
class ArtifactTest extends TestCase {

  #[DataProvider('dataProviderFromUri')]
  public function testFromUri(?string $uri, string $expectedRepo, string $expectedRef, ?string $expectedException = NULL, ?string $expectedMessage = NULL): void {
    if ($expectedException !== NULL) {
      /** @var class-string<\Throwable> $expectedException */
      $this->expectException($expectedException);
      $this->expectExceptionMessage($expectedMessage);
    }

    $artifact = Artifact::fromUri($uri);

    if ($expectedException === NULL) {
      $this->assertEquals($expectedRepo, $artifact->getRepo());
      $this->assertEquals($expectedRef, $artifact->getRef());
    }
  }

  /**
   * Data provider for testFromUri().
   */
  public static function dataProviderFromUri(): array {
    return [
      // Default URI cases.
      'null uri defaults to default repo and stable ref' => [
        NULL,
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_STABLE,
      ],
      'empty string defaults to default repo and stable ref' => [
        '',
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_STABLE,
      ],

      // GitHub HTTPS patterns.
      'https url with #ref' => [
        'https://github.com/drevops/vortex.git#1.0.0',
        'https://github.com/drevops/vortex.git',
        '1.0.0',
      ],
      'https url without #ref defaults to HEAD' => [
        'https://github.com/drevops/vortex.git',
        'https://github.com/drevops/vortex.git',
        'HEAD',
      ],
      'https url with release tag pattern' => [
        'https://github.com/drevops/vortex/releases/tag/25.11.0',
        'https://github.com/drevops/vortex',
        '25.11.0',
      ],
      'https url with tree pattern' => [
        'https://github.com/drevops/vortex/tree/feature-branch',
        'https://github.com/drevops/vortex',
        'feature-branch',
      ],
      'https url with commit pattern' => [
        'https://github.com/drevops/vortex/commit/abc123def',
        'https://github.com/drevops/vortex',
        'abc123def',
      ],

      // Git SSH patterns.
      'git@ scp-style with #ref' => [
        'git@github.com:drevops/vortex#stable',
        'git@github.com:drevops/vortex',
        'stable',
      ],
      'git@ scp-style without #ref defaults to HEAD' => [
        'git@github.com:drevops/vortex',
        'git@github.com:drevops/vortex',
        'HEAD',
      ],

      // SSH and Git protocol URLs.
      'ssh:// url with #ref' => [
        'ssh://git@github.com/drevops/vortex#develop',
        'ssh://git@github.com/drevops/vortex',
        'develop',
      ],
      'ssh:// url without #ref defaults to HEAD' => [
        'ssh://git@github.com/drevops/vortex',
        'ssh://git@github.com/drevops/vortex',
        'HEAD',
      ],
      'git:// url with #ref' => [
        'git://github.com/drevops/vortex#main',
        'git://github.com/drevops/vortex',
        'main',
      ],
      'git:// url without #ref defaults to HEAD' => [
        'git://github.com/drevops/vortex',
        'git://github.com/drevops/vortex',
        'HEAD',
      ],
      'http:// url with #ref' => [
        'http://github.com/drevops/vortex#feature',
        'http://github.com/drevops/vortex',
        'feature',
      ],
      'http:// url without #ref defaults to HEAD' => [
        'http://github.com/drevops/vortex',
        'http://github.com/drevops/vortex',
        'HEAD',
      ],

      // Local path patterns.
      'local path with #ref' => [
        '/path/to/repo#develop',
        '/path/to/repo',
        'develop',
      ],
      'local path without #ref defaults to HEAD' => [
        '/path/to/repo',
        '/path/to/repo',
        'HEAD',
      ],
      'local path with trailing slash removed' => [
        '/path/to/repo/',
        '/path/to/repo',
        'HEAD',
      ],
      'file:// url with #ref' => [
        'file:///path/to/repo#main',
        '/path/to/repo',
        'main',
      ],
      'file:// url without #ref' => [
        'file:///path/to/repo',
        '/path/to/repo',
        'HEAD',
      ],

      // Invalid ref format.
      'invalid ref with space' => [
        'https://github.com/drevops/vortex.git#invalid ref',
        '',
        '',
        \RuntimeException::class,
        'Invalid git reference: "invalid ref"',
      ],
      'invalid ref with trailing slash' => [
        'https://github.com/drevops/vortex.git#feature/',
        '',
        '',
        \RuntimeException::class,
        'Invalid git reference: "feature/"',
      ],
      'invalid ref with consecutive slashes' => [
        'https://github.com/drevops/vortex.git#feature//name',
        '',
        '',
        \RuntimeException::class,
        'Invalid git reference: "feature//name"',
      ],

      // Invalid URI formats.
      'invalid https format - missing path structure' => [
        'https://github.com',
        '',
        '',
        \RuntimeException::class,
        'Invalid remote repository format',
      ],
      'invalid ssh format - missing colon' => [
        'git@github.com',
        '',
        '',
        \RuntimeException::class,
        'Invalid remote repository format',
      ],
      'invalid file:// format - empty path' => [
        'file://',
        '',
        '',
        \RuntimeException::class,
        'Invalid local repository format',
      ],
      'non-github https with invalid ref' => [
        'https://gitlab.com/user/repo#invalid ref',
        '',
        '',
        \RuntimeException::class,
        'Invalid git reference: "invalid ref"',
      ],

      // Deprecated @ref syntax (supported until 1.1.0).
      'deprecated @ref with https' => [
        'https://github.com/drevops/vortex.git@1.0.0',
        'https://github.com/drevops/vortex.git',
        '1.0.0',
      ],
      'deprecated @ref with http' => [
        'http://github.com/drevops/vortex.git@stable',
        'http://github.com/drevops/vortex.git',
        'stable',
      ],
      'deprecated @ref with ssh://' => [
        'ssh://git@github.com/drevops/vortex@main',
        'ssh://git@github.com/drevops/vortex',
        'main',
      ],
      'deprecated @ref with git://' => [
        'git://github.com/drevops/vortex@develop',
        'git://github.com/drevops/vortex',
        'develop',
      ],
      'deprecated @ref with git@ scp-style' => [
        'git@github.com:drevops/vortex@feature',
        'git@github.com:drevops/vortex',
        'feature',
      ],
    ];
  }

  #[DataProvider('dataProviderCreate')]
  public function testCreate(string $repo, string $ref, ?string $expectedException = NULL, ?string $expectedMessage = NULL): void {
    if ($expectedException !== NULL) {
      /** @var class-string<\Throwable> $expectedException */
      $this->expectException($expectedException);
      $this->expectExceptionMessage($expectedMessage);
    }

    $artifact = Artifact::create($repo, $ref);

    if ($expectedException === NULL) {
      $this->assertEquals($repo, $artifact->getRepo());
      $this->assertEquals($ref, $artifact->getRef());
    }
  }

  /**
   * Data provider for testCreate().
   */
  public static function dataProviderCreate(): array {
    return [
      'valid remote repo and ref' => [
        'https://github.com/drevops/vortex.git',
        '1.0.0',
      ],
      'valid local repo and ref' => [
        '/path/to/repo',
        'main',
      ],
      'invalid ref with space' => [
        'https://github.com/drevops/vortex.git',
        'invalid ref',
        \RuntimeException::class,
        'Invalid git reference: "invalid ref"',
      ],
      'invalid ref with trailing slash' => [
        '/path/to/repo',
        'feature/',
        \RuntimeException::class,
        'Invalid git reference: "feature/"',
      ],
    ];
  }

  #[DataProvider('dataProviderIsRemote')]
  public function testIsRemote(string $repo, bool $expected): void {
    $artifact = Artifact::create($repo, 'HEAD');
    $this->assertEquals($expected, $artifact->isRemote());
  }

  /**
   * Data provider for testIsRemote().
   */
  public static function dataProviderIsRemote(): array {
    return [
      'https url' => ['https://github.com/drevops/vortex.git', TRUE],
      'http url' => ['http://github.com/drevops/vortex.git', TRUE],
      'ssh:// url' => ['ssh://git@github.com/drevops/vortex.git', TRUE],
      'git:// url' => ['git://github.com/drevops/vortex.git', TRUE],
      'git@ scp-style url' => ['git@github.com:drevops/vortex', TRUE],
      'local absolute path' => ['/path/to/repo', FALSE],
      'local relative path' => ['./repo', FALSE],
      'file:// url treated as local' => ['file:///path/to/repo', FALSE],
    ];
  }

  #[DataProvider('dataProviderIsLocal')]
  public function testIsLocal(string $repo, bool $expected): void {
    $artifact = Artifact::create($repo, 'HEAD');
    $this->assertEquals($expected, $artifact->isLocal());
  }

  /**
   * Data provider for testIsLocal().
   */
  public static function dataProviderIsLocal(): array {
    return [
      'https url' => ['https://github.com/drevops/vortex.git', FALSE],
      'http url' => ['http://github.com/drevops/vortex.git', FALSE],
      'ssh:// url' => ['ssh://git@github.com/drevops/vortex.git', FALSE],
      'git:// url' => ['git://github.com/drevops/vortex.git', FALSE],
      'git@ scp-style url' => ['git@github.com:drevops/vortex', FALSE],
      'local absolute path' => ['/path/to/repo', TRUE],
      'local relative path' => ['./repo', TRUE],
      'file:// url treated as local' => ['file:///path/to/repo', TRUE],
    ];
  }

  #[DataProvider('dataProviderIsDefault')]
  public function testIsDefault(string $repo, string $ref, bool $expected): void {
    $artifact = Artifact::create($repo, $ref);
    $this->assertEquals($expected, $artifact->isDefault());
  }

  /**
   * Data provider for testIsDefault().
   */
  public static function dataProviderIsDefault(): array {
    return [
      'default repo with stable ref' => [
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_STABLE,
        TRUE,
      ],
      'default repo without .git with stable ref' => [
        'https://github.com/drevops/vortex',
        RepositoryDownloader::REF_STABLE,
        TRUE,
      ],
      'default repo with HEAD ref' => [
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_HEAD,
        TRUE,
      ],
      'default repo with custom ref' => [
        RepositoryDownloader::DEFAULT_REPO,
        'custom-branch',
        FALSE,
      ],
      'custom repo with stable ref' => [
        'https://github.com/custom/repo.git',
        RepositoryDownloader::REF_STABLE,
        FALSE,
      ],
      'custom repo with custom ref' => [
        'https://github.com/custom/repo.git',
        'custom-branch',
        FALSE,
      ],
    ];
  }

  #[DataProvider('dataProviderGetRepoUrl')]
  public function testGetRepoUrl(string $repo, string $expectedUrl): void {
    $artifact = Artifact::create($repo, 'HEAD');
    $this->assertEquals($expectedUrl, $artifact->getRepoUrl());
  }

  /**
   * Data provider for testGetRepoUrl().
   */
  public static function dataProviderGetRepoUrl(): array {
    return [
      'https url with .git' => [
        'https://github.com/drevops/vortex.git',
        'https://github.com/drevops/vortex',
      ],
      'https url without .git' => [
        'https://github.com/drevops/vortex',
        'https://github.com/drevops/vortex',
      ],
      'ssh url with .git' => [
        'git@github.com:drevops/vortex.git',
        'git@github.com:drevops/vortex',
      ],
      'local path not affected' => [
        '/path/to/repo',
        '/path/to/repo',
      ],
    ];
  }

  #[DataProvider('dataProviderIsStable')]
  public function testIsStable(string $repo, string $ref, bool $expected): void {
    $artifact = Artifact::create($repo, $ref);
    $this->assertEquals($expected, $artifact->isStable());
  }

  /**
   * Data provider for testIsStable().
   */
  public static function dataProviderIsStable(): array {
    return [
      'stable ref' => ['https://github.com/drevops/vortex.git', 'stable', TRUE],
      'HEAD ref' => ['https://github.com/drevops/vortex.git', 'HEAD', FALSE],
      'custom ref' => ['https://github.com/drevops/vortex.git', '1.0.0', FALSE],
      'branch ref' => ['https://github.com/drevops/vortex.git', 'feature-branch', FALSE],
    ];
  }

  #[DataProvider('dataProviderIsDevelopment')]
  public function testIsDevelopment(string $repo, string $ref, bool $expected): void {
    $artifact = Artifact::create($repo, $ref);
    $this->assertEquals($expected, $artifact->isDevelopment());
  }

  /**
   * Data provider for testIsDevelopment().
   */
  public static function dataProviderIsDevelopment(): array {
    return [
      'HEAD ref' => ['https://github.com/drevops/vortex.git', 'HEAD', TRUE],
      'stable ref' => ['https://github.com/drevops/vortex.git', 'stable', FALSE],
      'custom ref' => ['https://github.com/drevops/vortex.git', '1.0.0', FALSE],
      'branch ref' => ['https://github.com/drevops/vortex.git', 'feature-branch', FALSE],
    ];
  }

}
