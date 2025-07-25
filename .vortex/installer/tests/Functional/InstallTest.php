<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployType;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Downloader;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\Tui;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Test the installation with different pre-existing conditions.
 *
 * Prompts are tested in the PromptTest class.
 *
 * Run with `UPDATE_FIXTURES=1` to update all the test fixtures.
 */
#[CoversClass(InstallCommand::class)]
#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(CiProvider::class)]
#[CoversClass(CodeProvider::class)]
#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
#[CoversClass(DependencyUpdatesProvider::class)]
#[CoversClass(DeployType::class)]
#[CoversClass(Domain::class)]
#[CoversClass(HostingProvider::class)]
#[CoversClass(Internal::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
#[CoversClass(MachineName::class)]
#[CoversClass(ModulePrefix::class)]
#[CoversClass(Name::class)]
#[CoversClass(Org::class)]
#[CoversClass(OrgMachineName::class)]
#[CoversClass(PreserveDocsOnboarding::class)]
#[CoversClass(PreserveDocsProject::class)]
#[CoversClass(Profile::class)]
#[CoversClass(ProvisionType::class)]
#[CoversClass(Services::class)]
#[CoversClass(Theme::class)]
#[CoversClass(Timezone::class)]
#[CoversClass(Webroot::class)]
#[CoversClass(PromptManager::class)]
#[CoversClass(Downloader::class)]
#[CoversClass(Config::class)]
#[CoversClass(Git::class)]
#[CoversClass(Tui::class)]
class InstallTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    // Use a two-words name for the sut directory.
    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  public function testHelp(): void {
    static::runNonInteractiveInstall(options: ['help' => NULL]);
    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('php install destination');
  }

  #[DataProvider('dataProviderInstall')]
  #[RunInSeparateProcess]
  public function testInstall(
    ?SerializableClosure $before = NULL,
    ?SerializableClosure $after = NULL,
    array $expected = [],
  ): void {
    static::$fixtures = static::locationsFixtureDir();

    if ($before instanceof SerializableClosure) {
      $before = static::cu($before);
      $before($this);
    }

    $this->runNonInteractiveInstall();

    $expected = empty($expected) ? ['Welcome to the Vortex non-interactive installer'] : $expected;
    $this->assertApplicationOutputContains($expected);

    $baseline = File::dir(static::$fixtures . '/../' . self::BASELINE_DIR);
    static::replaceVersions(static::$sut);
    $this->assertDirectoryEqualsPatchedBaseline(static::$sut, $baseline, static::$fixtures);

    $this->assertCommon();

    if ($after instanceof SerializableClosure) {
      $after = static::cu($after);
      $after($this);
    }
  }

  public static function dataProviderInstall(): array {
    return [
      static::BASELINE_DATASET => [
        NULL,
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],

      'non-interactive' => [
        NULL,
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],

      'names' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Name::id()), 'New hope');
          Env::put(PromptManager::makeEnvName(MachineName::id()), 'the_new_hope');
          Env::put(PromptManager::makeEnvName(Org::id()), 'Jedi Order');
          Env::put(PromptManager::makeEnvName(OrgMachineName::id()), 'the_jedi_order');
          Env::put(PromptManager::makeEnvName(Domain::id()), 'deathstar.com');
          Env::put(PromptManager::makeEnvName(ModulePrefix::id()), 'the_force');
          Env::put(PromptManager::makeEnvName(Theme::id()), 'lightsaber');
        }),
      ],

      'code provider, github' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::GITHUB)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.dist.md');
          $test->assertFileContainsString('Checklist before requesting a review', static::$sut . '/.github/PULL_REQUEST_TEMPLATE.md');
        }),
      ],
      'code provider, other' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::OTHER)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertDirectoryDoesNotExist(static::$sut . '/.github');
        }),
      ],

      'profile, minimal' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), Profile::MINIMAL)),
      ],
      'profile, the_empire' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), 'the_empire')),
      ],

      'theme, absent' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Theme::id()), '');
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString('themes/custom', static::$sut, [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
          'CLAUDE.md',
        ])),
      ],

      'theme, custom' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), 'light_saber')),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString('your_site_theme', static::$sut)),
      ],

      'timezone, gha' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Timezone::id()), 'America/New_York');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          // Timezone should be replaced in .env file.
          $test->assertFileContainsString('TZ=America/New_York', static::$sut . '/.env');
          $test->assertFileNotContainsString('UTC', static::$sut . '/.env');

          // Timezone should be replaced in Renovate config.
          $test->assertFileContainsString('"timezone": "America/New_York"', static::$sut . '/renovate.json');
          $test->assertFileNotContainsString('UTC', static::$sut . '/renovate.json');

          // Timezone should not be replaced in GHA config in code as it should
          // be overridden via UI.
          $test->assertFileNotContainsString('America/New_York', static::$sut . '/.github/workflows/build-test-deploy.yml');

          // Timezone should not be replaced in Docker Compose config.
          $test->assertFileNotContainsString('America/New_York', static::$sut . '/docker-compose.yml');
        }),
      ],
      'timezone, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Timezone::id()), 'America/New_York');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          // Timezone should not be replaced in CircleCI config in code as it
          // should be overridden via UI.
          $test->assertFileContainsString('TZ: UTC', static::$sut . '/.circleci/config.yml');
          $test->assertFileNotContainsString('TZ: America/New_York', static::$sut . '/.circleci/config.yml');
        }),
      ],

      'services, no clamav' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::SOLR, Services::VALKEY]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('clamav')),
      ],
      'services, no valkey' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::SOLR]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains(['valkey', 'redis'])),
      ],
      'services, no solr' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::VALKEY]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('solr')),
      ],
      'services, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains(['valkey', 'redis']);
        }),
      ],

      'hosting, acquia' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::ACQUIA);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
      'hosting, lagoon' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('acquia')),
      ],

      'db download source, url' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::URL)),
      ],
      'db download source, ftp' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::FTP)),
      ],
      'db download source, acquia' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::ACQUIA)),
      ],
      'db download source, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::LAGOON)),
      ],
      'db download source, container_registry' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::CONTAINER_REGISTRY);
          Env::put(PromptManager::makeEnvName(DatabaseImage::id()), 'the_empire/star_wars:latest');
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'deploy type, artifact' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::ARTIFACT], ',', TRUE))),
      ],
      'deploy type, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::LAGOON], ',', TRUE))),
      ],
      'deploy type, container_image' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::CONTAINER_IMAGE], ',', TRUE))),
      ],
      'deploy type, webhook' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK], ',', TRUE))),
      ],
      'deploy type, all, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]))),
      ],
      'deploy type, all, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
      'deploy type, none, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), ',')),
      ],
      'deploy type, none, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), ',');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],

      'provision, database' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::DATABASE)),
      ],
      'provision, database, lagoon' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::DATABASE);
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
      'provision, profile' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::PROFILE);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'ciprovider, gha' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
      'ciprovider, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'deps updates provider, ci, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI)),
      ],
      'deps updates provider, ci, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI);
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
      'deps updates provider, app' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_APP)),
      ],
      'deps updates provider, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::NONE)),
      ],

      'assign author PR, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::TRUE)),
      ],
      'assign author PR, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::FALSE)),
      ],

      'label merge conflicts PR, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::TRUE)),
      ],
      'label merge conflicts PR, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::FALSE)),
      ],

      'preserve docs project, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::TRUE)),
      ],
      'preserve docs project, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::FALSE)),
      ],

      'preserve docs onboarding, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::TRUE)),
      ],
      'preserve docs onboarding, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::FALSE)),
      ],

      'ai instructions, claude' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE)),
      ],
      'ai instructions, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::NONE)),
      ],
    ];
  }

  protected function assertCommon(): void {
    $this->assertDirectoryEqualsDirectory(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
  }

  protected static function defaultAnswers(): array {
    return [
      'namespace' => 'YodasHut',
      'project' => 'force-crystal',
      'author' => 'Luke Skywalker',
      'use_php' => static::TUI_DEFAULT,
      'use_php_command' => static::TUI_DEFAULT,
      'php_command_name' => static::TUI_DEFAULT,
      'use_php_command_build' => static::TUI_DEFAULT,
      'use_php_script' => static::TUI_DEFAULT,
      'use_nodejs' => static::TUI_DEFAULT,
      'use_shell' => static::TUI_DEFAULT,
      'use_release_drafter' => static::TUI_DEFAULT,
      'use_pr_autoassign' => static::TUI_DEFAULT,
      'use_funding' => static::TUI_DEFAULT,
      'use_pr_template' => static::TUI_DEFAULT,
      'use_renovate' => static::TUI_DEFAULT,
      'use_docs' => static::TUI_DEFAULT,
      'remove_self' => static::TUI_DEFAULT,
    ];
  }

}
