<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Validator;

/**
 * Class InstallerHelpersTest.
 */
#[CoversClass(Validator::class)]
class ValidatorTest extends UnitTestCase {

  #[DataProvider('dataProviderContainerImage')]
  public function testContainerImage(string $input, bool $expected): void {
    $this->assertSame($expected, Validator::containerImage($input));
  }

  public static function dataProviderContainerImage(): array {
    return [
      ['myregistryhost:5000/fedora/httpd:version', TRUE],
      ['fedora/httpd:version1.0.test', TRUE],
      ['fedora/httpd:version1.0', TRUE],
      ['rabbit:3', TRUE],
      ['rabbit', TRUE],
      ['registry/rabbit:3', TRUE],
      ['registry/rabbit', TRUE],
      ['invalid@name!', FALSE],
      ['UPPERCASE/Repo:Tag', FALSE],
      ['registry.example.com/image', TRUE],
      ['registry.example.com:8080/image:v1.2', TRUE],
      ['multiple//slashes', FALSE],
      [' spaced name ', FALSE],
      ['trailing.dot.', FALSE],
      ['test:super-long-tag-that-exceeds-128-characters-aaaaaaaaaabbbbbbbbbbbbccccccccccddddddddddeeeeeeeeeeaaaaaaaaaabbbbbbbbbbbbccccccccccddddddddddeeeeeeeeee', FALSE],
    ];
  }

  #[DataProvider('dataProviderDomain')]
  public function testDomain(string $domain, bool $expected): void {
    $this->assertSame($expected, Validator::domain($domain));
  }

  public static function dataProviderDomain(): array {
    return [
      'valid domain with TLD' => ['example.com', TRUE],
      'valid subdomain' => ['sub.example.com', TRUE],
      'valid domain with multiple dots' => ['example.co.uk', TRUE],
      'invalid domain without TLD' => ['myproject', FALSE],
      'invalid domain with only dot' => ['.', FALSE],
      'invalid domain with special characters' => ['invalid_domain.com', FALSE],
      'invalid empty string' => ['', FALSE],
      'invalid numeric domain' => ['123456', FALSE],
      'invalid IP address' => ['192.168.1.1', FALSE],
      'invalid domain with spaces' => ['example .com', FALSE],
    ];
  }

  #[DataProvider('dataProviderGithubProject')]
  public function testGithubProject(string $value, bool $expected): void {
    $this->assertSame($expected, Validator::githubProject($value));
  }

  public static function dataProviderGithubProject(): array {
    return [
      'valid project' => ['user/repo', TRUE],
      'valid project with numbers' => ['user123/repo456', TRUE],
      'valid project with hyphens and underscores' => ['user-name/repo_name', TRUE],
      'valid project with mixed case' => ['UserName/RepoName', TRUE],
      'invalid missing slash' => ['userrepo', FALSE],
      'invalid leading slash' => ['/repo', FALSE],
      'invalid trailing slash' => ['user/', FALSE],
      'invalid empty string' => ['', FALSE],
      'invalid extra slash' => ['user/repo/extra', FALSE],
      'invalid spaces' => ['user /repo', FALSE],
      'invalid special characters' => ['user!@#/repo$', FALSE],
    ];
  }

  #[DataProvider('dataProviderDirname')]
  public function testDirname(string $input, bool $expected): void {
    $this->assertSame($expected, Validator::dirname($input));
  }

  public static function dataProviderDirname(): array {
    return [
      'valid folder name' => ['valid_folder', TRUE],
      'valid with hyphen' => ['my-folder', TRUE],
      'valid with dot' => ['another.folder', TRUE],
      'valid with space' => ['folder name', FALSE],
      'valid with leading dot' => ['.hidden_folder', TRUE],
      'valid with numbers' => ['folder123', TRUE],
      'invalid Windows reserved name CON' => ['CON', FALSE],
      'invalid Windows reserved name NUL' => ['NUL', FALSE],
      'invalid Windows reserved name COM1' => ['COM1', FALSE],
      'invalid with forward slash' => ['folder/name', FALSE],
      'invalid with backslash' => ['folder\name', FALSE],
      'invalid with pipe' => ['folder|name', FALSE],
      'invalid with angle brackets' => ['folder<>name', FALSE],
      'invalid single dot' => ['.', FALSE],
      'invalid double dot' => ['..', FALSE],
    ];
  }

