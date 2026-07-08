<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Config;
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

/**
 * The Vortex form, declared in PHP.
 *
 * This is the question set and its panel structure - the data the TUI
 * engine collects answers for.
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
   * @return \DrevOps\Tui\Config\Config
   *   The configuration.
   */
  public static function create(): Config {
    return Form::create('Vortex', 'your project')
      ->banner(self::BANNER)
      ->envPrefix('VORTEX_')
      ->panel('general', 'General information', function (PanelBuilder $p): void {
        $p->description('Project name, organization and public domain.');
        Name::field($p);
        MachineName::field($p);
        Org::field($p);
        OrgMachineName::field($p);
        Domain::field($p);
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->description('Install profile, modules, theme and front-end build.');
        Starter::field($p);
        Profile::field($p);
        ProfileCustom::field($p);
        Modules::field($p);
        ModulePrefix::field($p);
        CustomModules::field($p);
        Theme::field($p);
        ThemeCustom::field($p);
        FrontendBuild::field($p);
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p): void {
        $p->description('Where the code lives and how releases are versioned.');
        CodeProvider::field($p);
        VersionScheme::field($p);
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p): void {
        $p->description('Timezone, Docker services and developer tooling.');
        Timezone::field($p);
        Services::field($p);
        Tools::field($p);
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p): void {
        $p->description('Target hosting provider and project name.');
        HostingProvider::field($p);
        HostingProjectName::field($p);
        Webroot::field($p);
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p): void {
        $p->description('How code is shipped to the hosting environment.');
        DeployTypes::field($p);
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p): void {
        $p->description('Provisioning method and database source.');
        ProvisionType::field($p);
        DatabaseFetchSource::field($p);
        DatabaseImage::field($p);
        Migration::field($p);
        MigrationFetchSource::field($p);
        MigrationImage::field($p);
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p): void {
        $p->description('Where build and deployment notifications are sent.');
        NotificationChannels::field($p);
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p): void {
        $p->description('CI provider and visual regression testing.');
        CiProvider::field($p);
        VisualRegression::field($p);
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p): void {
        $p->description('Dependency updates, coverage and PR automation.');
        DependencyUpdatesProvider::field($p);
        CodeCoverageProvider::field($p);
        AssignAuthorPr::field($p);
        LabelMergeConflictsPr::field($p);
      })
      ->panel('documentation', 'Documentation', function (PanelBuilder $p): void {
        $p->description('Whether project documentation is kept.');
        PreserveDocsProject::field($p);
      })
      ->panel('ai', 'AI', function (PanelBuilder $p): void {
        $p->description('Whether AI agent instructions are included.');
        AiCodeInstructions::field($p);
      })
      ->build();
  }

}
