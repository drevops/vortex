<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
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
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Traits\TuiTrait;
use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\Tui;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(AiCodeInstructions::class)]
#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(CiProvider::class)]
#[CoversClass(CodeProvider::class)]
#[CoversClass(Composer::class)]
#[CoversClass(Converter::class)]
#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
#[CoversClass(DependencyUpdatesProvider::class)]
#[CoversClass(DeployType::class)]
#[CoversClass(Domain::class)]
#[CoversClass(Env::class)]
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
#[CoversClass(PromptManager::class)]
#[CoversClass(ProvisionType::class)]
#[CoversClass(Services::class)]
#[CoversClass(Theme::class)]
#[CoversClass(Tui::class)]
#[CoversClass(Webroot::class)]
class PromptManagerTest extends UnitTestCase {

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

  /**
   * Test responses.
   *
   * @code
   * composer test -- --filter=testRunPrompts@"name of the data provider"
   * @endcode
   */
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
      CodeProvider::id() => static::TUI_DEFAULT,
      Profile::id() => static::TUI_DEFAULT,
      ModulePrefix::id() => static::TUI_DEFAULT,
      Theme::id() => static::TUI_DEFAULT,
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
      AiCodeInstructions::id() => static::TUI_DEFAULT,
    ];
  }

  public static function dataProviderRunPrompts(): array {
    // Expected defaults for a new project.
    $expected_defaults = [
      Name::id() => 'myproject',
      MachineName::id() => 'myproject',
      Org::id() => 'myproject Org',
      OrgMachineName::id() => 'myproject_org',
      Domain::id() => 'myproject.com',
      CodeProvider::id() => CodeProvider::GITHUB,
      Profile::id() => Profile::STANDARD,
      ModulePrefix::id() => 'mypr',
      Theme::id() => 'myproject',
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
      AiCodeInstructions::id() => AiCodeInstructions::NONE,
    ];

    // Expected values for a pre-installed project.
    $expected_installed = [
      CiProvider::id() => CiProvider::NONE,
      DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE,
      AssignAuthorPr::id() => FALSE,
      LabelMergeConflictsPr::id() => FALSE,
      PreserveDocsProject::id() => FALSE,
      PreserveDocsOnboarding::id() => FALSE,
    ] + $expected_defaults;

    // Expected values for a responses discovered from the env.
    $expected_discovered = [
      Name::id() => 'Discovered project',
      MachineName::id() => 'discovered_project',
      Org::id() => 'Discovered project Org',
      OrgMachineName::id() => 'discovered_project_org',
      Domain::id() => 'discovered-project.com',
      ModulePrefix::id() => 'dp',
      Theme::id() => 'discovered_project',
    ] + $expected_defaults;

    return [
      'defaults' => [
        [],
        $expected_defaults,
      ],

      'project name - prompt' => [
        [Name::id() => 'Prompted project'],
        [
          Name::id() => 'Prompted project',
          MachineName::id() => 'prompted_project',
          Org::id() => 'Prompted project Org',
          OrgMachineName::id() => 'prompted_project_org',
          Domain::id() => 'prompted-project.com',
          ModulePrefix::id() => 'pp',
          Theme::id() => 'prompted_project',
        ] + $expected_defaults,
      ],
      'project name - prompt - invalid' => [
        [Name::id() => 'a_word'],
        'Please enter a valid project name.',
      ],
      'project name - discovery - dotenv' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubDotenvValue('VORTEX_PROJECT', 'discovered_project');
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'project name - discovery - description' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'project name - discovery - description short' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project.');
        },
      ],
      'project name - discovery - description unmatched' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('description', 'Some other description');
        },
      ],

      'project machine name - prompt' => [
        [MachineName::id() => 'prompted_project'],
        [
          MachineName::id() => 'prompted_project',
          Domain::id() => 'prompted-project.com',
          ModulePrefix::id() => 'pp',
          Theme::id() => 'prompted_project',
        ] + $expected_defaults,
      ],
      'project machine name - prompt - invalid' => [
        [MachineName::id() => 'a word'],
        'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'project machine name - discovery' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'project machine name - discovery - hyphenated' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered-project',
          Org::id() => 'myproject Org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'discovered_project_org/discovered-project');
        },
      ],
      'project machine name - discovery - unmatched' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'invalid_composer_name_format');
        },
      ],

      'org name - prompt' => [
        [Org::id() => 'Prompted Org'],
        [
          Org::id() => 'Prompted Org',
          OrgMachineName::id() => 'prompted_org',
        ] + $expected_defaults,
      ],
      'org name - invalid' => [
        [Org::id() => 'a_word'],
        'Please enter a valid organization name.',
      ],
      'org name - discovery' => [
        [],
        $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],
      'org name - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('description', 'Some other description that does not match the expected pattern');
        },
      ],

      'org machine name - prompt' => [
        [OrgMachineName::id() => 'prompted_org'],
        [OrgMachineName::id() => 'prompted_org'] + $expected_defaults,
      ],
      'org machine name - invalid ' => [
        [OrgMachineName::id() => 'a word'],
        'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'org machine name - discovery' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'discovered_project_org/discovered_project');
        },
      ],
      'org machine name - discovery - hyphenated' => [
        [],
        [
          Name::id() => 'myproject',
          MachineName::id() => 'discovered_project',
          Org::id() => 'myproject Org',
          OrgMachineName::id() => 'discovered-project-org',
        ] + $expected_discovered,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'discovered-project-org/discovered_project');
        },
      ],
      'org machine name - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubComposerJsonValue('name', 'invalid_format');
        },
      ],

      'domain - prompt' => [
        [Domain::id() => 'myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - prompt - www prefix' => [
        [Domain::id() => 'www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - prompt - secure protocol' => [
        [Domain::id() => 'https://www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],
      'domain - prompt - unsecure protocol' => [
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
      'domain - discovery' => [
        [],
        [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'discovered-project-dotenv.com');
        },
      ],
      'domain - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', '');
        },
      ],

      'code repo - prompt' => [
        [CodeProvider::id() => Key::ENTER],
        [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
      ],
      'code repo - prompt - other' => [
        [CodeProvider::id() => Key::DOWN . Key::ENTER],
        [
          CodeProvider::id() => CodeProvider::OTHER,
          CiProvider::id() => CiProvider::NONE,
        ] + $expected_defaults,
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
        [
          CodeProvider::id() => CodeProvider::OTHER,
        ] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          Git::init(static::$sut);
        },
      ],
      'code repo - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No .github directory and no .git directory - fall back to default.
        },
      ],

      'profile - prompt' => [
        [Profile::id() => Key::DOWN . Key::ENTER],
        [Profile::id() => 'minimal'] + $expected_defaults,
      ],
      'profile - prompt - custom' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'myprofile'],
        [Profile::id() => 'myprofile'] + $expected_defaults,
      ],
      'profile - prompt - invalid' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'my profile'],
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'profile - discovery' => [
        [],
        [Profile::id() => Profile::MINIMAL] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
        },
      ],
      'profile - discovery - non-Vortex project' => [
        [],
        [Profile::id() => 'discovered_profile'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/discovered_profile/discovered_profile.info');
        },
      ],
      'profile - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No .env file and no profile info files - fall back to default.
        },
      ],

      'module prefix - prompt' => [
        [ModulePrefix::id() => 'myprefix'],
        [ModulePrefix::id() => 'myprefix'] + $expected_defaults,
      ],
      'module prefix - prompt - override' => [
        [ModulePrefix::id() => 'myprefix'],
        [ModulePrefix::id() => 'myprefix'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_base/dp_base.info');
        },
      ],
      'module prefix - prompt - invalid' => [
        [ModulePrefix::id() => 'my prefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'module prefix - prompt - invalid - capitalization' => [
        [ModulePrefix::id() => 'MyPrefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'module prefix - discovery' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_base/dp_base.info');
        },
      ],
      'module prefix - discovery - core' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix - discovery - within profile' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_base/dp_base.info');
        },
      ],
      'module prefix - discovery - within profile - core' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_core/dp_core.info');
        },
      ],
      'module prefix - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No *_base or *_core modules exist - should fall back to default.
        },
      ],

      'theme - prompt' => [
        [Theme::id() => 'mytheme'],
        [Theme::id() => 'mytheme'] + $expected_defaults,
      ],
      'theme - prompt - invalid' => [
        [Theme::id() => 'my theme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'theme - prompt - invalid - capitalization' => [
        [Theme::id() => 'MyTheme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'theme - discovery' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],
      'theme - discovery - non-Vortex project' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],
      'theme - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No theme files exist and no DRUPAL_THEME in .env - fall back.
        },
      ],

      'services - prompt' => [
        [Services::id() => Key::ENTER],
        [Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY]] + $expected_defaults,
      ],

      'services - discovery - solr' => [
        [],
        [Services::id() => [Services::SOLR]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::SOLR => []]));
        },
      ],
      'services - discovery - valkey' => [
        [],
        [Services::id() => [Services::VALKEY]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::VALKEY => []]));
        },
      ],
      'services - discovery - clamav' => [
        [],
        [Services::id() => [Services::CLAMAV]] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => []]));
        },
      ],
      'services - discovery - all' => [
        [],
        [
          Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY],
        ] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::CLAMAV => [], Services::VALKEY => [], Services::SOLR => []]));
        },
      ],
      'services - discovery - none' => [
        [],
        [Services::id() => []] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['other_service' => []]));
        },
      ],
      'services - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // Invalid YAML causes discovery to fail and fall back to defaults.
          File::dump(static::$sut . '/docker-compose.yml', <<<'YAML'
- !text |
  first line
YAML
          );
        },
      ],
      'services - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump([Services::VALKEY => [], Services::CLAMAV => [], Services::SOLR => []]));
        },
      ],

      'hosting provider - prompt' => [
        [HostingProvider::id() => Key::ENTER],
        [HostingProvider::id() => HostingProvider::NONE] + $expected_defaults,
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
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::ACQUIA);
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
      'hosting provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No hooks, .lagoon.yml, or ACQUIA env var - fall back to default.
        },
      ],

      'webroot - prompt' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my_webroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'my_webroot',
        ] + $expected_defaults,
      ],
      'webroot - prompt - capitalization' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'MyWebroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'MyWebroot',
        ] + $expected_defaults,
      ],
      'webroot - prompt - invalid' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my webroot',
        ],
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],
      'webroot - discovery' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubDotenvValue('WEBROOT', 'discovered_webroot');
        },
      ],
      'webroot - discovery - composer' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubComposerJsonValue('extra', ['drupal-scaffold' => ['drupal-scaffold' => ['locations' => ['web-root' => 'discovered_webroot']]]]);
        },
      ],
      'webroot - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No WEBROOT in .env and no composer.json scaffold - fall back.
        },
      ],

      'deploy type - prompt' => [
        [DeployType::id() => Key::ENTER],
        [DeployType::id() => [DeployType::WEBHOOK]] + $expected_defaults,
      ],
      'deploy type - discovery' => [
        [],
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::ARTIFACT, DeployType::WEBHOOK]));
        },
      ],
      'deploy type - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No VORTEX_DEPLOY_TYPES in .env - should fall back to default.
        },
      ],

      'provision type - prompt' => [
        [ProvisionType::id() => Key::ENTER],
        [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
      ],
      'provision type - discovery - database' => [
        [],
        [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
        },
      ],
      'provision type - discovery - profile' => [
        [],
        [ProvisionType::id() => ProvisionType::PROFILE, DatabaseDownloadSource::id() => DatabaseDownloadSource::NONE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
        },
      ],
      'provision type - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No VORTEX_PROVISION_TYPE in .env - should fall back to default.
        },
      ],

      'database download source - prompt' => [
        [DatabaseDownloadSource::id() => Key::ENTER],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::URL] + $expected_defaults,
      ],
      'database download source - discovery' => [
        [],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::FTP] + $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::FTP);
        },
      ],
      'database download source - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', 'invalid_source');
        },
      ],

      'database image - prompt' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry/myimage:mytag',
        ],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $expected_defaults,
      ],
      'database image - invalid' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry:myimage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - invalid - capitalization' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'MyRegistry/MyImage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - discovery' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        ],
        [
          DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY,
          DatabaseImage::id() => 'discovered_owner/discovered_image:tag',
        ] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
        },
      ],

      'ci provider - prompt' => [
        [CiProvider::id() => Key::ENTER],
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_defaults,
      ],
      'ci provider - discovery - gha' => [
        [],
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml');
        },
      ],
      'ci provider - discovery - circleci' => [
        [],
        [CiProvider::id() => CiProvider::CIRCLECI] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.circleci/config.yml');
        },
      ],
      'ci provider - discovery - none' => [
        [],
        [CiProvider::id() => CiProvider::NONE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'ci provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No CI files and not installed - should fall back to default.
        },
      ],

      'dependency updates provider - prompt' => [
        [DependencyUpdatesProvider::id() => Key::ENTER],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $expected_defaults,
      ],
      'dependency updates provider - discovery - renovate self-hosted - gha' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.github/workflows/update-dependencies.yml');
        },
      ],
      'dependency updates provider - discovery - renovate self-hosted - circleci' => [
        [],
        [
          CiProvider::id() => CiProvider::CIRCLECI,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        ] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.circleci/config.yml', 'update-dependencies');
        },
      ],
      'dependency updates provider - discovery - renovate app' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
        },
      ],
      'dependency updates provider - discovery - none' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'dependency updates provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No renovate.json and not installed - should fall back to default.
        },
      ],

      'auto assign pr - prompt' => [
        [AssignAuthorPr::id() => Key::ENTER],
        [AssignAuthorPr::id() => TRUE] + $expected_defaults,
      ],
      'auto assign pr - discovery' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],
      'auto assign pr - discovery - removed' => [
        [],
        [AssignAuthorPr::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'auto assign pr - discovery - non-Vortex' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],
      'auto assign pr - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No assign-author.yml workflow and not installed - fall back.
        },
      ],

      'label merge conflicts - prompt' => [
        [LabelMergeConflictsPr::id() => Key::ENTER],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_defaults,
      ],
      'label merge conflicts - discovery' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],
      'label merge conflicts - discovery - removed' => [
        [],
        [LabelMergeConflictsPr::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'label merge conflicts - discovery - non-Vortex' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],
      'label merge conflicts - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No label-merge-conflict.yml workflow and not installed - fall back.
        },
      ],

      'preserve project documentation - prompt' => [
        [PreserveDocsProject::id() => Key::ENTER],
        [PreserveDocsProject::id() => TRUE] + $expected_defaults,
      ],
      'preserve project documentation - discovery' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docs/README.md');
        },
      ],
      'preserve project documentation - discovery - removed' => [
        [],
        [PreserveDocsProject::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'preserve project documentation - discovery - non-Vortex' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docs/README.md');
        },
      ],
      'preserve project documentation - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No docs/README.md and not installed - should fall back to default.
        },
      ],

      'preserve onboarding checklist - prompt' => [
        [PreserveDocsOnboarding::id() => Key::ENTER],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_defaults,
      ],
      'preserve onboarding checklist - discovery' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],
      'preserve onboarding checklist - discovery - removed' => [
        [],
        [PreserveDocsOnboarding::id() => FALSE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'preserve onboarding checklist - discovery - non-Vortex' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],
      'preserve onboarding checklist - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No docs/onboarding.md and not installed - fall back to default.
        },
      ],

      'ai instructions - prompt' => [
        [AiCodeInstructions::id() => Key::ENTER],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_defaults,
      ],
      'ai instructions - discovery' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::CLAUDE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/CLAUDE.md');
        },
      ],
      'ai instructions - discovery - removed' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_installed,
        function (PromptManagerTest $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
      'ai instructions - discovery - non-Vortex' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_defaults,
        function (PromptManagerTest $test, Config $config): void {
          File::dump(static::$sut . '/CLAUDE.md');
        },
      ],
      'ai instructions - discovery - invalid' => [
        [],
        $expected_defaults,
        function (PromptManagerTest $test): void {
          // No CLAUDE.md and not installed - should fall back to default.
        },
      ],

    ];
  }

  protected function stubComposerJsonValue(string $name, mixed $value): string {
    $composer_json = static::$sut . DIRECTORY_SEPARATOR . 'composer.json';
    file_put_contents($composer_json, json_encode([$name => $value], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
    file_put_contents($readme, '[![Vortex](https://img.shields.io/badge/Vortex-1.2.3-65ACBC.svg)](https://github.com/drevops/vortex/tree/1.2.3)' . PHP_EOL, FILE_APPEND);

    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
  }

  protected function stubTheme(string $dir): void {
    File::dump($dir . '/scss/_variables.scss');
    File::dump($dir . '/Gruntfile.js');
    File::dump($dir . '/package.json', (string) json_encode(['build-dev' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}
