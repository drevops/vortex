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

    $expected = empty($expected) ? ['Welcome to Vortex non-interactive installer'] : $expected;
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
        ['Welcome to Vortex non-interactive installer'],
      ],

      'non-interactive' => [
        NULL,
        NULL,
        ['Welcome to Vortex non-interactive installer'],
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
      ],
      'code provider, other' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::OTHER)),
      ],

      'profile, minimal' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), Profile::MINIMAL)),
      ],
      'profile, the_empire' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), 'the_empire')),
      ],

      'theme, absent' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), '')),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString('themes/custom', static::$sut, [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
        ])),
      ],

      'theme, custom' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), 'light_saber')),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString('your_site_theme', static::$sut)),
      ],

      'services, no clamav' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::SOLR, Services::REDIS]))),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('clamav')),
      ],
      'services, no redis' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::SOLR]))),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('redis')),
      ],
      'services, no solr' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::REDIS]))),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('solr')),
      ],
      'services, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
        }),
      ],

      'hosting, acquia' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::ACQUIA)),
      ],
      'hosting, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON)),
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
      'provision, profile' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::PROFILE)),
      ],

      'ciprovider, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS)),
      ],
      'ciprovider, circleci' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI)),
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
