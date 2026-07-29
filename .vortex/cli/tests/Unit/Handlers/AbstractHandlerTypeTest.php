<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\AbstractHandler;
use DrevOps\VortexCli\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexCli\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeProvider;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexCli\Prompts\Handlers\MigrationImage;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexCli\Prompts\Handlers\DeployTypes;
use DrevOps\VortexCli\Prompts\Handlers\Domain;
use DrevOps\VortexCli\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexCli\Prompts\Handlers\Gitleaks;
use DrevOps\VortexCli\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexCli\Prompts\Handlers\MachineName;
use DrevOps\VortexCli\Prompts\Handlers\Migration;
use DrevOps\VortexCli\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexCli\Prompts\Handlers\Modules;
use DrevOps\VortexCli\Prompts\Handlers\Name;
use DrevOps\VortexCli\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexCli\Prompts\Handlers\Org;
use DrevOps\VortexCli\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexCli\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexCli\Prompts\Handlers\Profile;
use DrevOps\VortexCli\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexCli\Prompts\Handlers\ProvisionType;
use DrevOps\VortexCli\Prompts\Handlers\Services;
use DrevOps\VortexCli\Prompts\Handlers\Starter;
use DrevOps\VortexCli\Prompts\Handlers\Theme;
use DrevOps\VortexCli\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexCli\Prompts\Handlers\Timezone;
use DrevOps\VortexCli\Prompts\Handlers\Tools;
use DrevOps\VortexCli\Prompts\Handlers\VersionScheme;
use DrevOps\VortexCli\Prompts\Handlers\VisualRegression;
use DrevOps\VortexCli\Prompts\Handlers\Webroot;
use DrevOps\VortexCli\Prompts\PromptManager;
use DrevOps\VortexCli\Prompts\PromptType;
use DrevOps\VortexCli\Tests\Unit\UnitTestCase;
use DrevOps\VortexCli\Utils\Config;
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
    yield 'migration_image' => [MigrationImage::id(), PromptType::Text];
    // Select handlers.
    yield 'starter' => [Starter::id(), PromptType::Select];
    yield 'profile' => [Profile::id(), PromptType::Select];
    yield 'theme' => [Theme::id(), PromptType::Select];
    yield 'code_provider' => [CodeProvider::id(), PromptType::Select];
    yield 'version_scheme' => [VersionScheme::id(), PromptType::Select];
    yield 'hosting_provider' => [HostingProvider::id(), PromptType::Select];
    yield 'provision_type' => [ProvisionType::id(), PromptType::Select];
    yield 'database_fetch_source' => [DatabaseFetchSource::id(), PromptType::Select];
    yield 'migration_fetch_source' => [MigrationFetchSource::id(), PromptType::Select];
    yield 'ci_provider' => [CiProvider::id(), PromptType::Select];
    yield 'dependency_updates_provider' => [DependencyUpdatesProvider::id(), PromptType::Select];
    yield 'code_coverage_provider' => [CodeCoverageProvider::id(), PromptType::Select];
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
    yield 'visual_regression' => [VisualRegression::id(), PromptType::Confirm];
    yield 'gitleaks' => [Gitleaks::id(), PromptType::Confirm];
    yield 'frontend_build' => [FrontendBuild::id(), PromptType::Confirm];
    // Suggest handlers.
    yield 'timezone' => [Timezone::id(), PromptType::Suggest];
  }

}