  #[DataProvider('dataProviderGitCommitSha')]
  public function testGitCommitSha(string $sha, bool $expected): void {
    $this->assertSame($expected, Validator::gitCommitSha($sha));
  }

  public static function dataProviderGitCommitSha(): array {
    return [
      // Valid SHA-1 hashes (40 hexadecimal characters)
      'valid lowercase SHA' => ['a1b2c3d4e5f6789012345678901234567890abcd', TRUE],
      'valid uppercase SHA' => ['A1B2C3D4E5F6789012345678901234567890ABCD', TRUE],
      'valid mixed case SHA' => ['a1B2c3D4e5F6789012345678901234567890AbCd', TRUE],
      'valid all numbers SHA' => ['1234567890123456789012345678901234567890', TRUE],
      'valid all letters SHA' => ['abcdefabcdefabcdefabcdefabcdefabcdefabcd', TRUE],

      // Invalid SHA hashes.
      'invalid too short' => ['a1b2c3d4e5f6789012345678901234567890abc', FALSE],
      'invalid too long' => ['a1b2c3d4e5f6789012345678901234567890abcdef', FALSE],
      'invalid with non-hex characters' => ['a1b2c3d4e5f6789012345678901234567890abcg', FALSE],
      'invalid with special characters' => ['a1b2c3d4e5f6789012345678901234567890ab!@', FALSE],
      'invalid with spaces' => ['a1b2c3d4e5f6 789012345678901234567890abcd', FALSE],
      'invalid empty string' => ['', FALSE],
      'invalid only spaces' => ['                                        ', FALSE],
      'invalid with hyphens' => ['a1b2c3d4-e5f6-7890-1234-567890abcd', FALSE],
      'invalid partial match' => ['123', FALSE],
      'invalid null characters' => ["a1b2c3d4e5f6789012345678901234567890abc\0", FALSE],
    ];
  }

  #[DataProvider('dataProviderGitCommitShaShort')]
  public function testGitCommitShaShort(string $sha_short, bool $expected): void {
    $this->assertSame($expected, Validator::gitCommitShaShort($sha_short));
  }

  public static function dataProviderGitCommitShaShort(): array {
    return [
      // Valid short SHA-1 hashes (7 hexadecimal characters)
      'valid lowercase short SHA' => ['a1b2c3d', TRUE],
      'valid uppercase short SHA' => ['A1B2C3D', TRUE],
      'valid mixed case short SHA' => ['a1B2c3D', TRUE],
      'valid all numbers short SHA' => ['1234567', TRUE],
      'valid all letters short SHA' => ['abcdef0', TRUE],
      'valid with f characters' => ['fffffff', TRUE],
      'valid with 0 characters' => ['0000000', TRUE],

      // Invalid short SHA hashes.
      'invalid too short (6 chars)' => ['a1b2c3', FALSE],
      'invalid too short (1 char)' => ['a', FALSE],
      'invalid too long (8 chars)' => ['a1b2c3d4', FALSE],
      'invalid too long (40 chars)' => ['a1b2c3d4e5f6789012345678901234567890abcd', FALSE],
      'invalid with non-hex characters' => ['a1b2c3g', FALSE],
      'invalid with special characters' => ['a1b2c!@', FALSE],
      'invalid with spaces' => ['a1b 2c3', FALSE],
      'invalid with leading space' => [' a1b2c3', FALSE],
      'invalid with trailing space' => ['a1b2c3 ', FALSE],
      'invalid empty string' => ['', FALSE],
      'invalid only spaces' => ['       ', FALSE],
      'invalid with hyphens' => ['a1b-2c3', FALSE],
      'invalid with underscores' => ['a1b_2c3', FALSE],
      'invalid null characters' => ["a1b2c3\0", FALSE],
    ];
  }

}
