<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\PhpTui\Builder\Form;
use DrevOps\PhpTui\Builder\PanelBuilder;
use DrevOps\PhpTui\Condition\Condition;
use DrevOps\PhpTui\Tui;
use DrevOps\VortexCli\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexCli\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeProvider;
use DrevOps\VortexCli\Prompts\Handlers\CustomModules;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexCli\Prompts\Handlers\DeployTypes;
use DrevOps\VortexCli\Prompts\Handlers\Domain;
use DrevOps\VortexCli\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexCli\Prompts\Handlers\Gitleaks;
use DrevOps\VortexCli\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexCli\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexCli\Prompts\Handlers\MachineName;
use DrevOps\VortexCli\Prompts\Handlers\Migration;
use DrevOps\VortexCli\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\MigrationImage;
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
   * The namespace the engine searches for handler classes.
   */
  public const string HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Prompts\\Handlers';

  /**
   * The start banner shown before the interactive TUI.
   */
  private const string BANNER = <<<'BANNER'

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
  public const array PROCESSORS = [
    ['id' => 'dotenv', 'weight' => -1000],
    ['id' => 'internal', 'weight' => 1000],
  ];

  /**
   * The processing weight of each question; lower processes earlier.
   *
   * Processing order is the CLI's concern, not the form's: specific string
   * replacements must run before the generic ones they overlap with.
   */
  public const array WEIGHTS = [
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
   * The handler behind every question, keyed by question id.
   *
   * The form is the list of questions, so the handlers come from it rather
   * than from a second list that could drift out of step with it.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The configuration the handlers operate on.
   *
   * @return array<string,\DrevOps\VortexCli\Prompts\Handlers\HandlerInterface>
   *   The handlers, in processing order.
   *
   * @throws \RuntimeException
   *   When a question id has no handler behind it.
   */
  public static function handlers(Config $config): array {
    $registry = (new Tui(self::create($config), [self::HANDLER_NAMESPACE]))->registry();
    $handlers = [];

    foreach (array_keys(self::WEIGHTS) as $id) {
      $class = $registry->resolve($id);

      if ($class === NULL || !is_a($class, HandlerInterface::class, TRUE)) {
        throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
      }

      $handlers[$id] = new $class($config);
    }

    return $handlers;
  }

  /**
   * Build the Vortex form definition.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handlers operate on.
   *
   * @return \DrevOps\PhpTui\Builder\Form
   *   The form definition.
   */
  public static function create(Config $config): Form {
    return Form::create('Vortex')
      ->banner(self::BANNER)
      ->envPrefix('VORTEX_CLI_INSTALL_PROMPT_')
      ->panel('general', 'General information', function (PanelBuilder $p) use ($config): void {
        $p->description('Project name, organization and public domain.');
        TuiAdapter::field($p, new Name($config));
        TuiAdapter::field($p, new MachineName($config));
        TuiAdapter::field($p, new Org($config));
        TuiAdapter::field($p, new OrgMachineName($config));
        TuiAdapter::field($p, new Domain($config));
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p) use ($config): void {
        $p->description('Install profile, modules, theme and front-end build.');
        TuiAdapter::field($p, new Starter($config));
        TuiAdapter::field($p, new Profile($config));
        TuiAdapter::field($p, new ProfileCustom($config), when: new Condition('profile', eq: Profile::CUSTOM));
        TuiAdapter::field($p, new Modules($config));
        TuiAdapter::field($p, new ModulePrefix($config));
        TuiAdapter::field($p, new CustomModules($config));
        TuiAdapter::field($p, new Theme($config));
        TuiAdapter::field($p, new ThemeCustom($config), when: new Condition('theme', eq: Theme::CUSTOM));
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
        TuiAdapter::field($p, new HostingProjectName($config), when: new Condition('hosting_provider', in: [
          HostingProvider::LAGOON,
          HostingProvider::ACQUIA,
        ]));
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
        TuiAdapter::field($p, new DatabaseImage($config), when: new Condition('database_fetch_source', eq: DatabaseFetchSource::CONTAINER_REGISTRY));
        TuiAdapter::field($p, new Migration($config));
        TuiAdapter::field($p, new MigrationFetchSource($config), when: new Condition('migration', eq: TRUE));
        TuiAdapter::field($p, new MigrationImage($config), when: new Condition('migration_fetch_source', eq: MigrationFetchSource::CONTAINER_REGISTRY));
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
      });
  }

}
