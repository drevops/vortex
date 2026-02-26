<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Downloader\Artifact;
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
    $version = $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
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

    $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
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
    $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
  }

  public function testDownloadArchiveCreatesTemporaryFile(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_file_downloader = $this->createMockFileDownloader();
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new RepositoryDownloader($mock_http_client, $mock_archiver, NULL, $mock_file_downloader);
    $version = $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
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
    $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
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
    $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
  }

  #[DataProvider('dataProviderDiscoverLatestReleaseRemote')]
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

    $version = $downloader->download(Artifact::create($repo, 'stable'), $destination);

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
    $version = $downloader->download(Artifact::create('https://github.com/user/repo.git', 'HEAD'), $destination);
    $this->assertEquals('develop', $version);
  }

  #[DataProvider('dataProviderDownloadWithNullDestination')]
  public function testDownloadWithNullDestination(string $repo, string $expectedMessage): void {
    $downloader = new RepositoryDownloader();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($expectedMessage);
    $downloader->download(Artifact::create($repo, 'HEAD'));
  }

  #[DataProvider('dataProviderDownloadFromLocal')]
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
    $version = $downloader->download(Artifact::create($temp_repo_dir, $ref), $destination);
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
    $downloader->download(Artifact::create($temp_repo_dir, 'nonexistent-ref'), $temp_dest_dir);
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
    $version = $downloader->download(Artifact::create('https://github.com/user/repo', 'stable'), $destination);
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
    $version = $downloader->download(Artifact::create('https://github.com/user/repo', 'HEAD'), $destination);
    $this->assertEquals('develop', $version);
  }

  /**
   * Data provider for testDiscoverLatestReleaseRemote().
   *
   * @return array<string, array<string, mixed>>
   *   Test data.
   */
  public static function dataProviderDiscoverLatestReleaseRemote(): array {
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
        'expectedMessage' => 'Local repository path does not exist',
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
  public static function dataProviderDownloadWithNullDestination(): array {
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
  public static function dataProviderDownloadFromLocal(): array {
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
    File::remove($repo_dir);
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
    $downloader->download(Artifact::create('https://github.com/user/nonexistent', '1.0.0'), $destination);
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
    $downloader->download(Artifact::create('https://github.com/user/repo', 'nonexistent-tag'), $destination);
  }

  public function testValidateLocalRepositoryExistsWithNonexistentPath(): void {
    $nonexistent_path = self::$tmp . '/nonexistent_repo_' . uniqid();
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Local repository path does not exist: "%s"', $nonexistent_path));
    $downloader->download(Artifact::create($nonexistent_path, 'main'), $destination);
  }

  public function testValidateLocalRepositoryExistsWithNonGitDirectory(): void {
    $non_git_path = self::$tmp . '/non_git_dir_' . uniqid();
    File::mkdir($non_git_path);
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Path is not a git repository: "%s"', $non_git_path));
    $downloader->download(Artifact::create($non_git_path, 'main'), $destination);
  }

  public function testValidateRemoteRepositoryExistsWithRequestException(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_http_client->method('request')->willThrowException(new RequestException('Connection timeout', $this->createMock(RequestInterface::class)));
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);
    $downloader = new RepositoryDownloader($mock_http_client);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unable to access repository: "https://github.com/user/repo" - Connection timeout');
    $downloader->download(Artifact::create('https://github.com/user/repo', '1.0.0'), $destination);
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
    $downloader->download(Artifact::create('https://github.com/user/repo', 'test-tag'), $destination);
  }

  public function testValidateRemoteArtifactWithStableRef(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);
    $downloader = new RepositoryDownloader($mock_http_client);
    $artifact = Artifact::create('https://github.com/user/repo', 'stable');
    $downloader->validate($artifact);
    $this->expectNotToPerformAssertions();
  }

  public function testValidateRemoteArtifactWithCustomRef(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);
    $downloader = new RepositoryDownloader($mock_http_client);
    $artifact = Artifact::create('https://github.com/user/repo', 'v1.0.0');
    $downloader->validate($artifact);
    $this->expectNotToPerformAssertions();
  }

  public function testValidateLocalArtifactWithHeadRef(): void {
    $temp_repo_dir = $this->createGitRepo();
    $downloader = new RepositoryDownloader();
    $artifact = Artifact::create($temp_repo_dir, 'HEAD');
    $downloader->validate($artifact);
    $this->expectNotToPerformAssertions();
    $this->removeGitRepo($temp_repo_dir);
  }

  public function testValidateLocalArtifactWithCustomRef(): void {
    $temp_repo_dir = $this->createGitRepo();
    $downloader = new RepositoryDownloader();
    $artifact = Artifact::create($temp_repo_dir, 'main');
    $downloader->validate($artifact);
    $this->expectNotToPerformAssertions();
    $this->removeGitRepo($temp_repo_dir);
  }

}
