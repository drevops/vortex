<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\Installer\Tests\Traits\TuiTrait;
use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Tui;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Services;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\ThemeRunner;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\Git;
use Laravel\Prompts\Key;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(PromptManager::class)]
#[CoversClass(Tui::class)]
#[CoversClass(Env::class)]
#[CoversClass(Converter::class)]
#[CoversClass(Composer::class)]
class PromptManagerTest extends UnitTestCase {

  use UpstreamTuiTrait;
  use TuiTrait;

  const FIXTURE_GITHUB_TOKEN = 'ghp_1234567890';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::tuiSetUp();

    putenv('GITHUB_TOKEN=' . self::FIXTURE_GITHUB_TOKEN);

    static::$sut = File::mkdir(static::$sut . DIRECTORY_SEPARATOR . 'myproject');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTeardown();

    parent::tearDown();
  }

  /**
   * Test responses.
   *
   * @code
   * composer test -- --filter=testPrompt@"name of the data provider"
   * @endcode
   */
  #[DataProvider('dataProviderPrompt')]
  public function testPrompt(
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

    $answers = array_replace(static::defaultAnswers(), $answers);
    $keystrokes = static::tuiKeystrokes($answers, 40);
    Prompt::fake($keystrokes);

    $pm = new PromptManager($config);
    $pm->prompt();

    if (!$exception) {
      $actual = $pm->getResponses();
      $this->assertEquals($expected, $actual, (string) $this->dataName());
    }
  }

  public static function defaultAnswers(): array {
    return [
      Name::id() => static::TUI_DEFAULT,
      MachineName::id() => static::TUI_DEFAULT,
      Org::id() => static::TUI_DEFAULT,
      OrgMachineName::id() => static::TUI_DEFAULT,
      Domain::id() => static::TUI_DEFAULT,
      CodeProvider::id() => static::TUI_DEFAULT,
      GithubToken::id() => static::TUI_SKIP,
      GithubRepo::id() => static::TUI_DEFAULT,
      Profile::id() => static::TUI_DEFAULT,
      ModulePrefix::id() => static::TUI_DEFAULT,
      Theme::id() => static::TUI_DEFAULT,
      ThemeRunner::id() => static::TUI_DEFAULT,
      Services::id() => static::TUI_DEFAULT,
      HostingProvider::id() => static::TUI_DEFAULT,
      Webroot::id() => static::TUI_DEFAULT,
      DeployType::id() => static::TUI_DEFAULT,
      ProvisionType::id() => static::TUI_DEFAULT,
      DatabaseDownloadSource::id() => static::TUI_DEFAULT,
      DatabaseImage::id() => static::TUI_SKIP,
      CiProvider::id() => static::TUI_DEFAULT,
      DependencyUpdatesProvider::id() => static::TUI_DEFAULT,
      AssignAuthorPr::id() => static::TUI_DEFAULT,
      LabelMergeConflictsPr::id() => static::TUI_DEFAULT,
      PreserveDocsProject::id() => static::TUI_DEFAULT,
      PreserveDocsOnboarding::id() => static::TUI_DEFAULT,
    ];
  }

  public static function dataProviderPrompt(): array {
    $expected_defaults = [
      Name::id() => 'myproject',
      MachineName::id() => 'myproject',
      Org::id() => 'myproject Org',
      OrgMachineName::id() => 'myproject_org',
      Domain::id() => 'myproject.com',
      CodeProvider::id() => CodeProvider::GITHUB,
      GithubToken::id() => self::FIXTURE_GITHUB_TOKEN,
      GithubRepo::id() => 'myproject_org/myproject',
      Profile::id() => Profile::STANDARD,
      ModulePrefix::id() => 'mypr',
      Theme::id() => 'myproject',
      ThemeRunner::id() => ThemeRunner::GRUNT,
      Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY],
      HostingProvider::id() => HostingProvider::NONE,
      Webroot::id() => Webroot::WEB,
      DeployType::id() => [DeployType::WEBHOOK],
      ProvisionType::id() => ProvisionType::DATABASE,
      DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
      DatabaseImage::id() => NULL,
      CiProvider::id() => CiProvider::GITHUB_ACTIONS,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
      AssignAuthorPr::id() => TRUE,
      LabelMergeConflictsPr::id() => TRUE,
      PreserveDocsProject::id() => TRUE,
      PreserveDocsOnboarding::id() => TRUE,
    ];

    $expected_installed = [
      CiProvider::id() => CiProvider::NONE,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE,
      AssignAuthorPr::id() => FALSE,
      LabelMergeConflictsPr::id() => FALSE,
      PreserveDocsProject::id() => FALSE,
      PreserveDocsOnboarding::id() => FALSE,
    ] + $expected_defaults;

    $expected_discovered = [
      Name::id() => 'Discovered project',
      MachineName::id() => 'discovered_project',
      Org::id() => 'Discovered project Org',
      OrgMachineName::id() => 'discovered_project_org',
      Domain::id() => 'discovered-project.com',
      GithubRepo::id() => 'discovered_project_org/discovered_project',
      ModulePrefix::id() => 'dp',
      Theme::id() => 'discovered_project',
    ] + $expected_defaults;

    return [
      'defaults' => [
        [],
        $expected_defaults,
      ],

      'project name - discovery' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'invalid project name' => [
        [Name::id() => 'a_word'],
        'Please enter a valid project name.',
      ],

      'project machine name - discovery' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'project machine name - invalid' => [
        [MachineName::id() => 'a word'],
        'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'org name - discovery' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'org name - invalid' => [
        [Org::id() => 'a_word'],
        'Please enter a valid organization name.',
      ],

      'org machine name - discovery' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'org machine name - invalid ' => [
        [OrgMachineName::id() => 'a word'],
        'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'domain - discovery' => [
        [],
        [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->setDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'discovered-project-dotenv.com');
        },
      ],
      'domain - no protocol' => [
        [Domain::id() => 'myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - www prefix' => [
        [Domain::id() => 'www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - secure protocol' => [
        [Domain::id() => 'https://www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - unsecure protocol' => [
        [Domain::id() => 'http://www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - invalid - missing TLD' => [
        [Domain::id() => 'myproject'],
        'Please enter a valid domain name.',
      ],
      'domain - invalid - incorrect protocol' => [
        [Domain::id() => 'htt://myproject.com'],
        'Please enter a valid domain name.',
      ],

      'code repo - discovery' => [
        [],
        [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
        function (PromptManagerTest $test): void {
          File::dump(static::$sut . '/.github/workflows/ci.yml');
        },
      ],

      'code repo - discovery - other' => [
        [],
        [CodeProvider::id() => CodeProvider::GITHUB] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          Git::init(static::$sut);
        },
      ],

      'github repo - discovery' => [
        [],
        [GithubRepo::id() => 'discovered-project-org/discovered-project'] + $expected_defaults,
        function (PromptManagerTest $test): void {
          Git::init(static::$sut)->addRemote('origin', 'git@github.com:discovered-project-org/discovered-project.git');
        },
      ],
      'github repo - discovery - missing remote' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          Git::init(static::$sut);
        },
      ],
      'github repo - valid name' => [
        [GithubRepo::id() => 'custom_org/custom_project'],
        // self::tuiFill(6, 'custom_org/custom_project'),.
        [GithubRepo::id() => 'custom_org/custom_project'] + $expected_defaults,
      ],
      'github repo - valid name - hyphenated' => [
        [GithubRepo::id() => 'custom-org/custom-project'],
        [GithubRepo::id() => 'custom-org/custom-project'] + $expected_defaults,
      ],
      'github repo - empty' => [
        [GithubRepo::id() => ''],
        [GithubRepo::id() => ''] + $expected_defaults,
      ],
      'github repo - invalid name' => [
        [GithubRepo::id() => 'custom_org-custom_project'],
        'Please enter a valid project name in the format "myorg/myproject"',
      ],

      'profile - discovery' => [
        [],
        [Profile::id() => Profile::MINIMAL] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
        },
      ],
      'profile - discovery - non-Vortex project' => [
        [],
        [Profile::id() => 'discovered_profile'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/discovered_profile/discovered_profile.info');
        },
      ],
      'profile - minimal' => [
        [Profile::id() => Key::DOWN . Key::ENTER],
        [Profile::id() => 'minimal'] + $expected_defaults,
      ],

      'profile - custom' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'myprofile'],
        [Profile::id() => 'myprofile'] + $expected_defaults,
      ],
      'profile - custom - invalid' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'my profile'],
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'module prefix - discovery' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_base/dp_base.info');
        },
      ],
      'module prefix - discovery - within profile' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_base/dp_base.info');
        },
      ],
      'module prefix' => [
        [ModulePrefix::id() => 'myprefix'],
        [ModulePrefix::id() => 'myprefix'] + $expected_defaults,
      ],
      'module prefix - invalid' => [
        [ModulePrefix::id() => 'my prefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'module prefix - invalid - capitalization' => [
        [ModulePrefix::id() => 'MyPrefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - discovery' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],
      'theme - discovery - non-Vortex project' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],
      'theme' => [
        [Theme::id() => 'mytheme'],
        [Theme::id() => 'mytheme'] + $expected_defaults,
      ],
      'theme - invalid' => [
        [Theme::id() => 'my theme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'theme - invalid - capitalization' => [
        [Theme::id() => 'MyTheme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'services - discovery - solr' => [
        [],
        [Services::id() => [Services::SOLR]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::SOLR => []]));
        },
      ],
      'services - discovery - valkey' => [
        [],
        [Services::id() => [Services::VALKEY]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::VALKEY => []]));
        },
      ],
      'services - discovery - clamav' => [
        [],
        [Services::id() => [Services::CLAMAV]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => []]));
        },
      ],
      'services - discovery - all' => [
        [],
        [
          Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY],
        ] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => [], Services::VALKEY => [], Services::SOLR => []]));
        },
      ],
      'services - discovery - none' => [
        [],
        [Services::id() => []] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['other_service' => []]));
        },
      ],
      'services - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::VALKEY => [], Services::CLAMAV => [], Services::SOLR => []]));
        },
      ],

      'hosting provider - discovery - Acquia' => [
        [],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/hooks/somehook');
        },
      ],
      'hosting provider - discovery - Acquia from env' => [
        [],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::ACQUIA);
        },
      ],
      'hosting provider - discovery - Lagoon' => [
        [],
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          DeployType::id() => [DeployType::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.lagoon.yml');
        },
      ],

      'webroot - custom - discovery' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('WEBROOT', 'discovered_webroot');
        },
      ],
      'webroot - custom - discovery no dotenv' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setComposerJsonValue('extra', ['drupal-scaffold' => ['drupal-scaffold' => ['locations' => ['web-root' => 'discovered_webroot']]]]);
        },
      ],
      'webroot - custom' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my_webroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'my_webroot',
        ] + $expected_defaults,
      ],
      'webroot - custom - capitalization' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'MyWebroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'MyWebroot',
        ] + $expected_defaults,
      ],
      'webroot - custom - invalid' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my webroot',
        ],
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'deploy type - discovery' => [
        [],
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::ARTIFACT, DeployType::WEBHOOK]));
        },
      ],

      'provision type - discovery - database' => [
        [],
        [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
        },
      ],
      'provision type - discovery - profile' => [
        [],
        [ProvisionType::id() => ProvisionType::PROFILE, DatabaseDownloadSource::id() => DatabaseDownloadSource::NONE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
        },
      ],

      'database image - discovery' => [
        [
          GithubRepo::id() => static::TUI_SKIP,
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        ],
        [
          DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY,
          DatabaseImage::id() => 'discovered_owner/discovered_image:tag',
        ] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
        },
      ],
      'database image - valid' => [
        [
          GithubRepo::id() => static::TUI_SKIP,
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry/myimage:mytag',
        ],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $expected_defaults,
      ],
      'database image - invalid' => [
        [
          GithubRepo::id() => static::TUI_SKIP,
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry:myimage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - invalid - capitalization' => [
        [
          GithubRepo::id() => static::TUI_SKIP,
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'MyRegistry/MyImage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],

      'ci provider - discovery - gha' => [
        [],
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml');
        },
      ],
      'ci provider - discovery - circleci' => [
        [],
        [CiProvider::id() => CiProvider::CIRCLECI] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.circleci/config.yml');
        },
      ],
      'ci provider - discovery - none' => [
        [],
        [CiProvider::id() => CiProvider::NONE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],

      'dependency updates provider - discovery - renovate self-hosted - gha' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.github/workflows/deps-updates.yml');
        },
      ],
      'dependency updates provider - discovery - renovate self-hosted - circleci' => [
        [],
        [
          CiProvider::id() => CiProvider::CIRCLECI,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        ] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.circleci/config.yml', 'deps-updates');
        },
      ],
      'dependency updates provider - discovery - renovate app' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
        },
      ],
      'dependency updates provider - discovery - none' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],

      'auto assign pr - discovery' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],
      'auto assign pr - discovery - removed' => [
        [],
        [AssignAuthorPr::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'auto assign pr - discovery - non-Vortex' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],

      'label merge conflicts - discovery' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],
      'label merge conflicts - discovery - removed' => [
        [],
        [LabelMergeConflictsPr::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'label merge conflicts - discovery - non-Vortex' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],

      'preserve project documentation - discovery' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docs/README.md');
        },
      ],
      'preserve project documentation - discovery - removed' => [
        [],
        [PreserveDocsProject::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'preserve project documentation - discovery - non-Vortex' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docs/README.md');
        },
      ],

      'preserve onboarding checklist - discovery' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],
      'preserve onboarding checklist - discovery - removed' => [
        [],
        [PreserveDocsOnboarding::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'preserve onboarding checklist - discovery - non-Vortex' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],
    ];
  }

  protected function setComposerJsonValue(string $name, mixed $value): string {
    $composer_json = static::$sut . DIRECTORY_SEPARATOR . 'composer.json';
    file_put_contents($composer_json, json_encode([$name => $value], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $composer_json;
  }

  protected function setDotenvValue(string $name, mixed $value, string $filename = '.env'): string {
    $dotenv = static::$sut . DIRECTORY_SEPARATOR . $filename;

    file_put_contents($dotenv, sprintf('%s=%s', $name, $value) . PHP_EOL, FILE_APPEND);

    return $dotenv;
  }

  protected function setVortexProject(Config $config): void {
    // Add a README.md file with a Vortex badge.
    $readme = static::$sut . DIRECTORY_SEPARATOR . 'README.md';
    file_put_contents($readme, '[![Vortex](https://img.shields.io/badge/Vortex-1.2.3-65ACBC.svg)](https://github.com/drevops/vortex/tree/1.2.3)' . PHP_EOL, FILE_APPEND);

    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
  }

  protected function setTheme(string $dir): void {
    File::dump($dir . '/scss/_variables.scss');
    File::dump($dir . '/Gruntfile.js');
    File::dump($dir . '/package.json', (string) json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
