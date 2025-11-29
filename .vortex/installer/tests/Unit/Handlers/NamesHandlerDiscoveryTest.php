<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Name::class)]
#[CoversClass(MachineName::class)]
#[CoversClass(Org::class)]
#[CoversClass(OrgMachineName::class)]
#[CoversClass(Domain::class)]
#[CoversClass(ModulePrefix::class)]
class NamesHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_discovered = static::getExpectedDiscovered();

    return [
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_PROJECT', 'discovered_project');
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],

      'project name - discovery - description' => [
        [],
        $expected_discovered,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],

      'project name - discovery - description short' => [
        [],
        $expected_discovered,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project.');
        },
      ],

      'project name - discovery - description unmatched' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('name', 'discovered_project_org/discovered-project');
        },
      ],

      'project machine name - discovery - unmatched' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('description', 'Drupal 11 Standard installation of Discovered project for Discovered project Org');
        },
      ],

      'org name - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
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
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('name', 'discovered-project-org/discovered_project');
        },
      ],

      'org machine name - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubComposerJsonValue('name', 'invalid_format');
        },
      ],
    ];
  }

}
