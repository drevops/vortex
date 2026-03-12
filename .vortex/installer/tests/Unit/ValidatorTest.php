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

  public static function dataProviderContainerImage(): \Iterator {
    yield ['myregistryhost:5000/fedora/httpd:version', TRUE];
    yield ['fedora/httpd:version1.0.test', TRUE];
    yield ['fedora/httpd:version1.0', TRUE];
    yield ['rabbit:3', TRUE];
    yield ['rabbit', TRUE];
    yield ['registry/rabbit:3', TRUE];
    yield ['registry/rabbit', TRUE];
    yield ['invalid@name!', FALSE];
    yield ['UPPERCASE/Repo:Tag', FALSE];
    yield ['registry.example.com/image', TRUE];
    yield ['registry.example.com:8080/image:v1.2', TRUE];
    yield ['multiple//slashes', FALSE];
    yield [' spaced name ', FALSE];
    yield ['trailing.dot.', FALSE];
    yield ['test:super-long-tag-that-exceeds-128-characters-aaaaaaaaaabbbbbbbbbbbbccccccccccddddddddddeeeeeeeeeeaaaaaaaaaabbbbbbbbbbbbccccccccccddddddddddeeeeeeeeee', FALSE];
  }

  #[DataProvider('dataProviderDomain')]
  public function testDomain(string $domain, bool $expected): void {
    $this->assertSame($expected, Validator::domain($domain));
  }

  public static function dataProviderDomain(): \Iterator {
    yield 'valid domain with TLD' => ['example.com', TRUE];
    yield 'valid subdomain' => ['sub.example.com', TRUE];
    yield 'valid domain with multiple dots' => ['example.co.uk', TRUE];
    yield 'invalid domain without TLD' => ['myproject', FALSE];
    yield 'invalid domain with only dot' => ['.', FALSE];
    yield 'invalid domain with special characters' => ['invalid_domain.com', FALSE];
    yield 'invalid empty string' => ['', FALSE];
    yield 'invalid numeric domain' => ['123456', FALSE];
    yield 'invalid IP address' => ['192.168.1.1', FALSE];
    yield 'invalid domain with spaces' => ['example .com', FALSE];
  }

  #[DataProvider('dataProviderGithubProject')]
  public function testGithubProject(string $value, bool $expected): void {
    $this->assertSame($expected, Validator::githubProject($value));
  }

  public static function dataProviderGithubProject(): \Iterator {
    yield 'valid project' => ['user/repo', TRUE];
    yield 'valid project with numbers' => ['user123/repo456', TRUE];
    yield 'valid project with hyphens and underscores' => ['user-name/repo_name', TRUE];
    yield 'valid project with mixed case' => ['UserName/RepoName', TRUE];
    yield 'invalid missing slash' => ['userrepo', FALSE];
    yield 'invalid leading slash' => ['/repo', FALSE];
    yield 'invalid trailing slash' => ['user/', FALSE];
    yield 'invalid empty string' => ['', FALSE];
    yield 'invalid extra slash' => ['user/repo/extra', FALSE];
    yield 'invalid spaces' => ['user /repo', FALSE];
    yield 'invalid special characters' => ['user!@#/repo$', FALSE];
  }

  #[DataProvider('dataProviderDirname')]
  public function testDirname(string $input, bool $expected): void {
    $this->assertSame($expected, Validator::dirname($input));
  }

  public static function dataProviderDirname(): \Iterator {
    yield 'valid folder name' => ['valid_folder', TRUE];
    yield 'valid with hyphen' => ['my-folder', TRUE];
    yield 'valid with dot' => ['another.folder', TRUE];
    yield 'valid with space' => ['folder name', FALSE];
    yield 'valid with leading dot' => ['.hidden_folder', TRUE];
    yield 'valid with numbers' => ['folder123', TRUE];
    yield 'invalid Windows reserved name CON' => ['CON', FALSE];
    yield 'invalid Windows reserved name NUL' => ['NUL', FALSE];
    yield 'invalid Windows reserved name COM1' => ['COM1', FALSE];
    yield 'invalid with forward slash' => ['folder/name', FALSE];
    yield 'invalid with backslash' => ['folder\name', FALSE];
    yield 'invalid with pipe' => ['folder|name', FALSE];
    yield 'invalid with angle brackets' => ['folder<>name', FALSE];
    yield 'invalid single dot' => ['.', FALSE];
    yield 'invalid double dot' => ['..', FALSE];
  }

  #[DataProvider('dataProviderGitCommitSha')]
  public function testGitCommitSha(string $sha, bool $expected): void {
    $this->assertSame($expected, Validator::gitCommitSha($sha));
  }

  public static function dataProviderGitCommitSha(): \Iterator {
    // Valid SHA-1 hashes (40 hexadecimal characters)
    yield 'valid lowercase SHA' => ['a1b2c3d4e5f6789012345678901234567890abcd', TRUE];
    yield 'valid uppercase SHA' => ['A1B2C3D4E5F6789012345678901234567890ABCD', TRUE];
    yield 'valid mixed case SHA' => ['a1B2c3D4e5F6789012345678901234567890AbCd', TRUE];
    yield 'valid all numbers SHA' => ['1234567890123456789012345678901234567890', TRUE];
    yield 'valid all letters SHA' => ['abcdefabcdefabcdefabcdefabcdefabcdefabcd', TRUE];
    // Invalid SHA hashes.
    yield 'invalid too short' => ['a1b2c3d4e5f6789012345678901234567890abc', FALSE];
    yield 'invalid too long' => ['a1b2c3d4e5f6789012345678901234567890abcdef', FALSE];
    yield 'invalid with non-hex characters' => ['a1b2c3d4e5f6789012345678901234567890abcg', FALSE];
    yield 'invalid with special characters' => ['a1b2c3d4e5f6789012345678901234567890ab!@', FALSE];
    yield 'invalid with spaces' => ['a1b2c3d4e5f6 789012345678901234567890abcd', FALSE];
    yield 'invalid empty string' => ['', FALSE];
    yield 'invalid only spaces' => ['                                        ', FALSE];
    yield 'invalid with hyphens' => ['a1b2c3d4-e5f6-7890-1234-567890abcd', FALSE];
    yield 'invalid partial match' => ['123', FALSE];
    yield 'invalid null characters' => ["a1b2c3d4e5f6789012345678901234567890abc\0", FALSE];
  }

  #[DataProvider('dataProviderGitCommitShaShort')]
  public function testGitCommitShaShort(string $sha_short, bool $expected): void {
    $this->assertSame($expected, Validator::gitCommitShaShort($sha_short));
  }

  public static function dataProviderGitCommitShaShort(): \Iterator {
    // Valid short SHA-1 hashes (7 hexadecimal characters)
    yield 'valid lowercase short SHA' => ['a1b2c3d', TRUE];
    yield 'valid uppercase short SHA' => ['A1B2C3D', TRUE];
    yield 'valid mixed case short SHA' => ['a1B2c3D', TRUE];
    yield 'valid all numbers short SHA' => ['1234567', TRUE];
    yield 'valid all letters short SHA' => ['abcdef0', TRUE];
    yield 'valid with f characters' => ['fffffff', TRUE];
    yield 'valid with 0 characters' => ['0000000', TRUE];
    // Invalid short SHA hashes.
    yield 'invalid too short (6 chars)' => ['a1b2c3', FALSE];
    yield 'invalid too short (1 char)' => ['a', FALSE];
    yield 'invalid too long (8 chars)' => ['a1b2c3d4', FALSE];
    yield 'invalid too long (40 chars)' => ['a1b2c3d4e5f6789012345678901234567890abcd', FALSE];
    yield 'invalid with non-hex characters' => ['a1b2c3g', FALSE];
    yield 'invalid with special characters' => ['a1b2c!@', FALSE];
    yield 'invalid with spaces' => ['a1b 2c3', FALSE];
    yield 'invalid with leading space' => [' a1b2c3', FALSE];
    yield 'invalid with trailing space' => ['a1b2c3 ', FALSE];
    yield 'invalid empty string' => ['', FALSE];
    yield 'invalid only spaces' => ['       ', FALSE];
    yield 'invalid with hyphens' => ['a1b-2c3', FALSE];
    yield 'invalid with underscores' => ['a1b_2c3', FALSE];
    yield 'invalid null characters' => ["a1b2c3\0", FALSE];
  }

  #[DataProvider('dataProviderGitRef')]
  public function testGitRef(string $ref, bool $expected): void {
    $this->assertSame($expected, Validator::gitRef($ref));
  }

  public static function dataProviderGitRef(): \Iterator {
    // Special keywords.
    yield 'special keyword stable' => ['stable', TRUE];
    yield 'special keyword HEAD' => ['HEAD', TRUE];
    // Commit hashes (already tested, but included for completeness).
    yield 'valid 40-char commit hash' => ['a1b2c3d4e5f6789012345678901234567890abcd', TRUE];
    yield 'valid 7-char commit hash' => ['a1b2c3d', TRUE];
    // Semantic versioning tags.
    yield 'semver without prefix' => ['1.2.3', TRUE];
    yield 'semver with v prefix' => ['v1.2.3', TRUE];
    yield 'semver with patch zero' => ['2.0.0', TRUE];
    yield 'semver with pre-release' => ['1.2.3-beta', TRUE];
    yield 'semver with pre-release alpha' => ['1.2.3-alpha.1', TRUE];
    yield 'semver with pre-release numbered' => ['1.2.3-beta.1', TRUE];
    yield 'semver with build metadata' => ['1.2.3+20130313144700', TRUE];
    yield 'semver with build metadata simple' => ['1.2.3+build', TRUE];
    yield 'semver with pre-release and build' => ['1.2.3-alpha.1+build.123', TRUE];
    // Calendar versioning tags.
    yield 'calver YY.MM.PATCH' => ['24.10.0', TRUE];
    yield 'calver YY.MM.PATCH with higher version' => ['25.11.0', TRUE];
    yield 'calver YYYY.MM.PATCH' => ['2024.12.3', TRUE];
    // Drupal-style versioning.
    yield 'drupal 8.x version' => ['8.x-1.10', TRUE];
    yield 'drupal 9.x version' => ['9.x-2.3', TRUE];
    yield 'drupal 10.x version' => ['10.x-1.0', TRUE];
    // Hybrid versioning (SemVer with CalVer build metadata).
    yield 'semver+calver hybrid' => ['1.0.0+2025.11.0', TRUE];
    yield 'semver+calver hybrid v2' => ['1.2.0+2025.12.0', TRUE];
    yield 'semver+calver with pre-release' => ['1.0.0-beta+2025.11.0', TRUE];
    // Pre-release tags.
    yield 'pre-release rc' => ['1.x-rc1', TRUE];
    yield 'pre-release beta' => ['2.0.0-beta', TRUE];
    yield 'pre-release alpha' => ['3.0.0-alpha', TRUE];
    // Branch names.
    yield 'branch main' => ['main', TRUE];
    yield 'branch master' => ['master', TRUE];
    yield 'branch develop' => ['develop', TRUE];
    yield 'branch feature with slash' => ['feature/my-feature', TRUE];
    yield 'branch bugfix with slash' => ['bugfix/fix-123', TRUE];
    yield 'branch release with slash' => ['release/1.0', TRUE];
    // Invalid formats - special characters.
    yield 'invalid with @' => ['invalid@ref', FALSE];
    yield 'invalid with ^' => ['invalid^ref', FALSE];
    yield 'invalid with ~' => ['invalid~ref', FALSE];
    yield 'invalid with :' => ['invalid:ref', FALSE];
    yield 'invalid with ?' => ['invalid?ref', FALSE];
    yield 'invalid with *' => ['invalid*ref', FALSE];
    yield 'invalid with [' => ['invalid[ref', FALSE];
    yield 'invalid with space' => ['invalid ref', FALSE];
    yield 'invalid with backslash' => ['invalid\ref', FALSE];
    yield 'invalid with @{' => ['invalid@{ref', FALSE];
    // Invalid formats - starting/ending patterns.
    yield 'invalid starting with dot' => ['.invalid', FALSE];
    yield 'invalid starting with hyphen' => ['-invalid', FALSE];
    yield 'invalid ending with .lock' => ['invalid.lock', FALSE];
    yield 'invalid containing ..' => ['invalid..ref', FALSE];
    yield 'invalid trailing slash' => ['feature/', FALSE];
    yield 'invalid consecutive slashes' => ['feature//name', FALSE];
    // Empty and edge cases.
    yield 'invalid empty string' => ['', FALSE];
    yield 'invalid only spaces' => ['   ', FALSE];
  }

}
