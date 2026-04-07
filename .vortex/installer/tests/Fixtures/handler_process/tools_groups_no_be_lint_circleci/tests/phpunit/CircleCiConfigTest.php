<?php

declare(strict_types=1);

use Drupal\Component\Serialization\Yaml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Class CircleCiConfigTest.
 *
 * Tests for CircleCI configurations.
 *
 *
 */
#[Group('ci')]
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
   */
  #[DataProvider('dataProviderDeployBranchRegex')]
  public function testDeployBranchRegex(string $branch, bool $expected = TRUE): void {
    $pattern = $this->config['workflows']['commit']['jobs'][3]['deploy']['filters']['branches']['only'];
    $result = preg_match($pattern, $branch);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testDeployBranchRegex().
   */
  public static function dataProviderDeployBranchRegex(): \Iterator {
    // Positive branches.
    yield ['production'];
    yield ['main'];
    yield ['master'];
    yield ['develop'];

    yield ['ci'];
    yield ['cisomething'];

    yield ['release/__VERSION__'];
    yield ['release/__VERSION__'];
    yield ['hotfix/__VERSION__'];
    yield ['hotfix/__VERSION__'];

    yield ['release/2023-04-17'];
    yield ['release/2023-04-17.1'];
    yield ['hotfix/2023-04-17'];
    yield ['hotfix/2023-04-17.1'];

    yield ['feature/description'];
    yield ['feature/Description'];
    yield ['feature/Description-With-Hyphens'];
    yield ['feature/Description-With_Underscores'];
    yield ['feature/123-description'];
    yield ['feature/123-Description'];
    yield ['feature/UNDERSCORES_UNDERSCORES'];
    yield ['feature/123-Description-With_UNDERSCORES'];
    yield ['feature/1.x'];
    yield ['feature/0.x'];
    yield ['feature/0.1.x'];
    yield ['feature/__VERSION__.x'];
    yield ['feature/1.x-description'];
    yield ['feature/0.x-description'];
    yield ['feature/0.1.x-description'];
    yield ['feature/__VERSION__.x-description'];

    yield ['bugfix/description'];
    yield ['bugfix/Description'];
    yield ['bugfix/Description-With-Hyphens'];
    yield ['bugfix/Description-With_Underscores'];
    yield ['bugfix/123-description'];
    yield ['bugfix/123-Description'];
    yield ['bugfix/UNDERSCORES_UNDERSCORES'];
    yield ['bugfix/123-Description-With_UNDERSCORES'];
    yield ['bugfix/1.x'];
    yield ['bugfix/0.x'];
    yield ['bugfix/0.1.x'];
    yield ['bugfix/__VERSION__.x'];
    yield ['bugfix/1.x-description'];
    yield ['bugfix/0.x-description'];
    yield ['bugfix/0.1.x-description'];
    yield ['bugfix/__VERSION__.x-description'];

    yield ['project/description'];
    yield ['project/Description'];
    yield ['project/Description-With-Hyphens'];
    yield ['project/123-description'];
    yield ['project/123-Description'];
    yield ['project/1.x'];
    yield ['project/0.x'];
    yield ['project/0.1.x'];
    yield ['project/__VERSION__.x'];
    yield ['project/1.x-description'];
    yield ['project/0.x-description'];
    yield ['project/0.1.x-description'];
    yield ['project/__VERSION__.x-description'];

    // Negative branches.
    yield ['something', FALSE];
    yield ['premain', FALSE];
    yield ['premaster', FALSE];
    yield ['predevelop', FALSE];
    yield ['mainpost', FALSE];
    yield ['masterpost', FALSE];
    yield ['developpost', FALSE];
    yield ['premainpost', FALSE];
    yield ['premasterpost', FALSE];
    yield ['predeveloppost', FALSE];

    yield ['preci', FALSE];
    yield ['precipost', FALSE];

    yield ['deps/something', FALSE];
    yield ['deps', FALSE];
    yield ['predeps', FALSE];
    yield ['depspost', FALSE];
    yield ['predepspost', FALSE];

    yield ['feature', FALSE];
    yield ['release', FALSE];
    yield ['hotfix', FALSE];
    yield ['prefeature', FALSE];
    yield ['prerelease', FALSE];
    yield ['prehotfix', FALSE];
    yield ['featurepost', FALSE];
    yield ['releasepost', FALSE];
    yield ['hotfixpost', FALSE];
    yield ['prefeaturepost', FALSE];
    yield ['prereleasepost', FALSE];
    yield ['prehotfixpost', FALSE];

    yield ['release/123', FALSE];
    yield ['release/123.456', FALSE];
    yield ['hotfix/123', FALSE];
    yield ['hotfix/123.456', FALSE];

    yield ['release/202-04-17', FALSE];
    yield ['release/2023-4-17', FALSE];
    yield ['release/2023-04-1', FALSE];
    yield ['release/pre2023-04-17', FALSE];
    yield ['release/2023-04-17post', FALSE];
    yield ['release/pre2023-04-17post', FALSE];

    yield ['hotfix/202-04-17', FALSE];
    yield ['hotfix/2023-4-17', FALSE];
    yield ['hotfix/2023-04-1', FALSE];
    yield ['hotfix/pre2023-04-17', FALSE];
    yield ['hotfix/2023-04-17post', FALSE];
    yield ['hotfix/pre2023-04-17post', FALSE];

    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];
    yield ['release/__VERSION__', FALSE];

    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];
    yield ['hotfix/__VERSION__', FALSE];

    yield ['prefeature/something', FALSE];
    yield ['prefbugfix/something', FALSE];
    yield ['prerelease/something', FALSE];
    yield ['prehotfix/something', FALSE];
    yield ['featurepost/something', FALSE];
    yield ['bugfixpost/something', FALSE];
    yield ['releasepost/something', FALSE];
    yield ['hotfixpost/something', FALSE];
    yield ['prefeaturepost/something', FALSE];
    yield ['prebugfixpost/something', FALSE];
    yield ['prereleasepost/something', FALSE];
    yield ['prehotfixpost/something', FALSE];
    yield ['preproject/something', FALSE];
    yield ['projectpost/something', FALSE];
  }

  /**
   * Tests for deploy tag regex.
   *
   * @see https://semver.org/
   */
  #[DataProvider('dataProviderDeployTagRegex')]
  public function testDeployTagRegex(string $branch, bool $expected = TRUE): void {
    $pattern = $this->config['workflows']['commit']['jobs'][4]['deploy-tags']['filters']['tags']['only'];
    $result = preg_match($pattern, $branch);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testDeployTagRegex().
   */
  public static function dataProviderDeployTagRegex(): \Iterator {
    // Positive tags.
    yield ['__VERSION__'];
    yield ['__VERSION__'];
    yield ['2023-04-17'];
    yield ['2023-04-17.123'];

    // Negative tags.
    yield ['123', FALSE];
    yield ['123.456', FALSE];
    yield ['__VERSION__', FALSE];
    yield ['__VERSION__', FALSE];
    yield ['__VERSION__', FALSE];
    yield ['__VERSION__', FALSE];
    yield ['__VERSION__', FALSE];

    yield ['202-04-17', FALSE];
    yield ['2023-0-17', FALSE];
    yield ['2023-04-1', FALSE];
    yield ['pre2023-04-17', FALSE];
    yield ['2023-04-17post', FALSE];
    yield ['pre2023-04-17post', FALSE];
    yield ['2023-04-17.123.', FALSE];
    yield ['2023-04-17.pre123', FALSE];
    yield ['2023-04-17.pre123post', FALSE];
    yield ['2023-04-17.123post', FALSE];
  }

}
