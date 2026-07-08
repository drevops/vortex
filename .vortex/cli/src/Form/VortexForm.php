<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Config as TuiConfig;
use DrevOps\Tui\Derive\Derive;
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
   * Build the Vortex form configuration.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The installer configuration the handlers operate on.
   *
   * @return \DrevOps\Tui\Config\Config
   *   The configuration.
   */
  public static function create(Config $config): TuiConfig {
    return Form::create('Vortex', 'your project')
      ->banner(self::BANNER)
      ->envPrefix('VORTEX_')
      ->panel('general', 'General information', function (PanelBuilder $p) use ($config): void {
        $p->description('Project name, organization and public domain.');
        TuiAdapter::field($p, new Name($config), weight: 380);
        TuiAdapter::field($p, new MachineName($config), weight: 360, derive: new Derive('{{name}}', 'machine'));
        TuiAdapter::field($p, new Org($config), weight: 370, derive: new Derive('{{name}} Org'));
        TuiAdapter::field($p, new OrgMachineName($config), weight: 350, derive: new Derive('{{org}}', 'machine'));
        TuiAdapter::field($p, new Domain($config), weight: 280, derive: new Derive('{{machine_name}}.com', 'host'));
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p) use ($config): void {
        $p->description('Install profile, modules, theme and front-end build.');
        TuiAdapter::field($p, new Starter($config), weight: 250);
        TuiAdapter::field($p, new Profile($config), weight: 270);
        TuiAdapter::field($p, new ProfileCustom($config), weight: 260, when: new Condition('profile', eq: Profile::CUSTOM));
        TuiAdapter::field($p, new Modules($config), weight: 240);
        TuiAdapter::field($p, new ModulePrefix($config), weight: 310, derive: new Derive('{{machine_name}}', 'initials'));
        TuiAdapter::field($p, new CustomModules($config), weight: 300);
        TuiAdapter::field($p, new Theme($config), weight: 340);
        TuiAdapter::field($p, new ThemeCustom($config), weight: 330, when: new Condition('theme', eq: Theme::CUSTOM), derive: new Derive('{{machine_name}}', 'machine'));
        TuiAdapter::field($p, new FrontendBuild($config), weight: 320, when: new Condition('theme', eq: Theme::CUSTOM));
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p) use ($config): void {
        $p->description('Where the code lives and how releases are versioned.');
        TuiAdapter::field($p, new CodeProvider($config), weight: 230);
        TuiAdapter::field($p, new VersionScheme($config), weight: 220);
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p) use ($config): void {
        $p->description('Timezone, Docker services and developer tooling.');
        TuiAdapter::field($p, new Timezone($config), weight: 210);
        TuiAdapter::field($p, new Services($config), weight: 200);
        TuiAdapter::field($p, new Tools($config), weight: 190);
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p) use ($config): void {
        $p->description('Target hosting provider and project name.');
        TuiAdapter::field($p, new HostingProvider($config), weight: 180);
        TuiAdapter::field($p, new HostingProjectName($config), weight: 290, when: new Condition('hosting_provider', in: [HostingProvider::LAGOON, HostingProvider::ACQUIA]), derive: new Derive('{{machine_name}}'));
        TuiAdapter::field($p, new Webroot($config), weight: 10);
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p) use ($config): void {
        $p->description('How code is shipped to the hosting environment.');
        TuiAdapter::field($p, new DeployTypes($config), weight: 170);
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p) use ($config): void {
        $p->description('Provisioning method and database source.');
        TuiAdapter::field($p, new ProvisionType($config), weight: 150);
        TuiAdapter::field($p, new DatabaseFetchSource($config), weight: 140, when: new Condition('provision_type', eq: ProvisionType::DATABASE));
        TuiAdapter::field($p, new DatabaseImage($config), weight: 130, when: new Condition('database_fetch_source', eq: DatabaseFetchSource::CONTAINER_REGISTRY), derive: new Derive('{{org_machine_name}}/{{machine_name}}-data:latest', 'lower'));
        TuiAdapter::field($p, new Migration($config), weight: 120);
        TuiAdapter::field($p, new MigrationFetchSource($config), weight: 110, when: new Condition('migration', eq: TRUE));
        TuiAdapter::field($p, new MigrationImage($config), weight: 100, when: new Condition('migration_fetch_source', eq: MigrationFetchSource::CONTAINER_REGISTRY), derive: new Derive('{{org_machine_name}}/{{machine_name}}-data-migration:latest', 'lower'));
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p) use ($config): void {
        $p->description('Where build and deployment notifications are sent.');
        TuiAdapter::field($p, new NotificationChannels($config), weight: 160);
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p) use ($config): void {
        $p->description('CI provider and visual regression testing.');
        TuiAdapter::field($p, new CiProvider($config), weight: 90);
        TuiAdapter::field($p, new VisualRegression($config), weight: 80);
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p) use ($config): void {
        $p->description('Dependency updates, coverage and PR automation.');
        TuiAdapter::field($p, new DependencyUpdatesProvider($config), weight: 70);
        TuiAdapter::field($p, new CodeCoverageProvider($config), weight: 60);
        TuiAdapter::field($p, new AssignAuthorPr($config), weight: 50);
        TuiAdapter::field($p, new LabelMergeConflictsPr($config), weight: 40);
      })
      ->panel('documentation', 'Documentation', function (PanelBuilder $p) use ($config): void {
        $p->description('Whether project documentation is kept.');
        TuiAdapter::field($p, new PreserveDocsProject($config), weight: 30);
      })
      ->panel('ai', 'AI', function (PanelBuilder $p) use ($config): void {
        $p->description('Whether AI agent instructions are included.');
        TuiAdapter::field($p, new AiCodeInstructions($config), weight: 20);
      })
      ->build();
  }

}
