<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

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
use DrevOps\Installer\Tests\Traits\TuiTrait;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\Git;
use Laravel\Prompts\Key;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(PromptManager::class)]
class PromptManagerTest extends UnitTestBase {

  use TuiTrait;

  const FIXTURE_GITHUB_TOKEN = 'ghp_1234567890';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    static::tuiSetUp();

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTearDown();

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
  public function testPrompt(array $responses, array|string $expected, ?callable $before_callback = NULL): void {
    // Re-use the expected value as an exception message if it is a string.
    $exception = is_string($expected) ? $expected : NULL;
    if ($exception) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception);
    }

    static::$sut = File::dir(static::$sut . DIRECTORY_SEPARATOR . 'myproject', TRUE);

    $config = new Config(static::$sut);
    putenv('GITHUB_TOKEN=' . self::FIXTURE_GITHUB_TOKEN);

    if ($before_callback !== NULL) {
      $before_callback($this, $config);
    }

    $pm = new PromptManager($config);
    // Enter responses and fill in the missing ones if an exception is expected
    // so that in case of exception not being thrown, the test does not hang
    // waiting for more input.
    self::tuiInput($responses);
    $pm->prompt();

    if (!$exception) {
      $actual = $pm->getResponses();
      $this->assertEquals($expected, $actual, (string) $this->dataName());
    }
  }

  public static function dataProviderPrompt(): array {
    $defaults = [
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
      Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR],
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

    $defaults_installed = [
      CiProvider::id() => CiProvider::NONE,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE,
      AssignAuthorPr::id() => FALSE,
      LabelMergeConflictsPr::id() => FALSE,
      PreserveDocsProject::id() => FALSE,
      PreserveDocsOnboarding::id() => FALSE,
    ] + $defaults;

    $discovered = [
      Name::id() => 'Discovered project',
      MachineName::id() => 'discovered_project',
      Org::id() => 'Discovered project Org',
      OrgMachineName::id() => 'discovered_project_org',
      Domain::id() => 'discovered-project.com',
      GithubRepo::id() => 'discovered_project_org/discovered_project',
      ModulePrefix::id() => 'dp',
      Theme::id() => 'discovered_project',
    ] + $defaults;

    return [
      'defaults' => [
        self::tuiFill(),
        $defaults,
      ],

      'project name - discovery' => [
        self::tuiFill(),
        $discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'invalid project name' => [
        self::tuiFill(0, 'a_word'),
        'Please enter a valid project name.',
      ],

      'project machine name - discovery' => [
        self::tuiFill(),
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'project machine name - invalid' => [
        self::tuiFill(1, 'a word'),
        'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'org name - discovery' => [
        self::tuiFill(),
        $discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'org name - invalid' => [
        self::tuiFill(2, 'a_word'),
        'Please enter a valid organization name.',
      ],

      'org machine name - discovery' => [
        self::tuiFill(),
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $discovered,
        function (PromptManagerTest $test): void {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'org machine name - invalid ' => [
        self::tuiFill(3, 'a word'),
        'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'domain - discovery' => [
        self::tuiFill(),
        [Domain::id() => 'discovered-project-dotenv.com'] + $defaults,
        function (PromptManagerTest $test): void {
          $test->setDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'discovered-project-dotenv.com');
        },
      ],
      'domain - no protocol' => [
        self::tuiFill(4, 'myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - www prefix' => [
        self::tuiFill(4, 'www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - secure protocol' => [
        self::tuiFill(4, 'https://www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - unsecure protocol' => [
        self::tuiFill(4, 'http://www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - invalid - missing TLD' => [
        self::tuiFill(4, 'myproject'),
        'Please enter a valid domain name.',
      ],
      'domain - invalid - incorrect protocol' => [
        self::tuiFill(4, 'htt://myproject.com'),
        'Please enter a valid domain name.',
      ],

      'code repo - discovery' => [
        self::tuiFill(),
        [CodeProvider::id() => CodeProvider::GITHUB] + $defaults,
        function (PromptManagerTest $test): void {
          File::dump(static::$sut . '/.github/workflows/ci.yml');
        },
      ],

      'code repo - discovery - other' => [
        self::tuiFill(),
        [CodeProvider::id() => CodeProvider::GITHUB] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          Git::init(static::$sut);
        },
      ],

      'github repo - discovery' => [
        self::tuiFill(),
        [GithubRepo::id() => 'discovered-project-org/discovered-project'] + $defaults,
        function (PromptManagerTest $test): void {
          Git::init(static::$sut)->addRemote('origin', 'git@github.com:discovered-project-org/discovered-project.git');
        },
      ],
      'github repo - discovery - missing remote' => [
        self::tuiFill(),
        $defaults,
        function (PromptManagerTest $test): void {
          Git::init(static::$sut);
        },
      ],
      'github repo - valid name' => [
        self::tuiFill(6, 'custom_org/custom_project'),
        [GithubRepo::id() => 'custom_org/custom_project'] + $defaults,
      ],
      'github repo - valid name - hyphenated' => [
        self::tuiFill(6, 'custom-org/custom-project'),
        [GithubRepo::id() => 'custom-org/custom-project'] + $defaults,
      ],
      'github repo - empty' => [
        self::tuiFill(6, ''),
        [GithubRepo::id() => ''] + $defaults,
      ],
      'github repo - invalid name' => [
        self::tuiFill(6, 'custom_org-custom_project'),
        'Please enter a valid project name in the format "myorg/myproject"',
      ],

      'profile - discovery' => [
        self::tuiFill(),
        [Profile::id() => Profile::MINIMAL] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
        },
      ],
      'profile - discovery - non-Vortex project' => [
        self::tuiFill(),
        [Profile::id() => 'discovered_profile'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/discovered_profile/discovered_profile.info');
        },
      ],
      'profile - custom' => [
        self::tuiFill(7, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myprofile'),
        [Profile::id() => 'myprofile'] + $defaults,
      ],
      'profile - custom - invalid' => [
        self::tuiFill(7, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my profile'),
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'module prefix - discovery' => [
        self::tuiFill(),
        [ModulePrefix::id() => 'dp'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix - discovery - within profile' => [
        self::tuiFill(),
        [ModulePrefix::id() => 'dp'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix' => [
        self::tuiFill(8, 'myprefix'),
        [ModulePrefix::id() => 'myprefix'] + $defaults,
      ],
      'module prefix - invalid' => [
        self::tuiFill(8, 'my prefix'),
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'module prefix - invalid - capitalization' => [
        self::tuiFill(8, 'MyPrefix'),
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - discovery' => [
        self::tuiFill(),
        [Theme::id() => 'discovered_project'] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],
      'theme - discovery - non-Vortex project' => [
        self::tuiFill(),
        [Theme::id() => 'discovered_project'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],
      'theme' => [
        self::tuiFill(9, 'mytheme'),
        [Theme::id() => 'mytheme'] + $defaults,
      ],
      'theme - invalid' => [
        self::tuiFill(9, 'my theme'),
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'theme - invalid - capitalization' => [
        self::tuiFill(9, 'MyTheme'),
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'services - discovery - solr' => [
        self::tuiFill(),
        [
          Services::id() => [Services::SOLR],
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::SOLR => []]));
        },
      ],
      'services - discovery - redis' => [
        self::tuiFill(),
        [
          Services::id() => [Services::REDIS],
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::REDIS => []]));
        },
      ],
      'services - discovery - clamav' => [
        self::tuiFill(),
        [
          Services::id() => [Services::CLAMAV],
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => []]));
        },
      ],
      'services - discovery - all' => [
        self::tuiFill(),
        [
          Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR],
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => [], Services::REDIS => [], Services::SOLR => []]));
        },
      ],
      'services - discovery - none' => [
        self::tuiFill(),
        [
          Services::id() => [],
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['other_service' => []]));
        },
      ],
      'services - discovery - non-Vortex project' => [
        self::tuiFill(),
        $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::REDIS => [], Services::CLAMAV => [], Services::SOLR => []]));
        },
      ],

      'hosting provider - discovery - Acquia' => [
        self::tuiFill(),
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/hooks/somehook');
        },
      ],
      'hosting provider - discovery - Acquia from env' => [
        self::tuiFill(),
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::ACQUIA);
        },
      ],
      'hosting provider - discovery - Lagoon' => [
        self::tuiFill(),
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          DeployType::id() => [DeployType::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.lagoon.yml');
        },
      ],

      'webroot - custom - discovery' => [
        self::tuiFill(),
        [Webroot::id() => 'discovered_webroot'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('WEBROOT', 'discovered_webroot');
        },
      ],
      'webroot - custom - discovery no dotenv' => [
        self::tuiFill(),
        [Webroot::id() => 'discovered_webroot'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setComposerJsonValue('extra', ['drupal-scaffold' => ['drupal-scaffold' => ['locations' => ['web-root' => 'discovered_webroot']]]]);
        },
      ],
      'webroot - custom' => [
        self::tuiFill(12, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my_webroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'my_webroot'] + $defaults,
      ],
      'webroot - custom - capitalization' => [
        self::tuiFill(12, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyWebroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'MyWebroot'] + $defaults,
      ],
      'webroot - custom - invalid' => [
        self::tuiFill(12, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my webroot'),
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'deploy type - discovery' => [
        self::tuiFill(),
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::ARTIFACT, DeployType::WEBHOOK]));
        },
      ],

      'provision type - discovery - database' => [
        self::tuiFill(),
        [ProvisionType::id() => ProvisionType::DATABASE] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
        },
      ],
      'provision type - discovery - profile' => [
        self::tuiFill(),
        [ProvisionType::id() => ProvisionType::PROFILE, DatabaseDownloadSource::id() => DatabaseDownloadSource::NONE] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
        },
      ],

      'database image - discovery' => [
        self::tuiFill(15, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER),
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'discovered_owner/discovered_image:tag'] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->setDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
        },
      ],
      'database image' => [
        self::tuiFill(15, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry/myimage:mytag'),
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $defaults,
      ],
      'database image - invalid' => [
        self::tuiFill(15, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry:myimage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - invalid - capitalization' => [
        self::tuiFill(15, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyRegistry/MyImage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],

      'ci provider - discovery - gha' => [
        self::tuiFill(),
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml');
        },
      ],
      'ci provider - discovery - circleci' => [
        self::tuiFill(),
        [CiProvider::id() => CiProvider::CIRCLECI] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.circleci/config.yml');
        },
      ],
      'ci provider - discovery - none' => [
        self::tuiFill(),
        [CiProvider::id() => CiProvider::NONE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],

      'dependency updates provider - discovery - renovate self-hosted - gha' => [
        self::tuiFill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.github/workflows/deps-updates.yml');
        },
      ],
      'dependency updates provider - discovery - renovate self-hosted - circleci' => [
        self::tuiFill(),
        [
          CiProvider::id() => CiProvider::CIRCLECI,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        ] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.circleci/config.yml', 'deps-updates');
        },
      ],
      'dependency updates provider - discovery - renovate app' => [
        self::tuiFill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
        },
      ],
      'dependency updates provider - discovery - none' => [
        self::tuiFill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],

      'auto assign pr - discovery' => [
        self::tuiFill(),
        [AssignAuthorPr::id() => TRUE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],
      'auto assign pr - discovery - removed' => [
        self::tuiFill(),
        [AssignAuthorPr::id() => FALSE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'auto assign pr - discovery - non-Vortex' => [
        self::tuiFill(),
        [AssignAuthorPr::id() => TRUE] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],

      'label merge conflicts - discovery' => [
        self::tuiFill(),
        [LabelMergeConflictsPr::id() => TRUE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],
      'label merge conflicts - discovery - removed' => [
        self::tuiFill(),
        [LabelMergeConflictsPr::id() => FALSE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'label merge conflicts - discovery - non-Vortex' => [
        self::tuiFill(),
        [LabelMergeConflictsPr::id() => TRUE] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],

      'preserve project documentation - discovery' => [
        self::tuiFill(),
        [PreserveDocsProject::id() => TRUE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docs/README.md');
        },
      ],
      'preserve project documentation - discovery - removed' => [
        self::tuiFill(),
        [PreserveDocsProject::id() => FALSE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'preserve project documentation - discovery - non-Vortex' => [
        self::tuiFill(),
        [PreserveDocsProject::id() => TRUE] + $defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docs/README.md');
        },
      ],

      'preserve onboarding checklist - discovery' => [
        self::tuiFill(),
        [PreserveDocsOnboarding::id() => TRUE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],
      'preserve onboarding checklist - discovery - removed' => [
        self::tuiFill(),
        [PreserveDocsOnboarding::id() => FALSE] + $defaults_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->setVortexProject($config);
        },
      ],
      'preserve onboarding checklist - discovery - non-Vortex' => [
        self::tuiFill(),
        [PreserveDocsOnboarding::id() => TRUE] + $defaults,
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
    file_put_contents($readme, '[![Vortex](https://img.shields.io/badge/Vortex-1.2.3-5909A1.svg)](https://github.com/drevops/vortex/tree/1.2.3)' . PHP_EOL, FILE_APPEND);

    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
  }

  protected function setTheme(string $dir): void {
    File::dump($dir . '/scss/_variables.scss');
    File::dump($dir . '/Gruntfile.js');
    File::dump($dir . '/package.json', (string) json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
