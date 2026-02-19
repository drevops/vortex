<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AbstractHandler;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Prompts\PromptType;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests that all handler type() methods return expected PromptType values.
 */
#[CoversClass(AbstractHandler::class)]
class AbstractHandlerTypeTest extends UnitTestCase {

  #[DataProvider('dataProviderTypeInference')]
  public function testTypeInference(string $handler_id, PromptType $expected_type): void {
    $config = Config::fromString('{}');
    $prompt_manager = new PromptManager($config);
    $handlers = $prompt_manager->getHandlers();

    $this->assertArrayHasKey($handler_id, $handlers, sprintf('Handler "%s" not found.', $handler_id));

    $handler = $handlers[$handler_id];
    $this->assertSame($expected_type, $handler->type(), sprintf('Handler "%s" returned wrong type.', $handler_id));
  }

  /**
   * Data provider for testTypeInference.
   */
  public static function dataProviderTypeInference(): array {
    return [
      // Text handlers.
      'name' => [Name::id(), PromptType::Text],
      'machine_name' => [MachineName::id(), PromptType::Text],
      'org' => [Org::id(), PromptType::Text],
      'org_machine_name' => [OrgMachineName::id(), PromptType::Text],
      'domain' => [Domain::id(), PromptType::Text],
      'profile_custom' => [ProfileCustom::id(), PromptType::Text],
      'module_prefix' => [ModulePrefix::id(), PromptType::Text],
      'theme_custom' => [ThemeCustom::id(), PromptType::Text],
      'hosting_project_name' => [HostingProjectName::id(), PromptType::Text],
      'webroot' => [Webroot::id(), PromptType::Text],
      'database_image' => [DatabaseImage::id(), PromptType::Text],

      // Select handlers.
      'starter' => [Starter::id(), PromptType::Select],
      'profile' => [Profile::id(), PromptType::Select],
      'theme' => [Theme::id(), PromptType::Select],
      'code_provider' => [CodeProvider::id(), PromptType::Select],
      'version_scheme' => [VersionScheme::id(), PromptType::Select],
      'hosting_provider' => [HostingProvider::id(), PromptType::Select],
      'provision_type' => [ProvisionType::id(), PromptType::Select],
      'database_download_source' => [DatabaseDownloadSource::id(), PromptType::Select],
      'migration_download_source' => [MigrationDownloadSource::id(), PromptType::Select],
      'ci_provider' => [CiProvider::id(), PromptType::Select],
      'dependency_updates_provider' => [DependencyUpdatesProvider::id(), PromptType::Select],

      // MultiSelect handlers.
      'modules' => [Modules::id(), PromptType::MultiSelect],
      'services' => [Services::id(), PromptType::MultiSelect],
      'tools' => [Tools::id(), PromptType::MultiSelect],
      'deploy_types' => [DeployTypes::id(), PromptType::MultiSelect],
      'notification_channels' => [NotificationChannels::id(), PromptType::MultiSelect],

      // Confirm handlers.
      'migration' => [Migration::id(), PromptType::Confirm],
      'assign_author_pr' => [AssignAuthorPr::id(), PromptType::Confirm],
      'label_merge_conflicts_pr' => [LabelMergeConflictsPr::id(), PromptType::Confirm],
      'preserve_docs_project' => [PreserveDocsProject::id(), PromptType::Confirm],
      'ai_code_instructions' => [AiCodeInstructions::id(), PromptType::Confirm],

      // Suggest handlers.
      'timezone' => [Timezone::id(), PromptType::Suggest],
    ];
  }

}
