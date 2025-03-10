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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \DrevOps\Installer\Prompts\PromptManager
 */
class PromptManagerTest extends UnitTestBase {

  use TuiTrait;

  const MAX_QUESTIONS = 25;

  const FIXTURE_GITHUB_TOKEN = 'ghp_1234567890';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    self::tuiSetUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    self::tuiTeardown();
  }

  /**
   * Test responses.
   *
   * @covers ::prompt()
   * @covers ::getResponses
   * @dataProvider dataProviderPrompt
   *
   * Run a specific test:
   * @code
   * composer test -- --filter=testPrompt@"name of the data provider"
   * @endcode
   */
  public function testPrompt(array $responses, array|string $expected, ?callable $before_callback = NULL) {
    // Re-use the expected value as an exception message if it is a string.
    $exception = is_string($expected) ? $expected : NULL;
    if ($exception) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception);
    }

    $config = new Config($this->prepareFixtureDir('myproject'));
    putenv('GITHUB_TOKEN=' . self::FIXTURE_GITHUB_TOKEN);

    if ($before_callback) {
      $before_callback($this, $config);
    }

    $pm = new PromptManager($config);
    // Enter responses and fill in the missing ones if an exception is expected
    // so that in case of exception not being thrown, the test does not hang
    // waiting for more input.
    self::tuiInput($responses, $exception ? 25 : 0);
    $pm->prompt();

    if (!$exception) {
      $this->assertEquals($expected, $pm->getResponses(), $this->dataName());
    }
  }

  public static function dataProviderPrompt() {
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
      Services::id() => [Services::CLAMAV, Services::SOLR, Services::REDIS],
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
        self::fill(),
        $defaults,
      ],

      'project name - discovery' => [
        self::fill(),
        $discovered,
        function (TestCase $test) {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'invalid project name' => [
        self::fill(0, 'a_word'),
        'Please enter a valid project name.',
      ],

      'project machine name - discovery' => [
        self::fill(),
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $discovered,
        function (TestCase $test) {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'project machine name - invalid' => [
        self::fill(1, 'a word'),
        'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'org name - discovery' => [
        self::fill(),
        $discovered,
        function (TestCase $test) {
          $test->setComposerJsonValue('description', 'Drupal 10 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'org name - invalid' => [
        self::fill(2, 'a_word'),
        'Please enter a valid organization name.',
      ],

      'org machine name - discovery' => [
        self::fill(),
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $discovered,
        function (TestCase $test) {
          $test->setComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'org machine name - invalid ' => [
        self::fill(3, 'a word'),
        'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'domain - discovery' => [
        self::fill(),
        [Domain::id() => 'discovered-project-dotenv.com'] + $defaults,
        function (TestCase $test) {
          $test->setDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'discovered-project-dotenv.com');
        },
      ],
      'domain - no protocol' => [
        self::fill(4, 'myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - www prefix' => [
        self::fill(4, 'www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - secure protocol' => [
        self::fill(4, 'https://www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - unsecure protocol' => [
        self::fill(4, 'http://www.myproject.com'),
        [Domain::id() => 'myproject.com'] + $defaults,
      ],
      'domain - invalid - missing TLD' => [
        self::fill(4, 'myproject'),
        'Please enter a valid domain name.',
      ],
      'domain - invalid - incorrect protocol' => [
        self::fill(4, 'htt://myproject.com'),
        'Please enter a valid domain name.',
      ],

      'code repo - discovery' => [
        self::fill(),
        [CodeProvider::id() => CodeProvider::GITHUB] + $defaults,
        function (TestCase $test) {
          File::dump($test->fixtureDir . '/.github/workflows/ci.yml');
        },
      ],

      'code repo - discovery - other' => [
        self::fill(),
        [CodeProvider::id() => CodeProvider::GITHUB] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          Git::init($test->fixtureDir);
        },
      ],

      'github repo - discovery' => [
        self::fill(),
        [GithubRepo::id() => 'discovered-project-org/discovered-project'] + $defaults,
        function (TestCase $test) {
          Git::init($test->fixtureDir)->addRemote('origin', 'git@github.com:discovered-project-org/discovered-project.git');
        },
      ],
      'github repo - discovery - missing remote' => [
        self::fill(),
        $defaults,
        function (TestCase $test) {
          Git::init($test->fixtureDir);
        },
      ],
      'github repo - valid name' => [
        self::fill(7, 'custom_org/custom_project'),
        [GithubRepo::id() => 'custom_org/custom_project'] + $defaults,
      ],
      'github repo - valid name - hyphenated' => [
        self::fill(7, 'custom-org/custom-project'),
        [GithubRepo::id() => 'custom-org/custom-project'] + $defaults,
      ],
      'github repo - empty' => [
        self::fill(7, ''),
        [GithubRepo::id() => ''] + $defaults,
      ],
      'github repo - invalid name' => [
        self::fill(7, 'custom_org-custom_project'),
        'Please enter a valid project name in the format "myorg/myproject"',
      ],

      'profile - discovery' => [
        self::fill(),
        [Profile::id() => Profile::MINIMAL] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
        },
      ],
      'profile - discovery - non-Vortex project' => [
        self::fill(),
        [Profile::id() => 'discovered_profile'] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/web/profiles/discovered_profile/discovered_profile.info');
        },
      ],
      'profile - custom' => [
        self::fill(8, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myprofile'),
        [Profile::id() => 'myprofile'] + $defaults,
      ],
      'profile - custom - invalid' => [
        self::fill(8, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my profile'),
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'module prefix - discovery' => [
        self::fill(),
        [ModulePrefix::id() => 'dp'] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/web/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix - discovery - within profile' => [
        self::fill(),
        [ModulePrefix::id() => 'dp'] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/web/profiles/custom/discovered_profile/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix' => [
        self::fill(9, 'myprefix'),
        [ModulePrefix::id() => 'myprefix'] + $defaults,
      ],
      'module prefix - invalid' => [
        self::fill(9, 'my prefix'),
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'module prefix - invalid - capitalization' => [
        self::fill(9, 'MyPrefix'),
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - discovery' => [
        self::fill(),
        [Theme::id() => 'discovered_project'] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          $test->setDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],
      'theme - discovery - non-Vortex project' => [
        self::fill(),
        [Theme::id() => 'discovered_project'] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],
      'theme' => [
        self::fill(10, 'mytheme'),
        [Theme::id() => 'mytheme'] + $defaults,
      ],
      'theme - invalid' => [
        self::fill(10, 'my theme'),
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'theme - invalid - capitalization' => [
        self::fill(10, 'MyTheme'),
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'services - discovery - solr' => [
        self::fill(),
        [
          Services::id() => [Services::SOLR],
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump([Services::SOLR => []]));
        },
      ],
      'services - discovery - redis' => [
        self::fill(),
        [
          Services::id() => [Services::REDIS],
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump([Services::REDIS => []]));
        },
      ],
      'services - discovery - clamav' => [
        self::fill(),
        [
          Services::id() => [Services::CLAMAV],
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => []]));
        },
      ],
      'services - discovery - all' => [
        self::fill(),
        [
          Services::id() => [Services::CLAMAV, Services::SOLR, Services::REDIS],
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => [], Services::SOLR => [], Services::REDIS => [],]));
        },
      ],
      'services - discovery - none' => [
        self::fill(),
        [
          Services::id() => [],
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump(['other_service' => []]));
        },
      ],
      'services - discovery - non-Vortex project' => [
        self::fill(),
        $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/docker-compose.yml', Yaml::dump([Services::REDIS => [], Services::CLAMAV => [], Services::SOLR => []]));
        },
      ],

      'hosting provider - discovery - Acquia' => [
        self::fill(),
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/hooks/somehook');
        },
      ],
      'hosting provider - discovery - Acquia from env' => [
        self::fill(),
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::ACQUIA);
        },
      ],
      'hosting provider - discovery - Lagoon' => [
        self::fill(),
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          DeployType::id() => [DeployType::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/.lagoon.yml');
        },
      ],

      'webroot - custom - discovery' => [
        self::fill(),
        [Webroot::id() => 'discovered_webroot'] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('WEBROOT', 'discovered_webroot');
        },
      ],
      'webroot - custom - discovery no dotenv' => [
        self::fill(),
        [Webroot::id() => 'discovered_webroot'] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setComposerJsonValue('extra', ['drupal-scaffold' => ['drupal-scaffold' => ['locations' => ['web-root' => 'discovered_webroot']]]]);
        },
      ],
      'webroot - custom' => [
        self::fill(13, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my_webroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'my_webroot'] + $defaults,
      ],
      'webroot - custom - capitalization' => [
        self::fill(13, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyWebroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'MyWebroot'] + $defaults,
      ],
      'webroot - custom - invalid' => [
        self::fill(13, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my webroot'),
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'deploy type - discovery' => [
        self::fill(),
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::ARTIFACT, DeployType::WEBHOOK]));
        },
      ],

      'provision type - discovery - database' => [
        self::fill(),
        [ProvisionType::id() => ProvisionType::DATABASE] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
        },
      ],
      'provision type - discovery - profile' => [
        self::fill(),
        [ProvisionType::id() => ProvisionType::PROFILE, DatabaseDownloadSource::id() => DatabaseDownloadSource::NONE] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
        },
      ],

      'database image - discovery' => [
        self::fill(16, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER),
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'discovered_owner/discovered_image:tag'] + $defaults,
        function (TestCase $test, Config $config) {
          $test->setDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
        },
      ],
      'database image' => [
        self::fill(16, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry/myimage:mytag'),
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $defaults,
      ],
      'database image - invalid' => [
        self::fill(16, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry:myimage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - invalid - capitalization' => [
        self::fill(16, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyRegistry/MyImage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],

      'ci provider - discovery - gha' => [
        self::fill(),
        [CiProvider::id() => CiProvider::GHA] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/.github/workflows/build-test-deploy.yml');
        },
      ],
      'ci provider - discovery - circleci' => [
        self::fill(),
        [CiProvider::id() => CiProvider::CIRCLECI] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/.circleci/config.yml');
        },
      ],
      'ci provider - discovery - none' => [
        self::fill(),
        [CiProvider::id() => CiProvider::NONE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],

      'dependency updates provider - discovery - renovate self-hosted - gha' => [
        self::fill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/renovate.json');
          File::dump($test->fixtureDir . '/.github/workflows/renovate.yml');
        },
      ],
      'dependency updates provider - discovery - renovate self-hosted - circleci' => [
        self::fill(),
        [
          CiProvider::id() => CiProvider::CIRCLECI,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        ] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/renovate.json');
          File::dump($test->fixtureDir . '/.circleci/config.yml', 'renovatebot_schedule');
        },
      ],
      'dependency updates provider - discovery - renovate app' => [
        self::fill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/renovate.json');
        },
      ],
      'dependency updates provider - discovery - none' => [
        self::fill(),
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],

      'auto assign pr - discovery' => [
        self::fill(),
        [AssignAuthorPr::id() => TRUE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/.github/workflows/assign-author.yml');
        },
      ],
      'auto assign pr - discovery - removed' => [
        self::fill(),
        [AssignAuthorPr::id() => FALSE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],
      'auto assign pr - discovery - non-Vortex' => [
        self::fill(),
        [AssignAuthorPr::id() => TRUE] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/.github/workflows/assign-author.yml');
        },
      ],

      'label merge conflicts - discovery' => [
        self::fill(),
        [LabelMergeConflictsPr::id() => TRUE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/.github/workflows/label-merge-conflict.yml');
        },
      ],
      'label merge conflicts - discovery - removed' => [
        self::fill(),
        [LabelMergeConflictsPr::id() => FALSE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],
      'label merge conflicts - discovery - non-Vortex' => [
        self::fill(),
        [LabelMergeConflictsPr::id() => TRUE] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/.github/workflows/label-merge-conflict.yml');
        },
      ],

      'preserve project documentation - discovery' => [
        self::fill(),
        [PreserveDocsProject::id() => TRUE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docs/README.md');
        },
      ],
      'preserve project documentation - discovery - removed' => [
        self::fill(),
        [PreserveDocsProject::id() => FALSE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],
      'preserve project documentation - discovery - non-Vortex' => [
        self::fill(),
        [PreserveDocsProject::id() => TRUE] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/docs/README.md');
        },
      ],

      'preserve onboarding checklist - discovery' => [
        self::fill(),
        [PreserveDocsOnboarding::id() => TRUE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
          File::dump($test->fixtureDir . '/docs/onboarding.md');
        },
      ],
      'preserve onboarding checklist - discovery - removed' => [
        self::fill(),
        [PreserveDocsOnboarding::id() => FALSE] + $defaults_installed,
        function (TestCase $test, Config $config) {
          $test->setVortexProject($config);
        },
      ],
      'preserve onboarding checklist - discovery - non-Vortex' => [
        self::fill(),
        [PreserveDocsOnboarding::id() => TRUE] + $defaults,
        function (TestCase $test, Config $config) {
          File::dump($test->fixtureDir . '/docs/onboarding.md');
        },
      ],
    ];
  }

  protected static function fill(int $skip = self::MAX_QUESTIONS, ...$values): array {
    $suffix_length = max(self::MAX_QUESTIONS - $skip - count($values), 0);

    return array_merge(array_fill(0, $skip, NULL), $values, array_fill(0, $suffix_length, NULL));
  }

  protected function setComposerJsonValue(string $name, mixed $value): string {
    $composer_json = $this->fixtureDir . DIRECTORY_SEPARATOR . 'composer.json';
    file_put_contents($composer_json, json_encode([$name => $value], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $composer_json;
  }

  protected function setDotenvValue(string $name, mixed $value, string $filename = '.env'): string {
    $dotenv = $this->fixtureDir . DIRECTORY_SEPARATOR . $filename;

    file_put_contents($dotenv, "$name=$value" . PHP_EOL, FILE_APPEND);

    return $dotenv;
  }

  protected function setVortexProject(Config $config): void {
    // Add a README.md file with a Vortex badge.
    $readme = $this->fixtureDir . DIRECTORY_SEPARATOR . 'README.md';
    file_put_contents($readme, '[![Vortex](https://img.shields.io/badge/Vortex-1.2.3-5909A1.svg)](https://github.com/drevops/vortex/tree/1.2.3)' . PHP_EOL, FILE_APPEND);

    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
  }

  protected function setTheme(string $dir): void {
    File::dump($dir . '/scss/_variables.scss');
    File::dump($dir . '/Gruntfile.js');
    File::dump($dir . '/package.json', json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
