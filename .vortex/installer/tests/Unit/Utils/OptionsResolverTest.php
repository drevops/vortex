<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\OptionsResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Tests for the OptionsResolver class.
 */
#[CoversClass(OptionsResolver::class)]
class OptionsResolverTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_INSTALLER');
    static::envUnsetPrefix('VORTEX_DOWNLOAD');
    static::envUnsetPrefix('VORTEX_DB');
  }

  public function testCheckRequirementsPassesWhenAllPresent(): void {
    $finder = $this->createMock(ExecutableFinder::class);
    $finder->method('find')->willReturn('/usr/bin/mock');

    OptionsResolver::checkRequirements($finder);

    // No exception means success.
    $this->addToAssertionCount(1);
  }

  #[DataProvider('dataProviderCheckRequirementsThrowsOnMissing')]
  public function testCheckRequirementsThrowsOnMissing(string $missing_command): void {
    $finder = $this->createMock(ExecutableFinder::class);
    $finder->method('find')
      ->willReturnCallback(fn(string $name): ?string => $name === $missing_command ? NULL : '/usr/bin/' . $name);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Missing required command: %s.', $missing_command));

    OptionsResolver::checkRequirements($finder);
  }

  public static function dataProviderCheckRequirementsThrowsOnMissing(): array {
    return [
      'missing git' => ['git'],
      'missing tar' => ['tar'],
      'missing composer' => ['composer'],
    ];
  }

  public function testCheckRequirementsStopsAtFirstMissing(): void {
    $finder = $this->createMock(ExecutableFinder::class);
    $finder->method('find')->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Missing required command: git.');

    OptionsResolver::checkRequirements($finder);
  }

  public function testResolveReturnsConfigAndArtifact(): void {
    $options = self::defaultOptions();

    [$config, $artifact] = OptionsResolver::resolve($options);

    $this->assertInstanceOf(Config::class, $config);
    $this->assertInstanceOf(Artifact::class, $artifact);
  }

  #[DataProvider('dataProviderResolveBooleanOptions')]
  public function testResolveBooleanOptions(string $option_key, string $config_key_or_method, bool $value): void {
    $options = self::defaultOptions([$option_key => $value]);

    [$config] = OptionsResolver::resolve($options);

    if (method_exists($config, $config_key_or_method)) {
      $this->assertSame($value, $config->$config_key_or_method());
    }
    else {
      $this->assertSame($value, (bool) $config->get($config_key_or_method));
    }
  }

  public static function dataProviderResolveBooleanOptions(): array {
    return [
      'no-interaction true' => ['no-interaction', 'getNoInteraction', TRUE],
      'no-interaction false' => ['no-interaction', 'getNoInteraction', FALSE],
      'quiet true' => ['quiet', 'isQuiet', TRUE],
      'quiet false' => ['quiet', 'isQuiet', FALSE],
      'build true' => ['build', Config::BUILD_NOW, TRUE],
      'build false' => ['build', Config::BUILD_NOW, FALSE],
      'no-cleanup true' => ['no-cleanup', Config::NO_CLEANUP, TRUE],
      'no-cleanup false' => ['no-cleanup', Config::NO_CLEANUP, FALSE],
    ];
  }

  public function testResolveSetsDestination(): void {
    $dst = self::$sut;
    $options = self::defaultOptions(['destination' => $dst]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertEquals($dst, $config->getDst());
  }

  public function testResolveSetsRoot(): void {
    $root = self::$sut;
    $options = self::defaultOptions(['root' => $root]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertEquals($root, $config->getRoot());
  }

  public function testResolveWithConfigJsonString(): void {
    $options = self::defaultOptions([
      'config' => '{"VORTEX_PROJECT_NAME":"test_project"}',
    ]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertInstanceOf(Config::class, $config);
  }

  public function testResolveWithConfigJsonFile(): void {
    $config_file = self::$sut . '/config.json';
    file_put_contents($config_file, '{"VORTEX_PROJECT_NAME":"file_project"}');

    $options = self::defaultOptions([
      'config' => $config_file,
    ]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertInstanceOf(Config::class, $config);
  }

  public function testResolveWithoutConfig(): void {
    $options = self::defaultOptions(['config' => NULL]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertInstanceOf(Config::class, $config);
  }

  #[DataProvider('dataProviderResolveUri')]
  public function testResolveUri(?string $uri, string $expected_repo, string $expected_ref): void {
    $options = self::defaultOptions(['uri' => $uri]);

    [, $artifact] = OptionsResolver::resolve($options);

    $this->assertEquals($expected_repo, $artifact->getRepo());
    $this->assertEquals($expected_ref, $artifact->getRef());
  }

  public static function dataProviderResolveUri(): array {
    return [
      'default (null uri)' => [
        NULL,
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_STABLE,
      ],
      'repo with branch ref' => [
        'https://github.com/drevops/vortex.git#main',
        'https://github.com/drevops/vortex.git',
        'main',
      ],
      'repo with tag ref' => [
        'https://github.com/drevops/vortex.git#25.11.0',
        'https://github.com/drevops/vortex.git',
        '25.11.0',
      ],
      'repo with stable ref' => [
        RepositoryDownloader::DEFAULT_REPO . '#' . RepositoryDownloader::REF_STABLE,
        RepositoryDownloader::DEFAULT_REPO,
        RepositoryDownloader::REF_STABLE,
      ],
    ];
  }

  public function testResolveSetsRepoAndRefOnConfig(): void {
    $options = self::defaultOptions([
      'uri' => 'https://github.com/drevops/vortex.git#main',
    ]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertEquals('https://github.com/drevops/vortex.git', $config->get(Config::REPO));
    $this->assertEquals('main', $config->get(Config::REF));
  }

  public function testResolveSetsProceedFlag(): void {
    $options = self::defaultOptions();

    [$config] = OptionsResolver::resolve($options);

    $this->assertTrue((bool) $config->get(Config::PROCEED));
  }

  public function testResolveSetsIsVortexProject(): void {
    $options = self::defaultOptions();

    [$config] = OptionsResolver::resolve($options);

    // The SUT directory should not be a Vortex project.
    $this->assertNotNull($config->get(Config::IS_VORTEX_PROJECT));
  }

  public function testResolveDemoModeFromEnv(): void {
    putenv(Config::IS_DEMO . '=1');

    $options = self::defaultOptions();
    [$config] = OptionsResolver::resolve($options);

    $this->assertTrue((bool) $config->get(Config::IS_DEMO));
  }

  public function testResolveDemoDbDownloadSkipFromEnv(): void {
    putenv(Config::IS_DEMO_DB_DOWNLOAD_SKIP . '=1');

    $options = self::defaultOptions();
    [$config] = OptionsResolver::resolve($options);

    $this->assertTrue((bool) $config->get(Config::IS_DEMO_DB_DOWNLOAD_SKIP));
  }

  public function testResolveDestinationPriority(): void {
    // Option takes priority over root.
    $dst = self::$sut;
    $options = self::defaultOptions([
      'destination' => $dst,
      'root' => '/some/other/root',
    ]);

    [$config] = OptionsResolver::resolve($options);

    $this->assertEquals($dst, $config->getDst());
  }

  /**
   * Build a default options array with overrides.
   *
   * @param array<string, mixed> $overrides
   *   Options to override.
   *
   * @return array<string, mixed>
   *   The merged options array.
   */
  protected static function defaultOptions(array $overrides = []): array {
    return $overrides + [
      'destination' => NULL,
      'root' => NULL,
      'no-interaction' => FALSE,
      'config' => NULL,
      'quiet' => FALSE,
      'uri' => NULL,
      'no-cleanup' => FALSE,
      'build' => FALSE,
      'schema' => FALSE,
      'validate' => FALSE,
      'agent-help' => FALSE,
    ];
  }

}
