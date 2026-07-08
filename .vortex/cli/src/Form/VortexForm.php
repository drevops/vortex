<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\FieldType;
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
use DrevOps\VortexCli\Handler\OptionsInterface;
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
        self::field($p, Name::class);
        self::field($p, MachineName::class);
        self::field($p, Org::class);
        self::field($p, OrgMachineName::class);
        self::field($p, Domain::class);
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->description('Install profile, modules, theme and front-end build.');
        self::field($p, Starter::class);
        self::field($p, Profile::class);
        self::field($p, ProfileCustom::class);
        self::field($p, Modules::class);
        self::field($p, ModulePrefix::class);
        self::field($p, CustomModules::class);
        self::field($p, Theme::class);
        self::field($p, ThemeCustom::class);
        self::field($p, FrontendBuild::class);
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p): void {
        $p->description('Where the code lives and how releases are versioned.');
        self::field($p, CodeProvider::class);
        self::field($p, VersionScheme::class);
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p): void {
        $p->description('Timezone, Docker services and developer tooling.');
        self::field($p, Timezone::class);
        self::field($p, Services::class);
        self::field($p, Tools::class);
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p): void {
        $p->description('Target hosting provider and project name.');
        self::field($p, HostingProvider::class);
        self::field($p, HostingProjectName::class);
        self::field($p, Webroot::class);
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p): void {
        $p->description('How code is shipped to the hosting environment.');
        self::field($p, DeployTypes::class);
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p): void {
        $p->description('Provisioning method and database source.');
        self::field($p, ProvisionType::class);
        self::field($p, DatabaseFetchSource::class);
        self::field($p, DatabaseImage::class);
        self::field($p, Migration::class);
        self::field($p, MigrationFetchSource::class);
        self::field($p, MigrationImage::class);
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p): void {
        $p->description('Where build and deployment notifications are sent.');
        self::field($p, NotificationChannels::class);
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p): void {
        $p->description('CI provider and visual regression testing.');
        self::field($p, CiProvider::class);
        self::field($p, VisualRegression::class);
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p): void {
        $p->description('Dependency updates, coverage and PR automation.');
        self::field($p, DependencyUpdatesProvider::class);
        self::field($p, CodeCoverageProvider::class);
        self::field($p, AssignAuthorPr::class);
        self::field($p, LabelMergeConflictsPr::class);
      })
      ->panel('documentation', 'Documentation', function (PanelBuilder $p): void {
        $p->description('Whether project documentation is kept.');
        self::field($p, PreserveDocsProject::class);
      })
      ->panel('ai', 'AI', function (PanelBuilder $p): void {
        $p->description('Whether AI agent instructions are included.');
        self::field($p, AiCodeInstructions::class);
      })
      ->build();
  }

  /**
   * Declare a handler's question on a panel.
   *
   * The adapter between the handlers and the form: handlers declare their
   * question as pure data, and this is the single place converting that
   * metadata into form elements.
   *
   * @param \DrevOps\Tui\Builder\PanelBuilder $p
   *   The panel builder.
   * @param class-string<\DrevOps\VortexCli\Handler\FieldInterface> $handler
   *   The handler class declaring the question.
   */
  protected static function field(PanelBuilder $p, string $handler): void {
    $field = match ($handler::type()) {
      FieldType::Text => $p->text($handler::id(), $handler::label()),
      FieldType::Select => $p->select($handler::id(), $handler::label()),
      FieldType::MultiSelect => $p->multiselect($handler::id(), $handler::label()),
      FieldType::Confirm => $p->confirm($handler::id(), $handler::label()),
      FieldType::Suggest => $p->suggest($handler::id(), $handler::label()),
    };

    $field->weight($handler::weight());

    if ($handler::description() !== '') {
      $field->description($handler::description());
    }

    if ($handler::default() !== NULL) {
      $field->default($handler::default());
    }

    if ($handler::required()) {
      $field->required();
    }

    if (is_a($handler, OptionsInterface::class, TRUE)) {
      $field->options($handler::options());
    }

    $when = $handler::when();
    if ($when instanceof ConditionInterface) {
      $field->when($when);
    }

    $derive = $handler::derive();
    if ($derive instanceof Derive) {
      $field->derive($derive);
    }

    $discover = $handler::discover();
    if ($discover !== NULL) {
      $field->discover($discover);
    }
  }

}
