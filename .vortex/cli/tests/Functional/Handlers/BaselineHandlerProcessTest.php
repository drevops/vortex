<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeProvider;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexCli\Prompts\Handlers\DeployTypes;
use DrevOps\VortexCli\Prompts\Handlers\Domain;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\Internal;
use DrevOps\VortexCli\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexCli\Prompts\Handlers\MachineName;
use DrevOps\VortexCli\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexCli\Prompts\Handlers\Name;
use DrevOps\VortexCli\Prompts\Handlers\Org;
use DrevOps\VortexCli\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexCli\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexCli\Prompts\Handlers\Profile;
use DrevOps\VortexCli\Prompts\Handlers\ProvisionType;
use DrevOps\VortexCli\Prompts\Handlers\Services;
use DrevOps\VortexCli\Prompts\Handlers\Theme;
use DrevOps\VortexCli\Prompts\Handlers\Timezone;
use DrevOps\VortexCli\Prompts\Handlers\Webroot;
use DrevOps\VortexCli\Prompts\PromptManager;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\Git;
use DrevOps\VortexCli\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(CiProvider::class)]
#[CoversClass(CodeProvider::class)]
#[CoversClass(DatabaseFetchSource::class)]
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

  public static function dataProviderHandlerProcess(): \Iterator {
    yield static::BASELINE_DATASET => [
      NULL,
      NULL,
      ['Welcome to the Vortex CLI non-interactive install'],
    ];
    yield 'non_interactive' => [
      NULL,
      NULL,
      ['Welcome to the Vortex CLI non-interactive install'],
    ];
    yield 'non_interactive_config_file' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $prompts_file = static::$tmp . DIRECTORY_SEPARATOR . 'prompts.json';
          File::dump($prompts_file, (string) json_encode([
            // Test overriding scalar value.
            Org::id() => 'My custom org',
            // Test overriding array value.
            Services::id() => [Services::SOLR, Services::CLAMAV],
          ]));
          $test->installOptions['prompts'] = $prompts_file;
      }),
      NULL,
      ['Welcome to the Vortex CLI non-interactive install'],
    ];
    yield 'non_interactive_config_string' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $prompts_string = (string) json_encode([
            // Test overriding scalar value.
            Org::id() => 'My other custom org',
            // Test overriding array value.
            Services::id() => [Services::SOLR, Services::REDIS],
          ]);
          $test->installOptions['prompts'] = $prompts_string;
      }),
      NULL,
      ['Welcome to the Vortex CLI non-interactive install'],
    ];
  }

}
