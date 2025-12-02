<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Downloader\ArchiverInterface;
use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(Downloader::class)]
class DownloaderTest extends UnitTestCase {

  #[DataProvider('dataProviderParseUri')]
  public function testParseUri(string $src, ?string $expected_repo = NULL, ?string $expected_ref = NULL, ?string $expected_exception_message = NULL): void {
    if (!is_null($expected_exception_message)) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }

    $result = Downloader::parseUri($src);

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
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public function testDownloadThrowsExceptionWhenComposerJsonMissing(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_body = $this->createMock(StreamInterface::class);

    $mock_response->method('getBody')->willReturn($mock_body);
    $mock_body->method('getContents')->willReturn('mock content');
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);

    $destination = self::$tmp . '/destination';
    File::mkdir($destination);

    $downloader = new Downloader($mock_http_client, $mock_archiver);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The downloaded repository does not contain a composer.json file.');

    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadFromRemoteCallsArchiverCorrectly(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_body = $this->createMock(StreamInterface::class);

    $mock_response->method('getBody')->willReturn($mock_body);
    $mock_body->method('getContents')->willReturn('mock content');
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);

    $mock_archiver->expects($this->once())->method('validate')->with($this->stringContains('vortex_archive_'));
    $mock_archiver->expects($this->once())->method('extract')->with($this->stringContains('vortex_archive_'), $this->anything(), TRUE);

    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');

    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadArchiveCreatesTemporaryFile(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->method('request')->willReturn($mock_response);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public function testDownloadArchiveHandlesHttpError(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(404);
    $mock_http_client->method('request')->willReturn($mock_response);
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to download archive: HTTP 404');
    $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
  }

  public function testDownloadArchiveHandlesRequestException(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_http_client->method('request')->willThrowException(new RequestException('Network error', $this->createMock(RequestInterface::class)));
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    $downloader = new Downloader($mock_http_client, $mock_archiver);
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

        if ($expectedVersion !== NULL) {
          $mock_http_client->expects($this->exactly(2))->method('request')->willReturnOnConsecutiveCalls($mock_response, $mock_response);
        }
        else {
          $mock_http_client->method('request')->willReturn($mock_response);
        }
      }
    }

    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination_' . uniqid();
    File::mkdir($destination);

    if ($expectedVersion !== NULL) {
      File::dump($destination . '/composer.json', '{}');
    }

