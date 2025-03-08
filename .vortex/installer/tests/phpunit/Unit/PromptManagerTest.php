<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

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
use DrevOps\Installer\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\Installer\Prompts\Handlers\MachineName;
use DrevOps\Installer\Prompts\Handlers\ModulePrefix;
use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\Handlers\Org;
use DrevOps\Installer\Prompts\Handlers\OrgMachineName;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsProject;
use DrevOps\Installer\Prompts\Handlers\Profile;
use DrevOps\Installer\Prompts\Handlers\ProfileCustom;
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Tests\Traits\PromptsTrait;
use DrevOps\Installer\Utils\Config;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;

/**
 * @coversDefaultClass \DrevOps\Installer\Prompts\PromptManager
 */
class PromptManagerTest extends UnitTestBase {

  use PromptsTrait;

  const FIXTURE_GITHUB_TOKEN = 'ghp_1234567890';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    self::promptsSetUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    self::promptsTeardown();
  }

  /**
   * Test responses.
   *
   * @covers ::prompt()
   * @covers ::getResponses
   * @dataProvider dataProviderPrompt
   */
  public function testPrompt(array $responses, array $expected) {
    $output = new BufferedConsoleOutput();
    $config = new Config($this->prepareFixtureDir('myproject'));
    putenv('GITHUB_TOKEN=' . self::FIXTURE_GITHUB_TOKEN);

    $pm = new PromptManager($output, $config);
    self::promptsInput($responses);

    $pm->prompt();

    $this->assertEquals($expected, $pm->getResponses(), $this->dataName());
  }

  public static function dataProviderPrompt() {
    return [
      'defaults' => [
        array_fill(0, 20, NULL),
        [
          Name::id() => 'myproject',
          MachineName::id() => 'myproject',
          Org::id() => 'myproject Org',
          OrgMachineName::id() => 'myproject_org',
          Domain::id() => 'myproject.com',
          CodeProvider::id() => CodeProvider::GITHUB,
          GithubToken::id() => self::FIXTURE_GITHUB_TOKEN,
          GithubRepo::id() => 'myproject_org/myproject',
          Profile::id() => Profile::STANDARD,
          ProfileCustom::id() => NULL,
          ModulePrefix::id() => 'mypr',
          Theme::id() => 'myproject',
          HostingProvider::id() => HostingProvider::NONE,
          Webroot::id() => Webroot::WEB,
          DeployType::id() => [DeployType::WEBHOOK],
          ProvisionType::id() => ProvisionType::DATABASE,
          DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
          DatabaseImage::id() => NULL,
          CiProvider::id() => CiProvider::GHA,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
          AssignAuthorPr::id() => TRUE,
          LabelMergeConflictsPr::id() => TRUE,
          PreserveDocsProject::id() => TRUE,
          PreserveDocsOnboarding::id() => TRUE,
        ],
      ],
    ];
  }

}
