<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\Downloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

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

}
