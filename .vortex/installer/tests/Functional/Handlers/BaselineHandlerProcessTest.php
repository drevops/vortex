<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(CiProvider::class)]
#[CoversClass(CodeProvider::class)]
#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
#[CoversClass(DependencyUpdatesProvider::class)]
#[CoversClass(DeployTypes::class)]
#[CoversClass(Domain::class)]
#[CoversClass(HostingProvider::class)]
#[CoversClass(Internal::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
#[CoversClass(MachineName::class)]
#[CoversClass(ModulePrefix::class)]
#[CoversClass(Name::class)]
#[CoversClass(Org::class)]
#[CoversClass(OrgMachineName::class)]
#[CoversClass(PreserveDocsProject::class)]
#[CoversClass(Profile::class)]
#[CoversClass(ProvisionType::class)]
#[CoversClass(Services::class)]
#[CoversClass(Theme::class)]
#[CoversClass(Timezone::class)]
#[CoversClass(Webroot::class)]
#[CoversClass(PromptManager::class)]
#[CoversClass(RepositoryDownloader::class)]
#[CoversClass(Config::class)]
#[CoversClass(Git::class)]
#[CoversClass(Tui::class)]
class BaselineHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      static::BASELINE_DATASET => [
        NULL,
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],

      'non_interactive' => [
        NULL,
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],

      'non_interactive_config_file' => [
        static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $config_file = static::$tmp . DIRECTORY_SEPARATOR . 'config.json';
          File::dump($config_file, (string) json_encode([
            // Test overriding scalar value.
            Org::envName() => 'My custom org',
            // Test overriding array value.
            Services::envName() => [Services::SOLR, Services::CLAMAV],
          ]));
          $test->installOptions['config'] = $config_file;
        }),
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],

      'non_interactive_config_string' => [
        static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $config_string = (string) json_encode([
            // Test overriding scalar value.
            Org::envName() => 'My other custom org',
            // Test overriding array value.
            Services::envName() => [Services::SOLR, Services::REDIS],
          ]);
          $test->installOptions['config'] = $config_string;
        }),
        NULL,
        ['Welcome to the Vortex non-interactive installer'],
      ],
    ];
  }

}
