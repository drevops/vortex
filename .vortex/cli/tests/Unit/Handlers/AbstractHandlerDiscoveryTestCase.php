<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexCli\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeProvider;
use DrevOps\VortexCli\Prompts\Handlers\CustomModules;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexCli\Prompts\Handlers\DeployTypes;
use DrevOps\VortexCli\Prompts\Handlers\Domain;
use DrevOps\VortexCli\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexCli\Prompts\Handlers\Gitleaks;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexCli\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexCli\Prompts\Handlers\MachineName;
use DrevOps\VortexCli\Prompts\Handlers\Migration;
use DrevOps\VortexCli\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\MigrationImage;
use DrevOps\VortexCli\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexCli\Prompts\Handlers\Modules;
use DrevOps\VortexCli\Prompts\Handlers\Name;
use DrevOps\VortexCli\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexCli\Prompts\Handlers\Org;
use DrevOps\VortexCli\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexCli\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexCli\Prompts\Handlers\Profile;
use DrevOps\VortexCli\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexCli\Prompts\Handlers\ProvisionType;
use DrevOps\VortexCli\Prompts\Handlers\Services;
use DrevOps\VortexCli\Prompts\Handlers\Starter;
use DrevOps\VortexCli\Prompts\Handlers\Theme;
use DrevOps\VortexCli\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexCli\Prompts\Handlers\Timezone;
use DrevOps\VortexCli\Prompts\Handlers\Tools;
use DrevOps\VortexCli\Prompts\Handlers\VersionScheme;
use DrevOps\VortexCli\Prompts\Handlers\VisualRegression;
use DrevOps\VortexCli\Prompts\Handlers\Webroot;
use DrevOps\PhpTui\Tui as Engine;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use DrevOps\VortexCli\Tests\Traits\TuiTrait;
use DrevOps\VortexCli\Tests\Unit\UnitTestCase;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Abstract base class for handler discovery tests.
 *
 * Provides common test logic for all discovery scenarios.
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

    $supplied = static::suppliedAnswers(array_replace(static::defaultTuiAnswers(), $answers), $expected);

    $tui = new Engine(VortexForm::create($config), ['DrevOps\\VortexCli\\Prompts\\Handlers']);
    // Discovery is what these scenarios exercise, so it always runs; a
    // destination with nothing to find simply discovers nothing.
    $collected = $tui->collect((string) json_encode($supplied), (string) $config->getDst(), TRUE, '1.0.0');

    // The questions never asked are part of the answer set every handler sees,
    // so the assertion is against that set rather than the collected subset.
    $actual = (new Processor())->responses($collected, $tui->registry(), $config, VortexForm::WEIGHTS);

    if (!$exception) {
      $this->assertEquals($expected, $actual, (string) $this->dataName());
    }

    if ($after !== NULL) {
      $after($this, $config);
    }
  }

  /**
   * The answers to supply, from a data set written as keystrokes.
   *
   * A scenario says what it answers by scripting the keys a person would press
   * at a linear prompt. Collection now takes values, so a control sequence -
   * "accept the default", "move down then accept" - is read back as the value
   * the scenario already declares it settles on, and only literal typing is
   * carried through as-is.
   *
   * @param array<string,mixed> $answers
   *   The data set's answers, keyed by question id.
   * @param array<string,mixed>|string $expected
   *   The expected answer set, or an exception message for a failing scenario.
   *
   * @return array<string,mixed>
   *   The answers to supply to collection.
   */
  protected static function suppliedAnswers(array $answers, array|string $expected): array {
    $supplied = [];

    foreach ($answers as $id => $entry) {
      // Both sentinels mean the scenario answers nothing here: one accepts
      // whatever the default is, the other never reaches the question at all.
      if ($entry === static::TUI_DEFAULT || $entry === static::TUI_SKIP) {
        continue;
      }

      // A control sequence navigates rather than types, so the value it lands
      // on is the one the scenario expects for that question - except in a
      // scenario that expects a rejection, where what was typed alongside the
      // navigation is the value being rejected.
      if (is_string($entry) && preg_match('/[\x00-\x1F\x7F]/', $entry) === 1) {
        // A cursor key is an escape sequence, so the bracket and letter go
        // with the escape byte rather than surviving as typed text.
        $typed = (string) preg_replace(['/\x1B\[[0-9;]*[A-Za-z]/', '/[\x00-\x1F\x7F]/'], '', $entry);

        if (is_string($expected)) {
          if ($typed !== '') {
            $supplied[$id] = $typed;
          }

          continue;
        }

        if (array_key_exists($id, $expected)) {
          $supplied[$id] = $expected[$id];
        }

        continue;
      }

      $supplied[$id] = $entry;
    }

    return $supplied;
  }

  /**
   * Abstract data provider that must be implemented by handler test classes.
   */
  abstract public static function dataProviderRunPrompts(): \Iterator;

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
      ProfileCustom::id() => NULL,
      Modules::id() => array_keys(Modules::getAvailableModules()),
      ModulePrefix::id() => 'mypr',
      CustomModules::id() => [CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO],
      Theme::id() => Theme::CUSTOM,
      ThemeCustom::id() => 'myproject',
      FrontendBuild::id() => TRUE,
      CodeProvider::id() => CodeProvider::GITHUB,
      VersionScheme::id() => VersionScheme::CALVER,
      Timezone::id() => 'UTC',
      Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR],
      Tools::id() => [Tools::BEHAT, Tools::DCLINT, Tools::ESLINT, Tools::HADOLINT, Tools::JEST, Tools::PHPCS, Tools::PHPSTAN, Tools::PHPUNIT, Tools::RECTOR, Tools::STYLELINT, Tools::TWIG_CS_FIXER],
      HostingProvider::id() => HostingProvider::NONE,
      HostingProjectName::id() => NULL,
      Webroot::id() => Webroot::WEB,
      DeployTypes::id() => [DeployTypes::WEBHOOK],
      ProvisionType::id() => ProvisionType::DATABASE,
      DatabaseFetchSource::id() => DatabaseFetchSource::URL,
      DatabaseImage::id() => NULL,
      Migration::id() => FALSE,
      MigrationFetchSource::id() => NULL,
      MigrationImage::id() => NULL,
      CiProvider::id() => CiProvider::GITHUB_ACTIONS,
      VisualRegression::id() => FALSE,
      Gitleaks::id() => TRUE,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP,
      CodeCoverageProvider::id() => CodeCoverageProvider::NONE,
      AssignAuthorPr::id() => TRUE,
      LabelMergeConflictsPr::id() => TRUE,
      PreserveDocsProject::id() => TRUE,
      AiCodeInstructions::id() => TRUE,
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
      CodeCoverageProvider::id() => CodeCoverageProvider::NONE,
      AssignAuthorPr::id() => FALSE,
      LabelMergeConflictsPr::id() => FALSE,
      PreserveDocsProject::id() => FALSE,
      AiCodeInstructions::id() => FALSE,
      Gitleaks::id() => FALSE,
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
      Theme::id() => Theme::CUSTOM,
      ThemeCustom::id() => 'discovered_project',
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
      CustomModules::id() => static::TUI_DEFAULT,
      Theme::id() => static::TUI_DEFAULT,
      ThemeCustom::id() => static::TUI_DEFAULT,
      FrontendBuild::id() => static::TUI_DEFAULT,
      CodeProvider::id() => static::TUI_DEFAULT,
      VersionScheme::id() => static::TUI_DEFAULT,
      Timezone::id() => static::TUI_DEFAULT,
      Services::id() => static::TUI_DEFAULT,
      Tools::id() => static::TUI_DEFAULT,
      HostingProvider::id() => static::TUI_DEFAULT,
      Webroot::id() => static::TUI_DEFAULT,
      DeployTypes::id() => static::TUI_DEFAULT,
      ProvisionType::id() => static::TUI_DEFAULT,
      DatabaseFetchSource::id() => static::TUI_DEFAULT,
      DatabaseImage::id() => static::TUI_SKIP,
      Migration::id() => static::TUI_DEFAULT,
      MigrationFetchSource::id() => static::TUI_SKIP,
      MigrationImage::id() => static::TUI_SKIP,
      CiProvider::id() => static::TUI_DEFAULT,
      VisualRegression::id() => static::TUI_DEFAULT,
      Gitleaks::id() => static::TUI_DEFAULT,
      DependencyUpdatesProvider::id() => static::TUI_DEFAULT,
      CodeCoverageProvider::id() => static::TUI_DEFAULT,
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
    File::dump($dir . '/package.json', (string) json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
