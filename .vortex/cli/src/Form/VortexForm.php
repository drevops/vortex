<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Model\FormDefinition;
use DrevOps\VortexCli\Handler\AiCodeInstructions;
use DrevOps\VortexCli\Handler\AssignAuthorPr;
use DrevOps\VortexCli\Handler\CiProvider;
use DrevOps\VortexCli\Handler\CodeCoverageProvider;
use DrevOps\VortexCli\Handler\CodeProvider;
use DrevOps\VortexCli\Handler\CustomModules;
use DrevOps\VortexCli\Handler\DatabaseFetchSource;
use DrevOps\VortexCli\Handler\DatabaseImage;
use DrevOps\VortexCli\Handler\DependencyUpdatesProvider;
use DrevOps\VortexCli\Handler\DeployTypes;
use DrevOps\VortexCli\Handler\Domain;
use DrevOps\VortexCli\Handler\FrontendBuild;
use DrevOps\VortexCli\Handler\Gitleaks;
use DrevOps\VortexCli\Handler\HostingProjectName;
use DrevOps\VortexCli\Handler\HostingProvider;
use DrevOps\VortexCli\Handler\LabelMergeConflictsPr;
use DrevOps\VortexCli\Handler\MachineName;
use DrevOps\VortexCli\Handler\Migration;
use DrevOps\VortexCli\Handler\MigrationFetchSource;
use DrevOps\VortexCli\Handler\MigrationImage;
use DrevOps\VortexCli\Handler\ModulePrefix;
use DrevOps\VortexCli\Handler\Modules;
use DrevOps\VortexCli\Handler\Name;
use DrevOps\VortexCli\Handler\NotificationChannels;
use DrevOps\VortexCli\Handler\Org;
use DrevOps\VortexCli\Handler\OrgMachineName;
use DrevOps\VortexCli\Handler\PreserveDocsProject;
use DrevOps\VortexCli\Handler\Profile;
use DrevOps\VortexCli\Handler\ProfileCustom;
use DrevOps\VortexCli\Handler\ProvisionType;
use DrevOps\VortexCli\Handler\Services;
use DrevOps\VortexCli\Handler\Starter;
use DrevOps\VortexCli\Handler\Theme;
use DrevOps\VortexCli\Handler\ThemeCustom;
use DrevOps\VortexCli\Handler\Timezone;
use DrevOps\VortexCli\Handler\Tools;
use DrevOps\VortexCli\Handler\VersionScheme;
use DrevOps\VortexCli\Handler\VisualRegression;
use DrevOps\VortexCli\Handler\Webroot;
use DrevOps\VortexCli\Utils\Config;

/**
 * The Vortex form, declared in PHP.
 *
 * The form owns the panel structure, question order, conditional gating,
 * derivation and processing weights; everything about a single question comes
 * from its handler through the TuiAdapter.
 *
 * @package DrevOps\VortexCli\Form
 */
final class VortexForm {

  /**
   * The start banner shown before the interactive TUI.
   */
  protected const BANNER = <<<'BANNER'

  ██╗   ██╗ ██████╗ ██████╗ ████████╗███████╗██╗  ██╗
  ██║   ██║██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝╚██╗██╔╝
  ██║   ██║██║   ██║██████╔╝   ██║   █████╗   ╚███╔╝
  ╚██╗ ██╔╝██║   ██║██╔══██╗   ██║   ██╔══╝   ██╔██╗
   ╚████╔╝ ╚██████╔╝██║  ██║   ██║   ███████╗██╔╝ ██╗
    ╚═══╝   ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝

                Drupal project template

                                           by DrevOps
BANNER;

  /**
   * The field-less processors that bookend field processing.
   */
  public const PROCESSORS = [
    ['id' => 'dotenv', 'weight' => -1000],
    ['id' => 'internal', 'weight' => 1000],
  ];

  /**
   * The processing weight of each question; lower processes earlier.
   *
   * Processing order is the CLI's concern, not the form's: specific string
   * replacements must run before the generic ones they overlap with.
   */
  public const WEIGHTS = [
    'webroot' => 10,
    'ai_code_instructions' => 20,
    'preserve_docs_project' => 30,
    'label_merge_conflicts_pr' => 40,
    'assign_author_pr' => 50,
    'code_coverage_provider' => 60,
    'dependency_updates_provider' => 70,
    'gitleaks' => 75,
    'visual_regression' => 80,
    'ci_provider' => 90,
    'migration_image' => 100,
    'migration_fetch_source' => 110,
    'migration' => 120,
    'database_image' => 130,
    'database_fetch_source' => 140,
    'provision_type' => 150,
    'notification_channels' => 160,
    'deploy_types' => 170,
    'hosting_provider' => 180,
    'tools' => 190,
    'services' => 200,
    'timezone' => 210,
    'version_scheme' => 220,
    'code_provider' => 230,
    'modules' => 240,
    'starter' => 250,
    'profile_custom' => 260,
    'profile' => 270,
    'domain' => 280,
    'hosting_project_name' => 290,
    'custom_modules' => 300,
    'module_prefix' => 310,
    'frontend_build' => 320,
    'theme_custom' => 330,
    'theme' => 340,
    'org_machine_name' => 350,
    'machine_name' => 360,
    'org' => 370,
    'name' => 380,
  ];

