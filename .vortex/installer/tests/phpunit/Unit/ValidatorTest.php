<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\Validator;

/**
 * Class InstallerHelpersTest.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\Validator
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class ValidatorTest extends UnitTestBase {

  /**
   * @covers ::containerImage
   * @dataProvider dataProviderContainerImage
   */
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

  /**
   * @dataProvider dataProviderDomain
   * @covers ::domain
   */
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

  /**
   * @dataProvider dataProviderGithubProject
   * @covers ::githubProject
   */
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

  /**
   * @dataProvider dataProviderDirname
   * @covers ::dirname
   */
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

}
