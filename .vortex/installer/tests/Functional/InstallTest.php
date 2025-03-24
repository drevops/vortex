<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

use DrevOps\Installer\Command\InstallCommand;
use DrevOps\Installer\Prompts\Handlers\AssignAuthorPr;
use DrevOps\Installer\Prompts\Handlers\CiProvider;
use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\Installer\Prompts\Handlers\DatabaseImage;
use DrevOps\Installer\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\Installer\Prompts\Handlers\DeployType;
use DrevOps\Installer\Prompts\Handlers\Domain;
use DrevOps\Installer\Prompts\Handlers\GithubRepo;
use DrevOps\Installer\Prompts\Handlers\GithubToken;
use DrevOps\Installer\Prompts\Handlers\HostingProvider;
use DrevOps\Installer\Prompts\Handlers\Internal;
use DrevOps\Installer\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\Installer\Prompts\Handlers\MachineName;
use DrevOps\Installer\Prompts\Handlers\ModulePrefix;
use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\Handlers\Org;
use DrevOps\Installer\Prompts\Handlers\OrgMachineName;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsProject;
use DrevOps\Installer\Prompts\Handlers\Profile;
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Services;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\ThemeRunner;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Downloader;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\Git;
use DrevOps\Installer\Utils\Tui;
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
#[CoversClass(GithubRepo::class)]
#[CoversClass(GithubToken::class)]
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
#[CoversClass(ThemeRunner::class)]
#[CoversClass(Webroot::class)]
#[CoversClass(PromptManager::class)]
#[CoversClass(Downloader::class)]
#[CoversClass(Config::class)]
#[CoversClass(Git::class)]
#[CoversClass(Tui::class)]
class InstallTest extends FunctionalTestBase {

  public function testHelp(): void {
    static::runNonInteractiveInstall(options: ['help' => NULL]);
    $this->assertApplicationSuccessOutputContains('php install destination');
  }

  #[DataProvider('dataProviderInstall')]
  #[RunInSeparateProcess]
  public function testInstall(?callable $before_callback, ?callable $after_callback = NULL, ?array $answers = NULL, ?array $expected_output = NULL): void {
    static::$fixtures = static::locationsFixtureDir();

    if ($before_callback !== NULL) {
      $before_callback = static::fnu($before_callback);
      $before_callback($this);
    }

    if (!is_null($answers)) {
      self::runInteractiveInstall($answers);
    }
    else {
      self::runNonInteractiveInstall();
    }

    $expected_output = $expected_output ?? ['Welcome to Vortex non-interactive installer'];
    $this->assertApplicationSuccessOutputContains($expected_output);

    $baseline = File::dir(static::$fixtures . '/../_baseline');
    static::replaceVersions(static::$sut);
    $this->assertBaselineDiffs($baseline, static::$fixtures, static::$sut);

    $this->assertCommon();

    if ($after_callback !== NULL) {
      $after_callback = static::fnu($after_callback);
      $after_callback($this);
    }
  }

  public static function dataProviderInstall(): array {
    return [
      'non-interactive' => [
        NULL,
        NULL,
        NULL,
        NULL,
      ],
      'interactive' => [
        NULL,
        NULL,
        static::tuiFill(),
        ['Welcome to Vortex interactive installer'],
      ],

      'names' => [
        static::fnw(function (): void {
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
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::GITHUB)),
      ],
      'code provider, other' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::OTHER)),
      ],

      'profile, minimal' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), Profile::MINIMAL)),
      ],
      'profile, the_empire' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), 'the_empire')),
      ],

      'theme, absent' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), '')),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertDirectoryNotContainsString('themes/custom', static::$sut, [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
        ])),
      ],

      'theme, custom' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), 'light_saber')),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertDirectoryNotContainsString('your_site_theme', static::$sut)),
      ],

      'services, no clamav' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::SOLR, Services::REDIS]))),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertSutNotContains('clamav')),
      ],
      'services, no redis' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::SOLR]))),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertSutNotContains('redis')),
      ],
      'services, no solr' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::REDIS]))),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertSutNotContains('solr')),
      ],
      'services, none' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), ',')),
        static::fnw(function (FunctionalTestBase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
        }),
      ],

      'hosting, acquia' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::ACQUIA)),
      ],
      'hosting, lagoon' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON)),
        static::fnw(fn(FunctionalTestBase $test) => $test->assertSutNotContains('acquia')),
      ],

      'db download source, url' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::URL)),
      ],
      'db download source, ftp' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::FTP)),
      ],
      'db download source, acquia' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::ACQUIA)),
      ],
      'db download source, lagoon' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::LAGOON)),
      ],
      'db download source, container_registry' => [
        static::fnw(function (): void {
          Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::CONTAINER_REGISTRY);
          Env::put(PromptManager::makeEnvName(DatabaseImage::id()), 'the_empire/star_wars:latest');
        }),
      ],

      'deploy type, artifact' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::ARTIFACT], ',', TRUE))),
      ],
      'deploy type, lagoon' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::LAGOON], ',', TRUE))),
      ],
      'deploy type, container_image' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::CONTAINER_IMAGE], ',', TRUE))),
      ],
      'deploy type, webhook' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK], ',', TRUE))),
      ],
      'deploy type, all, gha' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]))),
      ],
      'deploy type, all, circleci' => [
        static::fnw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
      'deploy type, none, gha' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), ',')),
      ],
      'deploy type, none, circleci' => [
        static::fnw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), ',');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],

      'provision, database' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::DATABASE)),
      ],
      'provision, profile' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::PROFILE)),
      ],

      'ciprovider, gha' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS)),
      ],
      'ciprovider, circleci' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI)),
      ],

      'deps updates provider, ci, gha' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI)),
      ],
      'deps updates provider, ci, circleci' => [
        static::fnw(function (): void {
          Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI);
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
      'deps updates provider, app' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_APP)),
      ],
      'deps updates provider, none' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::NONE)),
      ],

      'assign author PR, enabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::TRUE)),
      ],
      'assign author PR, disabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::FALSE)),
      ],

      'label merge conflicts PR, enabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::TRUE)),
      ],
      'label merge conflicts PR, disabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::FALSE)),
      ],

      'preserve docs project, enabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::TRUE)),
      ],
      'preserve docs project, disabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::FALSE)),
      ],

      'preserve docs onboarding, enabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::TRUE)),
      ],
      'preserve docs onboarding, disabled' => [
        static::fnw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::FALSE)),
      ],
    ];
  }

  protected function assertCommon(): void {
    $this->assertDirectoriesEqual(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
  }

}
