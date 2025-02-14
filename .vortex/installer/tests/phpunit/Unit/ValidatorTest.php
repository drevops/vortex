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

}
