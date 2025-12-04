<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Downloader\ArchiverInterface;
use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(RepositoryDownloader::class)]
class RepositoryDownloaderTest extends UnitTestCase {

  #[DataProvider('dataProviderParseUri')]
  public function testParseUri(string $src, ?string $expected_repo = NULL, ?string $expected_ref = NULL, ?string $expected_exception_message = NULL): void {
    if (!is_null($expected_exception_message)) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }

    $result = RepositoryDownloader::parseUri($src);

    if (is_null($expected_exception_message)) {
      $this->assertCount(2, $result);
      $this->assertEquals($expected_repo, $result[0], 'Repository matches input: ' . $src);
      $this->assertEquals($expected_ref, $result[1], 'Reference matches input: ' . $src);
    }
  }

  public function testDownloadWithMockedArchiver(): void {
    $mock_http_client = $this->createMockHttpClient();
    /** @var \PHPUnit\Framework\MockObject\MockObject&\DrevOps\VortexInstaller\Downloader\ArchiverInterface $mock_archiver */
    $mock_archiver = $this->createMockArchiver();
    $mock_archiver->expects($this->once())->method('validate');
    $mock_archiver->expects($this->once())->method('extract');
    $mock_file_downloader = $this->createMockFileDownloader();
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public function testDownloadThrowsExceptionWhenComposerJsonMissing(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();

    $destination = self::$tmp . '/destination';
    File::mkdir($destination);

    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The downloaded repository does not contain a composer.json file.');

    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadFromRemoteCallsArchiverCorrectly(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();

    $mock_archiver->expects($this->once())->method('validate')->with($this->stringContains('vortex_archive_'));
    $mock_archiver->expects($this->once())->method('extract')->with($this->stringContains('vortex_archive_'), $this->anything(), TRUE);

    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');

    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadArchiveCreatesTemporaryFile(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public function testDownloadArchiveHandlesHttpError(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMock(Downloader::class);
    $mock_file_downloader->method('download')->willThrowException(new \RuntimeException('HTTP 404 error'));
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to download archive from');
    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadArchiveHandlesRequestException(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMock(Downloader::class);
    $mock_file_downloader->method('download')->willThrowException(new \RuntimeException('Network error'));
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to download archive from');
    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  #[DataProvider('providerDiscoverLatestReleaseRemote')]
  public function testDiscoverLatestReleaseRemote(string $repo, mixed $releaseData, bool $throwException, bool $skipMockSetup, ?string $expectedVersion, ?string $expectedException, ?string $expectedMessage): void {
    $mock_http_client = $this->createMock(ClientInterface::class);

    if (!$skipMockSetup) {
      if ($throwException) {
        $mock_http_client->method('request')->willThrowException(new RequestException('API error', $this->createMock(RequestInterface::class)));
      }
      else {
        $mock_response = $this->createMock(ResponseInterface::class);
        $mock_body = $this->createMock(StreamInterface::class);
        $mock_response->method('getBody')->willReturn($mock_body);

        $release_json = is_array($releaseData) ? json_encode($releaseData) : $releaseData;

        $mock_body->method('getContents')->willReturn($release_json);
        $mock_response->method('getStatusCode')->willReturn(200);

        // Only the API call uses httpClient now.
        $mock_http_client->method('request')->willReturn($mock_response);
      }
    }

    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);

    if ($expectedVersion !== NULL) {
      File::dump($destination . '/composer.json', '{}');
    }

    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);

    if ($expectedException !== NULL) {
      /** @var class-string<\Throwable> $expectedException */
      $this->expectException($expectedException);
      $this->expectExceptionMessage($expectedMessage);
    }

    $version = $downloader->download($repo, 'stable', $destination);

    if ($expectedVersion !== NULL) {
      $this->assertEquals($expectedVersion, $version);
    }
  }

  public function testDownloadFromRemoteWithGitSuffix(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download('https://github.com/user/repo.git', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  #[DataProvider('providerDownloadWithNullDestination')]
  public function testDownloadWithNullDestination(string $repo, string $expectedMessage): void {
    $downloader = new RepositoryDownloader();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($expectedMessage);
    $downloader->download($repo, 'HEAD');
  }

  #[DataProvider('providerDownloadFromLocal')]
  public function testDownloadFromLocal(string $ref, string $expectedVersion): void {
    $temp_repo_dir = $this->createGitRepo();
    $destination = self::$tmp . '/dest_' . uniqid();
    File::mkdir($destination);

    // Handle the special case where we need to get the actual commit hash.
    if ($ref === 'COMMIT_HASH') {
      $output = shell_exec('cd ' . escapeshellarg($temp_repo_dir) . ' && git rev-parse HEAD');
      $this->assertIsString($output, 'Failed to get commit hash from git repository');
      $commit_hash = trim($output);
      $this->assertNotEmpty($commit_hash, 'Git rev-parse returned empty output');
      $ref = substr($commit_hash, 0, 7);
      $expectedVersion = $ref;
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject&\DrevOps\VortexInstaller\Downloader\ArchiverInterface $mock_archiver */
    $mock_archiver = $this->createMockArchiverWithExtract();
    $downloader = new RepositoryDownloader(NULL, $mock_archiver);
    $version = $downloader->download($temp_repo_dir, $ref, $destination);
    $this->assertEquals($expectedVersion, $version);
    $this->removeGitRepo($temp_repo_dir);
  }

  public function testArchiveFromLocalHandlesGitFailure(): void {
    $temp_repo_dir = self::$tmp . '/test_git_repo_' . uniqid();
    $temp_dest_dir = self::$tmp . '/test_dest_' . uniqid();
    File::mkdir($temp_repo_dir);
    File::mkdir($temp_dest_dir);
    chdir($temp_repo_dir);
    exec('git init 2>&1');
    exec('git config user.email "test@example.com" 2>&1');
    exec('git config user.name "Test User" 2>&1');
    File::dump($temp_repo_dir . '/test.txt', 'test content');
    exec('git add . 2>&1');
    exec('git commit -m "Initial commit" 2>&1');
    $downloader = new RepositoryDownloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Reference "nonexistent-ref" not found in local repository');
    $downloader->download($temp_repo_dir, 'nonexistent-ref', $temp_dest_dir);
  }

  public function testDiscoverLatestReleaseRemoteWithGithubToken(): void {
    static::envSet('GITHUB_TOKEN', 'test_token_12345');
    $release_json = json_encode([
      ['tag_name' => 'v1.5.0', 'draft' => FALSE],
    ]);
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_body = $this->createMock(StreamInterface::class);
    $mock_response->method('getBody')->willReturn($mock_body);
    $mock_body->method('getContents')->willReturn($release_json);
    $mock_response->method('getStatusCode')->willReturn(200);
    // Two calls: HEAD for repo validation, GET for releases API.
    $mock_http_client->expects($this->exactly(2))->method('request')->willReturnCallback(function ($method, $url, array|\ArrayAccess $options) use ($mock_response): ResponseInterface {
      $this->assertArrayHasKey('headers', $options);
      $this->assertArrayHasKey('Authorization', $options['headers']);
      $this->assertEquals('Bearer test_token_12345', $options['headers']['Authorization']);
      return $mock_response;
    });
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    // File downloader should receive the token in headers.
    $mock_file_downloader = $this->createMock(Downloader::class);
    $mock_file_downloader->expects($this->once())->method('download')->willReturnCallback(function ($url, $dest, array $headers): void {
      $this->assertArrayHasKey('Authorization', $headers);
      $this->assertEquals('Bearer test_token_12345', $headers['Authorization']);
    });
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download('https://github.com/user/repo', 'stable', $destination);
    $this->assertEquals('v1.5.0', $version);
  }

  public function testDownloadArchiveWithGithubToken(): void {
    static::envSet('GITHUB_TOKEN', 'test_token_67890');
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    // File downloader should receive the token in headers.
    $mock_file_downloader = $this->createMock(Downloader::class);
    $mock_file_downloader->expects($this->once())->method('download')->willReturnCallback(function ($url, $dest, array $headers): void {
      $this->assertArrayHasKey('Authorization', $headers);
      $this->assertEquals('Bearer test_token_67890', $headers['Authorization']);
    });
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public static function dataProviderParseUri(): array {
    return [
      // Valid test cases.
      'HTTPS URLs - with default HEAD reference - GitHub' => [
        'https://github.com/user/repo',
        'https://github.com/user/repo',
        RepositoryDownloader::REF_HEAD,

      ],
      'HTTPS URLs - with default HEAD reference - GitLab' => [
        'https://gitlab.com/user/repo',
        'https://gitlab.com/user/repo',
        RepositoryDownloader::REF_HEAD,

      ],
      'HTTPS URLs - with default HEAD reference - Bitbucket' => [
        'https://bitbucket.org/user/repo',
        'https://bitbucket.org/user/repo',
        RepositoryDownloader::REF_HEAD,

      ],
      'HTTPS URLs - with specific valid references - stable' => [
        'https://github.com/user/repo#stable',
        'https://github.com/user/repo',
        RepositoryDownloader::REF_STABLE,

      ],
      'HTTPS URLs - with specific valid references - HEAD' => [
        'https://github.com/user/repo#HEAD',
        'https://github.com/user/repo',
        RepositoryDownloader::REF_HEAD,

      ],
      'HTTPS URLs - with 40-character commit hash' => [
        'https://github.com/user/repo#1234567890abcdef1234567890abcdef12345678',
        'https://github.com/user/repo',
        '1234567890abcdef1234567890abcdef12345678',

      ],
      'HTTPS URLs - with 7-character short commit hash' => [
        'https://github.com/user/repo#1234567',
        'https://github.com/user/repo',
        '1234567',

      ],
      'Git SSH URLs - with default HEAD reference - GitHub' => [
        'git@github.com:user/repo',
        'git@github.com:user/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Git SSH URLs - with default HEAD reference - GitLab' => [
        'git@gitlab.com:user/repo',
        'git@gitlab.com:user/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Git SSH URLs - with default HEAD reference - Bitbucket' => [
        'git@bitbucket.org:user/repo',
        'git@bitbucket.org:user/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Git SSH URLs - with specific valid references - stable' => [
        'git@github.com:user/repo#stable',
        'git@github.com:user/repo',
        RepositoryDownloader::REF_STABLE,
      ],
      'Git SSH URLs - with specific valid references - HEAD' => [
        'git@github.com:user/repo#HEAD',
        'git@github.com:user/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Git SSH URLs - with commit hashes - 40 char' => [
        'git@github.com:user/repo#1234567890abcdef1234567890abcdef12345678',
        'git@github.com:user/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'Git SSH URLs - with commit hashes - 7 char' => [
        'git@github.com:user/repo#1234567',
        'git@github.com:user/repo',
        '1234567',
      ],
      'File URLs - with default HEAD reference' => [
        'file:///path/to/repo',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'File URLs - with default HEAD reference - user path' => [
        'file:///home/user/repos/myrepo',
        '/home/user/repos/myrepo',
        RepositoryDownloader::REF_HEAD,
      ],
      'File URLs - with specific valid references - stable' => [
        'file:///path/to/repo#stable',
        '/path/to/repo',
        RepositoryDownloader::REF_STABLE,
      ],
      'File URLs - with specific valid references - HEAD' => [
        'file:///path/to/repo#HEAD',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'File URLs - with 40-character commit hash' => [
        'file:///path/to/repo#1234567890abcdef1234567890abcdef12345678',
        '/path/to/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'File URLs - with 7-character commit hash' => [
        'file:///path/to/repo#1234567',
        '/path/to/repo',
        '1234567',
      ],
      'Local paths - with default HEAD reference - absolute' => [
        '/path/to/repo',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - user home' => [
        '/home/user/repos/myrepo',
        '/home/user/repos/myrepo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - relative' => [
        'relative/path/to/repo',
        'relative/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - current dir' => [
        './repo',
        './repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - parent dir' => [
        '../repo',
        '../repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with specific valid references - stable' => [
        '/path/to/repo#stable',
        '/path/to/repo',
        RepositoryDownloader::REF_STABLE,
      ],
      'Local paths - with specific valid references - HEAD' => [
        '/path/to/repo#HEAD',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths - with 40-character commit hash' => [
        '/path/to/repo#1234567890abcdef1234567890abcdef12345678',
        '/path/to/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'Local paths - with 7-character commit hash' => [
        '/path/to/repo#1234567',
        '/path/to/repo',
        '1234567',
      ],
      'Local paths with trailing slashes - should be trimmed - single slash' => [
        '/path/to/repo/',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths with trailing slashes - should be trimmed - double slash' => [
        '/path/to/repo//',
        '/path/to/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Local paths with trailing slashes - should be trimmed - with reference' => [
        '/path/to/repo/#stable',
        '/path/to/repo',
        RepositoryDownloader::REF_STABLE,
      ],
      'Relative paths - simple' => [
        'repo',
        'repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Relative paths - with reference' => [
        'repo#stable',
        'repo',
        RepositoryDownloader::REF_STABLE,
      ],
      'Edge cases with valid commit hashes - uppercase 40 char' => [
        'https://github.com/user/repo#ABCDEF1234567890ABCDEF1234567890ABCDEF12',
        'https://github.com/user/repo',
        'ABCDEF1234567890ABCDEF1234567890ABCDEF12',
      ],
      'Edge cases with valid commit hashes - uppercase 7 char' => [
        'git@github.com:user/repo#ABCDEF1',
        'git@github.com:user/repo',
        'ABCDEF1',
      ],
      'Edge cases that are actually valid - HTTPS with extra path' => [
        'https://github.com/user/repo/extra/path',
        'https://github.com/user/repo/extra/path',
        RepositoryDownloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - Git SSH with extra path' => [
        'git@github.com:user/repo/extra',
        'git@github.com:user/repo/extra',
        RepositoryDownloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - protocol-less GitHub' => [
        'github.com/user/repo',
        'github.com/user/repo',
        RepositoryDownloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - file root' => [
        'file:///',
        '/',
        RepositoryDownloader::REF_HEAD,
      ],
      'Empty reference defaults to HEAD - # is captured in repo part' => [
        '/path/to/repo#',
        '/path/to/repo#',
        RepositoryDownloader::REF_HEAD,
      ],

      // Version tags - Semantic versioning.
      'Version tags - SemVer without prefix' => [
        'https://github.com/user/repo#1.2.3',
        'https://github.com/user/repo',
        '1.2.3',
      ],
      'Version tags - SemVer with v prefix' => [
        'https://github.com/user/repo#v1.2.3',
        'https://github.com/user/repo',
        'v1.2.3',
      ],
      'Version tags - SemVer with patch zero' => [
        'git@github.com:user/repo#2.0.0',
        'git@github.com:user/repo',
        '2.0.0',
      ],
      'Version tags - SemVer with pre-release' => [
        'file:///path/to/repo#1.2.3-beta',
        '/path/to/repo',
        '1.2.3-beta',
      ],
      'Version tags - SemVer with pre-release alpha' => [
        '/path/to/repo#1.2.3-alpha.1',
        '/path/to/repo',
        '1.2.3-alpha.1',
      ],

      // Version tags - Calendar versioning.
      'Version tags - CalVer YY.MM.PATCH' => [
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO) . '#25.11.0',
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        '25.11.0',
      ],
      'Version tags - CalVer YYYY.MM.PATCH' => [
        'git@github.com:user/repo#2024.12.3',
        'git@github.com:user/repo',
        '2024.12.3',
      ],

      // Version tags - Drupal-style versioning.
      'Version tags - Drupal 8.x style' => [
        '/path/to/repo#8.x-1.10',
        '/path/to/repo',
        '8.x-1.10',
      ],
      'Version tags - Drupal 9.x style' => [
        'file:///path/to/repo#9.x-2.3',
        '/path/to/repo',
        '9.x-2.3',
      ],

      // Version tags - Hybrid versioning.
      'Version tags - SemVer+CalVer hybrid' => [
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO) . '#1.0.0+2025.11.0',
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        '1.0.0+2025.11.0',
      ],

      // Version tags - Pre-release tags.
      'Version tags - pre-release rc' => [
        'git@github.com:user/repo#1.x-rc1',
        'git@github.com:user/repo',
        '1.x-rc1',
      ],

      // Branch names.
      'Branch names - main' => [
        'https://github.com/user/repo#main',
        'https://github.com/user/repo',
        'main',
      ],
      'Branch names - develop' => [
        'git@github.com:user/repo#develop',
        'git@github.com:user/repo',
        'develop',
      ],
      'Branch names - feature with slash' => [
        '/path/to/repo#feature/my-feature',
        '/path/to/repo',
        'feature/my-feature',
      ],
      'Branch names - bugfix with slash' => [
        'file:///path/to/repo#bugfix/fix-123',
        '/path/to/repo',
        'bugfix/fix-123',
      ],

      // GitHub URL patterns - Direct release URLs.
      'GitHub release URL - CalVer' => [
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO) . '/releases/tag/25.11.0',
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        '25.11.0',
      ],
      'GitHub release URL - SemVer with v prefix' => [
        'https://github.com/user/repo/releases/tag/v1.2.3',
        'https://github.com/user/repo',
        'v1.2.3',
      ],
      'GitHub release URL - SemVer without prefix' => [
        'https://github.com/org/project/releases/tag/2.0.0',
        'https://github.com/org/project',
        '2.0.0',
      ],

      // GitHub URL patterns - Tree URLs.
      'GitHub tree URL - SemVer' => [
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO) . '/tree/1.2.3',
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        '1.2.3',
      ],
      'GitHub tree URL - branch name' => [
        'https://github.com/user/repo/tree/main',
        'https://github.com/user/repo',
        'main',
      ],
      'GitHub tree URL - feature branch' => [
        'https://github.com/user/repo/tree/feature/new-ui',
        'https://github.com/user/repo',
        'feature/new-ui',
      ],

      // GitHub URL patterns - Commit URLs.
      'GitHub commit URL - full hash' => [
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO) . '/commit/1234567890abcdef1234567890abcdef12345678',
        str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'GitHub commit URL - short hash' => [
        'https://github.com/user/repo/commit/abcd123',
        'https://github.com/user/repo',
        'abcd123',
      ],

      // Alternative # syntax - HTTPS.
      'Hash syntax HTTPS - version tag' => [
        RepositoryDownloader::DEFAULT_REPO . '#25.11.0',
        RepositoryDownloader::DEFAULT_REPO,
        '25.11.0',
      ],
      'Hash syntax HTTPS - stable keyword' => [
        'https://github.com/user/repo.git#stable',
        'https://github.com/user/repo.git',
        'stable',
      ],
      'Hash syntax HTTPS - HEAD keyword' => [
        'https://github.com/user/repo.git#HEAD',
        'https://github.com/user/repo.git',
        'HEAD',
      ],
      'Hash syntax HTTPS - branch name' => [
        'https://github.com/user/repo.git#develop',
        'https://github.com/user/repo.git',
        'develop',
      ],
      'Hash syntax HTTPS - commit hash' => [
        'https://github.com/user/repo.git#abcd123',
        'https://github.com/user/repo.git',
        'abcd123',
      ],

      // Alternative # syntax - SSH.
      'Hash syntax SSH - version tag' => [
        'git@github.com:drevops/vortex#25.11.0',
        'git@github.com:drevops/vortex',
        '25.11.0',
      ],
      'Hash syntax SSH - stable keyword' => [
        'git@github.com:user/repo#stable',
        'git@github.com:user/repo',
        'stable',
      ],
      'Hash syntax SSH - branch name' => [
        'git@github.com:user/repo#main',
        'git@github.com:user/repo',
        'main',
      ],

      // Invalid test cases.
      'Invalid HTTPS URL formats - missing repo' => [
        'https://github.com',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com".',
      ],
      'Invalid HTTPS URL formats - missing repo with slash' => [
        'https://github.com/',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com/".',
      ],
      'Invalid HTTPS URL formats - missing repo name' => [
        'https://github.com/user',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com/user".',
      ],
      'Invalid HTTPS URL formats - missing repo name with slash' => [
        'https://github.com/user/',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com/user/".',
      ],
      'Invalid HTTPS URL formats - malformed with reference' => [
        'https://github.com/user#main',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com/user#main". Use # to specify a reference (e.g., repo.git#tag).',
      ],
      'Invalid HTTPS URL formats - protocol only' => [
        'https://',
        NULL,
        NULL,
        'Invalid remote repository format: "https://".',
      ],
      'Invalid HTTPS URL formats - protocol with slash' => [
        'https:///',
        NULL,
        NULL,
        'Invalid remote repository format: "https:///".',
      ],
      'Invalid Git SSH URL formats - missing colon' => [
        'git@github.com',
        NULL,
        NULL,
        'Invalid remote repository format: "git@github.com".',
      ],
      'Invalid Git SSH URL formats - empty after colon' => [
        'git@github.com:',
        NULL,
        NULL,
        'Invalid remote repository format: "git@github.com:".',
      ],
      'Invalid Git SSH URL formats - empty user' => [
        'git@',
        NULL,
        NULL,
        'Invalid remote repository format: "git@".',
      ],
      'Invalid Git SSH URL formats - empty host' => [
        'git@:user/repo',
        NULL,
        NULL,
        'Invalid remote repository format: "git@:user/repo".',
      ],
      'Invalid file URL formats - empty path' => [
        'file://',
        NULL,
        NULL,
        'Invalid local repository format: "file://".',
      ],
      'Invalid reference formats - special characters - exclamation' => [
        'https://github.com/user/repo#invalid-ref-with-special-chars!',
        NULL,
        NULL,
        'Invalid git reference: "invalid-ref-with-special-chars!". Reference must be a valid git tag, branch, or commit hash.',
      ],
      'Invalid reference formats - special characters - double hash' => [
        'https://github.com/user/repo#invalid##ref',
        NULL,
        NULL,
        'Invalid git reference: "invalid##ref". Reference must be a valid git tag, branch, or commit hash.',
      ],
      'Edge cases - hash in reference is invalid - HTTPS' => [
        'https://github.com/user/repo##main',
        NULL,
        NULL,
        'Invalid git reference: "#main". Reference must be a valid git tag, branch, or commit hash.',
      ],
      'Edge cases - hash in reference is invalid - Git SSH' => [
        'git@github.com:user/repo##main',
        NULL,
        NULL,
        'Invalid git reference: "#main". Reference must be a valid git tag, branch, or commit hash.',
      ],
    ];
  }

  /**
   * Data provider for testDiscoverLatestReleaseRemote().
   *
   * @return array<string, array<string, mixed>>
   *   Test data.
   */
  public static function providerDiscoverLatestReleaseRemote(): array {
    return [
      'valid releases' => [
        'repo' => 'https://github.com/user/repo',
        'releaseData' => [
          ['tag_name' => 'v2.0.0', 'draft' => FALSE],
          ['tag_name' => 'v1.0.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => 'v2.0.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
      'skips drafts' => [
        'repo' => 'https://github.com/user/repo',
        'releaseData' => [
          ['tag_name' => 'v3.0.0', 'draft' => TRUE],
          ['tag_name' => 'v2.0.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => 'v2.0.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
      'no releases' => [
        'repo' => 'https://github.com/user/repo',
        'releaseData' => [],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => NULL,
        'expectedException' => \RuntimeException::class,
        'expectedMessage' => 'Unable to discover the latest release',
      ],
      'request exception' => [
        'repo' => 'https://github.com/user/repo',
        'releaseData' => NULL,
        'throwException' => TRUE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => NULL,
        'expectedException' => \RuntimeException::class,
        'expectedMessage' => 'Unable to access repository',
      ],
      'empty response' => [
        'repo' => 'https://github.com/user/repo',
        'releaseData' => '',
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => NULL,
        'expectedException' => \RuntimeException::class,
        'expectedMessage' => 'Unable to download release information from',
      ],
      'invalid url' => [
        'repo' => 'https://',
        'releaseData' => NULL,
        'throwException' => FALSE,
        'skipMockSetup' => TRUE,
        'expectedVersion' => NULL,
        'expectedException' => \RuntimeException::class,
        'expectedMessage' => 'Invalid repository URL',
      ],
      'SemVer+CalVer format - single release' => [
        'repo' => str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        'releaseData' => [
          ['tag_name' => '1.0.0+2025.11.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => '1.0.0+2025.11.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
      'SemVer+CalVer format - multiple releases' => [
        'repo' => str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        'releaseData' => [
          ['tag_name' => '1.2.0+2025.12.0', 'draft' => FALSE],
          ['tag_name' => '1.1.0+2025.11.0', 'draft' => FALSE],
          ['tag_name' => '1.0.0+2025.10.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => '1.2.0+2025.12.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
      'SemVer+CalVer format - skip draft' => [
        'repo' => str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        'releaseData' => [
          ['tag_name' => '2.0.0+2026.01.0', 'draft' => TRUE],
          ['tag_name' => '1.0.0+2025.11.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => '1.0.0+2025.11.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
      'Mixed format - SemVer+CalVer and CalVer' => [
        'repo' => str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO),
        'releaseData' => [
          ['tag_name' => '1.0.0+2025.11.0', 'draft' => FALSE],
          ['tag_name' => '25.10.0', 'draft' => FALSE],
          ['tag_name' => '25.9.0', 'draft' => FALSE],
        ],
        'throwException' => FALSE,
        'skipMockSetup' => FALSE,
        'expectedVersion' => '1.0.0+2025.11.0',
        'expectedException' => NULL,
        'expectedMessage' => NULL,
      ],
    ];
  }

  /**
   * Data provider for testDownloadWithNullDestination().
   *
   * @return array<string, array<string, string>>
   *   Test data.
   */
  public static function providerDownloadWithNullDestination(): array {
    return [
      'remote repository' => [
        'repo' => 'https://github.com/user/repo',
        'expectedMessage' => 'Destination cannot be null for remote downloads',
      ],
      'local repository' => [
        'repo' => '/path/to/repo',
        'expectedMessage' => 'Destination cannot be null for local downloads',
      ],
    ];
  }

  /**
   * Data provider for testDownloadFromLocal().
   *
   * @return array<string, array<string, string>>
   *   Test data.
   */
  public static function providerDownloadFromLocal(): array {
    return [
      'HEAD ref' => [
        'ref' => 'HEAD',
        'expectedVersion' => 'develop',
      ],
      'stable ref' => [
        'ref' => 'stable',
        'expectedVersion' => 'develop',
      ],
      'commit hash' => [
        'ref' => 'COMMIT_HASH',
        'expectedVersion' => 'COMMIT_HASH',
      ],
    ];
  }

  protected function createMockHttpClient(int $statusCode = 200, string $bodyContent = 'mock content'): ClientInterface {
    $mock_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_body = $this->createMock(StreamInterface::class);
    $mock_response->method('getBody')->willReturn($mock_body);
    $mock_body->method('getContents')->willReturn($bodyContent);
    $mock_response->method('getStatusCode')->willReturn($statusCode);
    $mock_client->method('request')->willReturn($mock_response);
    return $mock_client;
  }

  protected function createMockArchiver(): MockObject {
    return $this->createMock(ArchiverInterface::class);
  }

  protected function createGitRepo(): string {
    $temp_repo_dir = self::$tmp . '/test_git_repo_' . uniqid();
    File::mkdir($temp_repo_dir);
    $original_dir = (string) getcwd();
    chdir($temp_repo_dir);
    exec('git init 2>&1');
    exec('git config user.email "test@example.com" 2>&1');
    exec('git config user.name "Test User" 2>&1');
    File::dump($temp_repo_dir . '/test.txt', 'test content');
    exec('git add . 2>&1');
    exec('git commit -m "Initial commit" 2>&1');
    File::dump($temp_repo_dir . '/composer.json', '{}');
    exec('git add composer.json 2>&1');
    exec('git commit -m "Add composer.json" 2>&1');
    chdir($original_dir);
    return $temp_repo_dir;
  }

  protected function removeGitRepo(string $repo_dir): void {
    File::rmdir($repo_dir);
  }

  protected function createMockArchiverWithExtract(): MockObject {
    $mock_archiver = $this->createMockArchiver();
    $mock_archiver->expects($this->once())->method('validate');
    $mock_archiver->expects($this->once())->method('extract')->willReturnCallback(function ($archive, string $dest): void {
      File::dump($dest . '/composer.json', '{}');
    });
    return $mock_archiver;
  }

  /**
   * @return \PHPUnit\Framework\MockObject\MockObject&\DrevOps\VortexInstaller\Downloader\Downloader
   *   Mock file downloader.
   */
  protected function createMockFileDownloader(): MockObject {
    return $this->createMock(Downloader::class);
  }

  public function testValidateRemoteRepositoryExistsWithNotFoundError(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(404);
    $mock_http_client->method('request')->willReturn($mock_response);
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Repository not found or not accessible: "https://github.com/user/nonexistent" (HTTP 404)');
    $downloader->download('https://github.com/user/nonexistent', '1.0.0', $destination);
  }

  public function testValidateRemoteRefExistsWithNotFoundError(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $repo_response = $this->createMock(ResponseInterface::class);
    $repo_response->method('getStatusCode')->willReturn(200);
    $ref_response = $this->createMock(ResponseInterface::class);
    $ref_response->method('getStatusCode')->willReturn(404);
    $mock_http_client->method('request')->willReturnCallback(function ($method, $url) use ($repo_response, $ref_response): ResponseInterface {
      if (str_contains($url, '/archive/')) {
        return $ref_response;
      }
      return $repo_response;
    });
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Reference "nonexistent-tag" not found in repository "https://github.com/user/repo"');
    $downloader->download('https://github.com/user/repo', 'nonexistent-tag', $destination);
  }

  public function testValidateLocalRepositoryExistsWithNonexistentPath(): void {
    $nonexistent_path = self::$tmp . '/nonexistent_repo_' . uniqid();
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Local repository path does not exist: "%s"', $nonexistent_path));
    $downloader->download($nonexistent_path, 'main', $destination);
  }

  public function testValidateLocalRepositoryExistsWithNonGitDirectory(): void {
    $non_git_path = self::$tmp . '/non_git_dir_' . uniqid();
    File::mkdir($non_git_path);
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Path is not a git repository: "%s"', $non_git_path));
    $downloader->download($non_git_path, 'main', $destination);
  }

  public function testValidateRemoteRepositoryExistsWithRequestException(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_http_client->method('request')->willThrowException(new RequestException('Connection timeout', $this->createMock(RequestInterface::class)));
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to access repository: "https://github.com/user/repo" - Connection timeout');
    $downloader->download('https://github.com/user/repo', '1.0.0', $destination);
  }

  public function testValidateRemoteRefExistsWithRequestException(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $repo_response = $this->createMock(ResponseInterface::class);
    $repo_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturnCallback(function ($method, $url) use ($repo_response): ResponseInterface {
      if (str_contains($url, '/archive/')) {
        throw new RequestException('Network error', $this->createMock(RequestInterface::class));
      }
      return $repo_response;
    });
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to verify reference "test-tag" in repository "https://github.com/user/repo" - Network error');
    $downloader->download('https://github.com/user/repo', 'test-tag', $destination);
  }

}