    $downloader = new Downloader($mock_http_client, $mock_archiver);

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
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $version = $downloader->download('https://github.com/user/repo.git', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  #[DataProvider('providerDownloadWithNullDestination')]
  public function testDownloadWithNullDestination(string $repo, string $expectedMessage): void {
    $downloader = new Downloader();
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
    $downloader = new Downloader(NULL, $mock_archiver);
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
    $downloader = new Downloader();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to create archive from local repository');
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
    $mock_http_client->expects($this->exactly(2))->method('request')->willReturnCallback(function ($method, $url, array|\ArrayAccess $options) use ($mock_response): ResponseInterface {
      $this->assertArrayHasKey('headers', $options);
      $this->assertArrayHasKey('Authorization', $options['headers']);
      $this->assertEquals('Bearer test_token_12345', $options['headers']['Authorization']);
      return $mock_response;
    });
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $version = $downloader->download('https://github.com/user/repo', 'stable', $destination);
    $this->assertEquals('v1.5.0', $version);
  }

  public function testDownloadArchiveWithGithubToken(): void {
    static::envSet('GITHUB_TOKEN', 'test_token_67890');
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->expects($this->once())->method('request')->willReturnCallback(function ($method, $url, array|\ArrayAccess $options) use ($mock_response): ResponseInterface {
      $this->assertArrayHasKey('headers', $options);
      $this->assertArrayHasKey('Authorization', $options['headers']);
      $this->assertEquals('Bearer test_token_67890', $options['headers']['Authorization']);
      return $mock_response;
    });
    $mock_archiver = $this->createMock(ArchiverInterface::class);
    $destination = self::$tmp . '/destination';
    File::mkdir($destination);
    File::dump($destination . '/composer.json', '{}');
    $downloader = new Downloader($mock_http_client, $mock_archiver);
    $version = $downloader->download('https://github.com/user/repo', 'HEAD', $destination);
    $this->assertEquals('develop', $version);
  }

  public static function dataProviderParseUri(): array {
    return [
      // Valid test cases.
      'HTTPS URLs - with default HEAD reference - GitHub' => [
        'https://github.com/user/repo',
        'https://github.com/user/repo',
        Downloader::REF_HEAD,

      ],
      'HTTPS URLs - with default HEAD reference - GitLab' => [
        'https://gitlab.com/user/repo',
        'https://gitlab.com/user/repo',
        Downloader::REF_HEAD,

      ],
      'HTTPS URLs - with default HEAD reference - Bitbucket' => [
        'https://bitbucket.org/user/repo',
        'https://bitbucket.org/user/repo',
        Downloader::REF_HEAD,

      ],
      'HTTPS URLs - with specific valid references - stable' => [
        'https://github.com/user/repo@stable',
        'https://github.com/user/repo',
        Downloader::REF_STABLE,

      ],
      'HTTPS URLs - with specific valid references - HEAD' => [
        'https://github.com/user/repo@HEAD',
        'https://github.com/user/repo',
        Downloader::REF_HEAD,

      ],
      'HTTPS URLs - with 40-character commit hash' => [
        'https://github.com/user/repo@1234567890abcdef1234567890abcdef12345678',
        'https://github.com/user/repo',
        '1234567890abcdef1234567890abcdef12345678',

      ],
      'HTTPS URLs - with 7-character short commit hash' => [
        'https://github.com/user/repo@1234567',
        'https://github.com/user/repo',
        '1234567',

      ],
      'Git SSH URLs - with default HEAD reference - GitHub' => [
        'git@github.com:user/repo',
        'git@github.com:user/repo',
        Downloader::REF_HEAD,
      ],
      'Git SSH URLs - with default HEAD reference - GitLab' => [
        'git@gitlab.com:user/repo',
        'git@gitlab.com:user/repo',
        Downloader::REF_HEAD,
      ],
      'Git SSH URLs - with default HEAD reference - Bitbucket' => [
        'git@bitbucket.org:user/repo',
        'git@bitbucket.org:user/repo',
        Downloader::REF_HEAD,
      ],
      'Git SSH URLs - with specific valid references - stable' => [
        'git@github.com:user/repo@stable',
        'git@github.com:user/repo',
        Downloader::REF_STABLE,
      ],
      'Git SSH URLs - with specific valid references - HEAD' => [
        'git@github.com:user/repo@HEAD',
        'git@github.com:user/repo',
        Downloader::REF_HEAD,
      ],
      'Git SSH URLs - with commit hashes - 40 char' => [
        'git@github.com:user/repo@1234567890abcdef1234567890abcdef12345678',
        'git@github.com:user/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'Git SSH URLs - with commit hashes - 7 char' => [
        'git@github.com:user/repo@1234567',
        'git@github.com:user/repo',
        '1234567',
      ],
      'File URLs - with default HEAD reference' => [
        'file:///path/to/repo',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'File URLs - with default HEAD reference - user path' => [
        'file:///home/user/repos/myrepo',
        '/home/user/repos/myrepo',
        Downloader::REF_HEAD,
      ],
      'File URLs - with specific valid references - stable' => [
        'file:///path/to/repo@stable',
        '/path/to/repo',
        Downloader::REF_STABLE,
      ],
      'File URLs - with specific valid references - HEAD' => [
        'file:///path/to/repo@HEAD',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'File URLs - with 40-character commit hash' => [
        'file:///path/to/repo@1234567890abcdef1234567890abcdef12345678',
        '/path/to/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'File URLs - with 7-character commit hash' => [
        'file:///path/to/repo@1234567',
        '/path/to/repo',
        '1234567',
      ],
      'Local paths - with default HEAD reference - absolute' => [
        '/path/to/repo',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - user home' => [
        '/home/user/repos/myrepo',
        '/home/user/repos/myrepo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - relative' => [
        'relative/path/to/repo',
        'relative/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - current dir' => [
        './repo',
        './repo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with default HEAD reference - parent dir' => [
        '../repo',
        '../repo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with specific valid references - stable' => [
        '/path/to/repo@stable',
        '/path/to/repo',
        Downloader::REF_STABLE,
      ],
      'Local paths - with specific valid references - HEAD' => [
        '/path/to/repo@HEAD',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'Local paths - with 40-character commit hash' => [
        '/path/to/repo@1234567890abcdef1234567890abcdef12345678',
        '/path/to/repo',
        '1234567890abcdef1234567890abcdef12345678',
      ],
      'Local paths - with 7-character commit hash' => [
        '/path/to/repo@1234567',
        '/path/to/repo',
        '1234567',
      ],
      'Local paths with trailing slashes - should be trimmed - single slash' => [
        '/path/to/repo/',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'Local paths with trailing slashes - should be trimmed - double slash' => [
        '/path/to/repo//',
        '/path/to/repo',
        Downloader::REF_HEAD,
      ],
      'Local paths with trailing slashes - should be trimmed - with reference' => [
        '/path/to/repo/@stable',
        '/path/to/repo',
        Downloader::REF_STABLE,
      ],
      'Relative paths - simple' => [
        'repo',
        'repo',
        Downloader::REF_HEAD,
      ],
      'Relative paths - with reference' => [
        'repo@stable',
        'repo',
        Downloader::REF_STABLE,
      ],
      'Edge cases with valid commit hashes - uppercase 40 char' => [
        'https://github.com/user/repo@ABCDEF1234567890ABCDEF1234567890ABCDEF12',
        'https://github.com/user/repo',
        'ABCDEF1234567890ABCDEF1234567890ABCDEF12',
      ],
      'Edge cases with valid commit hashes - uppercase 7 char' => [
        'git@github.com:user/repo@ABCDEF1',
        'git@github.com:user/repo',
        'ABCDEF1',
      ],
      'Edge cases that are actually valid - HTTPS with extra path' => [
        'https://github.com/user/repo/extra/path',
        'https://github.com/user/repo/extra/path',
        Downloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - Git SSH with extra path' => [
        'git@github.com:user/repo/extra',
        'git@github.com:user/repo/extra',
        Downloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - protocol-less GitHub' => [
        'github.com/user/repo',
        'github.com/user/repo',
        Downloader::REF_HEAD,
      ],
      'Edge cases that are actually valid - file root' => [
        'file:///',
        '/',
        Downloader::REF_HEAD,
      ],
      'Empty reference defaults to HEAD - @ is captured in repo part' => [
        '/path/to/repo@',
        '/path/to/repo@',
        Downloader::REF_HEAD,
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
        'https://github.com/user@main',
        NULL,
        NULL,
        'Invalid remote repository format: "https://github.com/user@main".',
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
      'Invalid Git SSH URL formats - missing repo name' => [
        'git@github.com:user',
        NULL,
        NULL,
        'Invalid remote repository format: "git@github.com:user".',
      ],
      'Invalid Git SSH URL formats - malformed with reference' => [
        'git@github.com:user@main',
        NULL,
        NULL,
        'Invalid remote repository format: "git@github.com:user@main".',
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
      'Invalid reference formats - branch names not allowed - main' => [
        'https://github.com/user/repo@main',
        NULL,
        NULL,
        'Invalid reference format: "main". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid reference formats - branch names not allowed - develop' => [
        'git@github.com:user/repo@develop',
        NULL,
        NULL,
        'Invalid reference format: "develop". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid reference formats - branch names not allowed - version tag' => [
        '/path/to/repo@v1.0.0',
        NULL,
        NULL,
        'Invalid reference format: "v1.0.0". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid reference formats - branch names not allowed - feature branch' => [
        'file:///path/to/repo@feature-branch',
        NULL,
        NULL,
        'Invalid reference format: "feature-branch". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid reference formats - special characters - exclamation' => [
        'https://github.com/user/repo@invalid-ref-with-special-chars!',
        NULL,
        NULL,
        'Invalid reference format: "invalid-ref-with-special-chars!". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid reference formats - special characters - double @' => [
        'git@github.com:user/repo@invalid@ref',
        NULL,
        NULL,
        'Invalid reference format: "invalid@ref". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 6 chars' => [
        'file:///path/to/repo@123456',
        NULL,
        NULL,
        'Invalid reference format: "123456". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 8 chars' => [
        'https://github.com/user/repo@12345678',
        NULL,
        NULL,
        'Invalid reference format: "12345678". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 39 chars' => [
        'git@github.com:user/repo@123456789012345678901234567890123456789',
        NULL,
        NULL,
        'Invalid reference format: "123456789012345678901234567890123456789". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - invalid characters - 40 char with g' => [
        'https://github.com/user/repo@1234567890abcdef1234567890abcdef1234567g',
        NULL,
        NULL,
        'Invalid reference format: "1234567890abcdef1234567890abcdef1234567g". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - invalid characters - 7 char with g' => [
        '/path/to/repo@123456g',
        NULL,
        NULL,
        'Invalid reference format: "123456g". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 3 chars' => [
        'https://github.com/user/repo@123',
        NULL,
        NULL,
        'Invalid reference format: "123". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 41 chars' => [
        'git@github.com:user/repo@12345678901234567890123456789012345678901',
        NULL,
        NULL,
        'Invalid reference format: "12345678901234567890123456789012345678901". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Invalid commit hash formats - wrong length - 39 chars mixed' => [
        '/path/to/repo@1234567890abcdef1234567890abcdef123456789',
        NULL,
        NULL,
        'Invalid reference format: "1234567890abcdef1234567890abcdef123456789". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Edge cases - double @ character results in reference validation error - HTTPS' => [
        'https://github.com/user/repo@@main',
        NULL,
        NULL,
        'Invalid reference format: "@main". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Edge cases - double @ character results in reference validation error - Git SSH' => [
        'git@github.com:user/repo@@main',
        NULL,
        NULL,
        'Invalid reference format: "@main". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
      ],
      'Protocol-less URLs - treated as local paths - reference validation error' => [
        'user/repo@github.com',
        NULL,
        NULL,
        'Invalid reference format: "github.com". Supported formats are: stable, HEAD, 40-character commit hash, 7-character commit hash.',
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
        'expectedMessage' => 'Unable to download release information from',
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
    exec('rm -rf ' . escapeshellarg($repo_dir) . ' 2>&1');
  }

  protected function createMockArchiverWithExtract(): MockObject {
    $mock_archiver = $this->createMockArchiver();
    $mock_archiver->expects($this->once())->method('validate');
    $mock_archiver->expects($this->once())->method('extract')->willReturnCallback(function ($archive, string $dest): void {
      File::dump($dest . '/composer.json', '{}');
    });
    return $mock_archiver;
  }

}
