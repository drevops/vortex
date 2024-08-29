<?php

declare(strict_types=1);

use Drupal\Component\Serialization\Yaml;
use PHPUnit\Framework\TestCase;

/**
 * Class CircleCiConfigTest.
 *
 * Tests for CircleCI configurations.
 *
 * @group ci
 *
 * @SuppressWarnings(PHPMD)
 *
 * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
 * phpcs:disable Drupal.NamingConventions.ValidGlobal.GlobalUnderScore
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
 */
class CircleCiConfigTest extends TestCase {

  /**
   * CircleCI loaded config.
   *
   * @var mixed
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $file = file_get_contents(__DIR__ . '/../../.circleci/config.yml');
    if (!$file) {
      throw new \RuntimeException('Unable to read CircleCI config file.');
    }
    $this->config = Yaml::decode($file);
  }

  /**
   * Tests for deploy branch regex.
   *
   * @see https://semver.org/
   *
   * @dataProvider dataProviderDeployBranchRegex
   */
  public function testDeployBranchRegex(string $branch, bool $expected = TRUE): void {
    $this->assertEquals($expected, preg_match($this->config['workflows']['commit']['jobs'][2]['deploy']['filters']['branches']['only'], $branch));
  }

  /**
   * Data provider for testDeployBranchRegex().
   */
  public static function dataProviderDeployBranchRegex(): array {
    return [
      // Positive branches.
      ['production'],
      ['main'],
      ['master'],
      ['develop'],

      ['ci'],
      ['cisomething'],

      ['deps/something'],

      ['release/123.456.789'],
      ['release/123.456.789-rc.123'],
      ['hotfix/123.456.789'],
      ['hotfix/123.456.789-rc.123'],

      ['release/2023-04-17'],
      ['release/2023-04-17.1'],
      ['hotfix/2023-04-17'],
      ['hotfix/2023-04-17.1'],

      ['feature/description'],
      ['feature/Description'],
      ['feature/Description-With-Hyphens'],
      ['feature/Description-With_Underscores'],
      ['feature/123-description'],
      ['feature/123-Description'],
      ['feature/UNDERSCORES_UNDERSCORES'],
      ['feature/123-Description-With_UNDERSCORES'],
      ['feature/1.x'],
      ['feature/0.x'],
      ['feature/0.1.x'],
      ['feature/0.1.2.x'],
      ['feature/1.x-description'],
      ['feature/0.x-description'],
      ['feature/0.1.x-description'],
      ['feature/0.1.2.x-description'],

      ['bugfix/description'],
      ['bugfix/Description'],
      ['bugfix/Description-With-Hyphens'],
      ['bugfix/Description-With_Underscores'],
      ['bugfix/123-description'],
      ['bugfix/123-Description'],
      ['bugfix/UNDERSCORES_UNDERSCORES'],
      ['bugfix/123-Description-With_UNDERSCORES'],
      ['bugfix/1.x'],
      ['bugfix/0.x'],
      ['bugfix/0.1.x'],
      ['bugfix/0.1.2.x'],
      ['bugfix/1.x-description'],
      ['bugfix/0.x-description'],
      ['bugfix/0.1.x-description'],
      ['bugfix/0.1.2.x-description'],

      ['project/description'],
      ['project/Description'],
      ['project/Description-With-Hyphens'],
      ['project/123-description'],
      ['project/123-Description'],
      ['project/1.x'],
      ['project/0.x'],
      ['project/0.1.x'],
      ['project/0.1.2.x'],
      ['project/1.x-description'],
      ['project/0.x-description'],
      ['project/0.1.x-description'],
      ['project/0.1.2.x-description'],

      // Negative branches.
      ['something', FALSE],
      ['premain', FALSE],
      ['premaster', FALSE],
      ['predevelop', FALSE],
      ['mainpost', FALSE],
      ['masterpost', FALSE],
      ['developpost', FALSE],
      ['premainpost', FALSE],
      ['premasterpost', FALSE],
      ['predeveloppost', FALSE],

      ['preci', FALSE],
      ['precipost', FALSE],

      ['deps', FALSE],
      ['predeps', FALSE],
      ['depspost', FALSE],
      ['predepspost', FALSE],

      ['feature', FALSE],
      ['release', FALSE],
      ['hotfix', FALSE],
      ['prefeature', FALSE],
      ['prerelease', FALSE],
      ['prehotfix', FALSE],
      ['featurepost', FALSE],
      ['releasepost', FALSE],
      ['hotfixpost', FALSE],
      ['prefeaturepost', FALSE],
      ['prereleasepost', FALSE],
      ['prehotfixpost', FALSE],

      ['release/123', FALSE],
      ['release/123.456', FALSE],
      ['hotfix/123', FALSE],
      ['hotfix/123.456', FALSE],

      ['release/202-04-17', FALSE],
      ['release/2023-4-17', FALSE],
      ['release/2023-04-1', FALSE],
      ['release/pre2023-04-17', FALSE],
      ['release/2023-04-17post', FALSE],
      ['release/pre2023-04-17post', FALSE],

      ['hotfix/202-04-17', FALSE],
      ['hotfix/2023-4-17', FALSE],
      ['hotfix/2023-04-1', FALSE],
      ['hotfix/pre2023-04-17', FALSE],
      ['hotfix/2023-04-17post', FALSE],
      ['hotfix/pre2023-04-17post', FALSE],

      ['release/123.456.789-something', FALSE],
      ['release/123.456.789-rc', FALSE],
      ['release/123.456.789-rc123', FALSE],
      ['release/123.456.789-rc-123', FALSE],
      ['release/123.456.789-prerc123', FALSE],
      ['release/123.456.789-rcpost123', FALSE],
      ['release/123.456.789-prercpost123', FALSE],
      ['release/123.456.789-rc123something', FALSE],
      ['release/123.456.789-rc.123something', FALSE],
      ['release/123.456.789-rc.123-something', FALSE],

      ['hotfix/123.456.789-something', FALSE],
      ['hotfix/123.456.789-rc', FALSE],
      ['hotfix/123.456.789-rc123', FALSE],
      ['hotfix/123.456.789-rc-123', FALSE],
      ['hotfix/123.456.789-prerc123', FALSE],
      ['hotfix/123.456.789-rcpost123', FALSE],
      ['hotfix/123.456.789-prercpost123', FALSE],
      ['hotfix/123.456.789-rc123something', FALSE],
      ['hotfix/123.456.789-rc.123something', FALSE],
      ['hotfix/123.456.789-rc.123-something', FALSE],

      ['prefeature/something', FALSE],
      ['prefbugfix/something', FALSE],
      ['prerelease/something', FALSE],
      ['prehotfix/something', FALSE],
      ['featurepost/something', FALSE],
      ['bugfixpost/something', FALSE],
      ['releasepost/something', FALSE],
      ['hotfixpost/something', FALSE],
      ['prefeaturepost/something', FALSE],
      ['prebugfixpost/something', FALSE],
      ['prereleasepost/something', FALSE],
      ['prehotfixpost/something', FALSE],
      ['preproject/something', FALSE],
      ['projectpost/something', FALSE],
    ];
  }

