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
  public static function dataProviderTypeInference(): \Iterator {
    // Text handlers.
    yield 'name' => [Name::id(), PromptType::Text];
    yield 'machine_name' => [MachineName::id(), PromptType::Text];
    yield 'org' => [Org::id(), PromptType::Text];
    yield 'org_machine_name' => [OrgMachineName::id(), PromptType::Text];
    yield 'domain' => [Domain::id(), PromptType::Text];
    yield 'profile_custom' => [ProfileCustom::id(), PromptType::Text];
    yield 'module_prefix' => [ModulePrefix::id(), PromptType::Text];
    yield 'theme_custom' => [ThemeCustom::id(), PromptType::Text];
    yield 'hosting_project_name' => [HostingProjectName::id(), PromptType::Text];
    yield 'webroot' => [Webroot::id(), PromptType::Text];
    yield 'database_image' => [DatabaseImage::id(), PromptType::Text];
    // Select handlers.
    yield 'starter' => [Starter::id(), PromptType::Select];
    yield 'profile' => [Profile::id(), PromptType::Select];
    yield 'theme' => [Theme::id(), PromptType::Select];
    yield 'code_provider' => [CodeProvider::id(), PromptType::Select];
    yield 'version_scheme' => [VersionScheme::id(), PromptType::Select];
    yield 'hosting_provider' => [HostingProvider::id(), PromptType::Select];
    yield 'provision_type' => [ProvisionType::id(), PromptType::Select];
    yield 'database_download_source' => [DatabaseDownloadSource::id(), PromptType::Select];
    yield 'migration_download_source' => [MigrationDownloadSource::id(), PromptType::Select];
    yield 'ci_provider' => [CiProvider::id(), PromptType::Select];
    yield 'dependency_updates_provider' => [DependencyUpdatesProvider::id(), PromptType::Select];
    // MultiSelect handlers.
    yield 'modules' => [Modules::id(), PromptType::MultiSelect];
    yield 'services' => [Services::id(), PromptType::MultiSelect];
    yield 'tools' => [Tools::id(), PromptType::MultiSelect];
    yield 'deploy_types' => [DeployTypes::id(), PromptType::MultiSelect];
    yield 'notification_channels' => [NotificationChannels::id(), PromptType::MultiSelect];
    // Confirm handlers.
    yield 'migration' => [Migration::id(), PromptType::Confirm];
    yield 'assign_author_pr' => [AssignAuthorPr::id(), PromptType::Confirm];
    yield 'label_merge_conflicts_pr' => [LabelMergeConflictsPr::id(), PromptType::Confirm];
    yield 'preserve_docs_project' => [PreserveDocsProject::id(), PromptType::Confirm];
    yield 'ai_code_instructions' => [AiCodeInstructions::id(), PromptType::Confirm];
    // Suggest handlers.
    yield 'timezone' => [Timezone::id(), PromptType::Suggest];
  }

}