  /**
   * Build the Vortex form definition.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The installer configuration the handlers operate on.
   *
   * @return \DrevOps\Tui\Model\FormDefinition
   *   The form definition.
   */
  public static function create(Config $config): FormDefinition {
    return Form::create('Vortex', 'your project')
      ->banner(self::BANNER)
      ->envPrefix('VORTEX_')
      ->panel('general', 'General information', function (PanelBuilder $p) use ($config): void {
        $p->description('Project name, organization and public domain.');
        TuiAdapter::field($p, new Name($config));
        TuiAdapter::field($p, new MachineName($config), derive: new Derive('{{name}}', 'machine'));
        TuiAdapter::field($p, new Org($config), derive: new Derive('{{name}} Org'));
        TuiAdapter::field($p, new OrgMachineName($config), derive: new Derive('{{org}}', 'machine'));
        TuiAdapter::field($p, new Domain($config), derive: new Derive('{{machine_name}}.com', 'host'));
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p) use ($config): void {
        $p->description('Install profile, modules, theme and front-end build.');
        TuiAdapter::field($p, new Starter($config));
        TuiAdapter::field($p, new Profile($config));
        TuiAdapter::field($p, new ProfileCustom($config), when: new Condition('profile', eq: Profile::CUSTOM));
        TuiAdapter::field($p, new Modules($config));
        TuiAdapter::field($p, new ModulePrefix($config), derive: new Derive('{{machine_name}}', 'initials'));
        TuiAdapter::field($p, new CustomModules($config));
        TuiAdapter::field($p, new Theme($config));
        TuiAdapter::field($p, new ThemeCustom($config), when: new Condition('theme', eq: Theme::CUSTOM), derive: new Derive('{{machine_name}}', 'machine'));
        TuiAdapter::field($p, new FrontendBuild($config), when: new Condition('theme', eq: Theme::CUSTOM));
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p) use ($config): void {
        $p->description('Where the code lives and how releases are versioned.');
        TuiAdapter::field($p, new CodeProvider($config));
        TuiAdapter::field($p, new VersionScheme($config));
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p) use ($config): void {
        $p->description('Timezone, Docker services and developer tooling.');
        TuiAdapter::field($p, new Timezone($config));
        TuiAdapter::field($p, new Services($config));
        TuiAdapter::field($p, new Tools($config));
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p) use ($config): void {
        $p->description('Target hosting provider and project name.');
        TuiAdapter::field($p, new HostingProvider($config));
        TuiAdapter::field($p, new HostingProjectName($config), when: new Condition('hosting_provider', in: [HostingProvider::LAGOON, HostingProvider::ACQUIA]), derive: new Derive('{{machine_name}}'));
        TuiAdapter::field($p, new Webroot($config));
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p) use ($config): void {
        $p->description('How code is shipped to the hosting environment.');
        TuiAdapter::field($p, new DeployTypes($config));
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p) use ($config): void {
        $p->description('Provisioning method and database source.');
        TuiAdapter::field($p, new ProvisionType($config));
        TuiAdapter::field($p, new DatabaseFetchSource($config), when: new Condition('provision_type', eq: ProvisionType::DATABASE));
        TuiAdapter::field($p, new DatabaseImage($config), when: new Condition('database_fetch_source', eq: DatabaseFetchSource::CONTAINER_REGISTRY), derive: new Derive('{{org_machine_name}}/{{machine_name}}-data:latest', 'lower'));
        TuiAdapter::field($p, new Migration($config));
        TuiAdapter::field($p, new MigrationFetchSource($config), when: new Condition('migration', eq: TRUE));
        TuiAdapter::field($p, new MigrationImage($config), when: new Condition('migration_fetch_source', eq: MigrationFetchSource::CONTAINER_REGISTRY), derive: new Derive('{{org_machine_name}}/{{machine_name}}-data-migration:latest', 'lower'));
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p) use ($config): void {
        $p->description('Where build and deployment notifications are sent.');
        TuiAdapter::field($p, new NotificationChannels($config));
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p) use ($config): void {
        $p->description('CI provider, visual regression and secret scanning.');
        TuiAdapter::field($p, new CiProvider($config));
        TuiAdapter::field($p, new VisualRegression($config));
        TuiAdapter::field($p, new Gitleaks($config));
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p) use ($config): void {
        $p->description('Dependency updates, coverage and PR automation.');
        TuiAdapter::field($p, new DependencyUpdatesProvider($config));
        TuiAdapter::field($p, new CodeCoverageProvider($config));
        TuiAdapter::field($p, new AssignAuthorPr($config));
        TuiAdapter::field($p, new LabelMergeConflictsPr($config));
      })
      ->panel('documentation', 'Documentation', function (PanelBuilder $p) use ($config): void {
        $p->description('Whether project documentation is kept.');
        TuiAdapter::field($p, new PreserveDocsProject($config));
      })
      ->panel('ai', 'AI', function (PanelBuilder $p) use ($config): void {
        $p->description('Whether AI agent instructions are included.');
        TuiAdapter::field($p, new AiCodeInstructions($config));
      })
      ->build();
  }

}
