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
use Laravel\Prompts\Key;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use PHPUnit\Framework\TestCase;

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
  public function testPrompt(array $responses, array|string $expected, ?callable $before_callback = NULL) {
    // Re-use the expected value as an exception message if it is a string.
    $exception = is_string($expected) ? $expected : NULL;
    if ($exception) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception);
    }

    $output = new BufferedConsoleOutput();
    $config = new Config($this->prepareFixtureDir('myproject'));
    putenv('GITHUB_TOKEN=' . self::FIXTURE_GITHUB_TOKEN);

    if ($before_callback) {
      $before_callback($this);
    }

    $pm = new PromptManager($output, $config);
    // Enter responses and fill in the missing ones if an exception is expected
    // so that in case of exception not being thrown, the test does not hang
    // waiting for more input.
    self::promptsInput($responses, $exception ? 25 : 0);
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
    ];

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

      'profile - custom' => [
        self::fill(8, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myprofile'),
        [Profile::id() => Profile::CUSTOM, ProfileCustom::id() => 'myprofile'] + $defaults,
      ],
      'profile - custom - invalid' => [
        self::fill(8, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my profile'),
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
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

      'webroot - custom' => [
        self::fill(11, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my_webroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'my_webroot'] + $defaults,
      ],
      'webroot - custom - capitalization' => [
        self::fill(11, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyWebroot'),
        [HostingProvider::id() => HostingProvider::OTHER, Webroot::id() => 'MyWebroot'] + $defaults,
      ],
      'webroot - custom - invalid' => [
        self::fill(11, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'my webroot'),
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'database image' => [
        self::fill(14, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry/myimage:mytag'),
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $defaults,
      ],
      'database image - invalid' => [
        self::fill(14, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'myregistry:myimage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],
      'database image - invalid - capitalization' => [
        self::fill(14, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER, 'MyRegistry/MyImage:mytag'),
        'Please enter a valid container image name with an optional tag.',
      ],
    ];
  }

  protected static function fill(int $skip = 25, ...$values): array {
    $suffix_length = max(25 - $skip - count($values), 0);

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
}
