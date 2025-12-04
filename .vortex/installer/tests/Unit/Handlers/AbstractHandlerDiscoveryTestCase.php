<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Traits\TuiTrait;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Abstract base class for PromptManager handler discovery tests.
 *
 * Provides common test logic for all PromptManager test scenarios.
 */
abstract class AbstractHandlerDiscoveryTestCase extends UnitTestCase {

  use UpstreamTuiTrait;
  use TuiTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::tuiSetUp();

    static::$sut = File::mkdir(static::$sut . DIRECTORY_SEPARATOR . 'myproject');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTeardown();

    parent::tearDown();
  }

  #[DataProvider('dataProviderRunPrompts')]
  public function testRunPrompts(
    array $answers,
    array|string $expected,
    ?callable $before = NULL,
    ?callable $after = NULL,
  ): void {
    // Re-use the expected value as an exception message if it is a string.
    $exception = is_string($expected) ? $expected : NULL;
    if ($exception) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception);
    }

    $config = new Config(static::$sut);

    if ($before !== NULL) {
      $before($this, $config);
    }

    $answers = array_replace(static::defaultTuiAnswers(), $answers);
    $keystrokes = static::tuiKeystrokes($answers, 40);
    Prompt::fake($keystrokes);

    $pm = new PromptManager($config);
    $pm->runPrompts();

    if (!$exception) {
      $actual = $pm->getResponses();
      $this->assertEquals($expected, $actual, (string) $this->dataName());
    }

    if ($after !== NULL) {
      $after($this, $config);
    }
  }

  /**
   * Abstract data provider that must be implemented by handler test classes.
   */
  abstract public static function dataProviderRunPrompts(): array;

  /**
   * Get expected defaults for a new project.
   */
  protected static function getExpectedDefaults(): array {
    return [
      Name::id() => 'myproject',
      MachineName::id() => 'myproject',
      Org::id() => 'myproject Org',
      OrgMachineName::id() => 'myproject_org',
      Domain::id() => 'myproject.com',
      Starter::id() => Starter::LOAD_DATABASE_DEMO,
      Profile::id() => Profile::STANDARD,
      Modules::id() => array_keys(Modules::getAvailableModules()),
      ModulePrefix::id() => 'mypr',
      Theme::id() => 'myproject',
      CodeProvider::id() => CodeProvider::GITHUB,
      VersionScheme::id() => VersionScheme::CALVER,
      Timezone::id() => 'UTC',
      Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR],
      Tools::id() => [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR, Tools::PHPUNIT, Tools::BEHAT],
      HostingProvider::id() => HostingProvider::NONE,
      HostingProjectName::id() => NULL,
      Webroot::id() => Webroot::WEB,
      DeployTypes::id() => [DeployTypes::WEBHOOK],
      ProvisionType::id() => ProvisionType::DATABASE,
      DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
      DatabaseImage::id() => NULL,
      CiProvider::id() => CiProvider::GITHUB_ACTIONS,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP,
      AssignAuthorPr::id() => TRUE,
      LabelMergeConflictsPr::id() => TRUE,
      PreserveDocsProject::id() => TRUE,
      AiCodeInstructions::id() => AiCodeInstructions::CLAUDE,
      NotificationChannels::id() => [NotificationChannels::EMAIL],
    ];
  }

  /**
   * Get expected values for a pre-installed project.
   */
  protected static function getExpectedInstalled(): array {
    $overrides = [
      Tools::id() => [],
      CiProvider::id() => CiProvider::NONE,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE,
      AssignAuthorPr::id() => FALSE,
      LabelMergeConflictsPr::id() => FALSE,
      PreserveDocsProject::id() => FALSE,
      AiCodeInstructions::id() => AiCodeInstructions::NONE,
    ];
    return $overrides + static::getExpectedDefaults();
  }

  /**
   * Get expected values for responses discovered from the env.
   */
  protected static function getExpectedDiscovered(): array {
    $overrides = [
      Name::id() => 'Discovered project',
      MachineName::id() => 'discovered_project',
      Org::id() => 'Discovered project Org',
      OrgMachineName::id() => 'discovered_project_org',
      Domain::id() => 'discovered-project.com',
      ModulePrefix::id() => 'dp',
      Theme::id() => 'discovered_project',
    ];
    return $overrides + static::getExpectedDefaults();
  }

  /**
   * The default answers for TUI prompts used in tests.
   *
   * @return array<string, string>
   *   An associative array of prompt IDs and their default values.
   */
  public static function defaultTuiAnswers(): array {
    return [
      Name::id() => static::TUI_DEFAULT,
      MachineName::id() => static::TUI_DEFAULT,
      Org::id() => static::TUI_DEFAULT,
      OrgMachineName::id() => static::TUI_DEFAULT,
      Domain::id() => static::TUI_DEFAULT,
      Starter::id() => static::TUI_DEFAULT,
      Profile::id() => static::TUI_DEFAULT,
      Modules::id() => static::TUI_DEFAULT,
      ModulePrefix::id() => static::TUI_DEFAULT,
      Theme::id() => static::TUI_DEFAULT,
      ThemeCustom::id() => static::TUI_DEFAULT,
      CodeProvider::id() => static::TUI_DEFAULT,
      VersionScheme::id() => static::TUI_DEFAULT,
      Timezone::id() => static::TUI_DEFAULT,
      Services::id() => static::TUI_DEFAULT,
      Tools::id() => static::TUI_DEFAULT,
      HostingProvider::id() => static::TUI_DEFAULT,
      Webroot::id() => static::TUI_DEFAULT,
      DeployTypes::id() => static::TUI_DEFAULT,
      ProvisionType::id() => static::TUI_DEFAULT,
      DatabaseDownloadSource::id() => static::TUI_DEFAULT,
      DatabaseImage::id() => static::TUI_SKIP,
      CiProvider::id() => static::TUI_DEFAULT,
      DependencyUpdatesProvider::id() => static::TUI_DEFAULT,
      AssignAuthorPr::id() => static::TUI_DEFAULT,
      LabelMergeConflictsPr::id() => static::TUI_DEFAULT,
      PreserveDocsProject::id() => static::TUI_DEFAULT,
      AiCodeInstructions::id() => static::TUI_DEFAULT,
      NotificationChannels::id() => static::TUI_DEFAULT,
    ];
  }

  protected function stubComposerJsonValue(string $name, mixed $value): string {
    $composer_json = static::$sut . DIRECTORY_SEPARATOR . 'composer.json';
    file_put_contents($composer_json, json_encode([$name => $value], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $composer_json;
  }

  protected function stubComposerJsonDependencies(array $dependencies, bool $is_dev = FALSE): string {
    $composer_json = static::$sut . DIRECTORY_SEPARATOR . 'composer.json';
    $section = $is_dev ? 'require-dev' : 'require';

    $data = [];
    if (file_exists($composer_json)) {
      $contents = file_get_contents($composer_json);
      $existing = $contents !== FALSE ? json_decode($contents, TRUE) : NULL;
      if ($existing) {
        $data = $existing;
      }
    }

    if (!isset($data[$section])) {
      $data[$section] = [];
    }

    $data[$section] = array_merge($data[$section], $dependencies);

    file_put_contents($composer_json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $composer_json;
  }

  protected function stubDotenvValue(string $name, mixed $value, string $filename = '.env'): string {
    $dotenv = static::$sut . DIRECTORY_SEPARATOR . $filename;

    file_put_contents($dotenv, sprintf('%s=%s', $name, $value) . PHP_EOL, FILE_APPEND);

    return $dotenv;
  }

  protected function stubVortexProject(Config $config): void {
    // Add a README.md file with a Vortex badge.
    $readme = static::$sut . DIRECTORY_SEPARATOR . 'README.md';
    $repo_url = str_replace('.git', '', RepositoryDownloader::DEFAULT_REPO);
    file_put_contents($readme, sprintf('[![Vortex](https://img.shields.io/badge/Vortex-1.2.3-65ACBC.svg)](%s/tree/1.2.3)', $repo_url) . PHP_EOL, FILE_APPEND);

    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
  }

  protected function stubTheme(string $dir): void {
    File::dump($dir . '/scss/_variables.scss');
    File::dump($dir . '/Gruntfile.js');
    File::dump($dir . '/package.json', (string) json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