  /**
   * Tests for deploy tag regex.
   *
   * @see https://semver.org/
   *
   * @dataProvider dataProviderDeployTagRegex
   */
  public function testDeployTagRegex(string $branch, bool $expected = TRUE): void {
    $this->assertEquals($expected, preg_match($this->config['workflows']['commit']['jobs'][3]['deploy-tags']['filters']['tags']['only'], $branch));
  }

  /**
   * Data provider for testDeployTagRegex().
   */
  public static function dataProviderDeployTagRegex(): array {
    return [
      // Positive tags.
      ['1.2.3'],
      ['1.2.3-rc.123'],
      ['2023-04-17'],
      ['2023-04-17.123'],

      // Negative tags.
      ['123', FALSE],
      ['123.456', FALSE],
      ['1.2.3-rc123', FALSE],
      ['1.2.3-rc.123post', FALSE],
      ['1.2.3-prerc.123', FALSE],
      ['1.2.3-rcpost.123', FALSE],
      ['1.2.3-prercpost.123', FALSE],

      ['202-04-17', FALSE],
      ['2023-0-17', FALSE],
      ['2023-04-1', FALSE],
      ['pre2023-04-17', FALSE],
      ['2023-04-17post', FALSE],
      ['pre2023-04-17post', FALSE],
      ['2023-04-17.123.', FALSE],
      ['2023-04-17.pre123', FALSE],
      ['2023-04-17.pre123post', FALSE],
      ['2023-04-17.123post', FALSE],
    ];
  }

}
