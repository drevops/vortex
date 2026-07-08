<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Handler\CiProvider;
use DrevOps\VortexCli\Handler\CodeCoverageProvider;
use DrevOps\VortexCli\Handler\CodeProvider;
use DrevOps\VortexCli\Handler\CustomModules;
use DrevOps\VortexCli\Handler\DatabaseFetchSource;
use DrevOps\VortexCli\Handler\DependencyUpdatesProvider;
use DrevOps\VortexCli\Handler\DeployTypes;
use DrevOps\VortexCli\Handler\HostingProvider;
use DrevOps\VortexCli\Handler\MigrationFetchSource;
use DrevOps\VortexCli\Handler\Modules;
use DrevOps\VortexCli\Handler\NotificationChannels;
use DrevOps\VortexCli\Handler\Profile;
use DrevOps\VortexCli\Handler\ProvisionType;
use DrevOps\VortexCli\Handler\Services;
use DrevOps\VortexCli\Handler\Starter;
use DrevOps\VortexCli\Handler\Theme;
use DrevOps\VortexCli\Handler\Timezone;
use DrevOps\VortexCli\Handler\Tools;
use DrevOps\VortexCli\Handler\VersionScheme;
use DrevOps\VortexCli\Handler\Webroot;
use DrevOps\VortexCli\Utils\Converter;

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
        $p->text('name', 'Site name')->description('We will use this name in the project and documentation.')->required()->default(fn (Context $c): string => Converter::label(basename($c->directory)))->weight(380);
        $p->text('machine_name', 'Site machine name')
          ->description('We will use this name for the project directory and in the code.')
          ->required()
          ->derive(new Derive('{{name}}', 'machine'))
          ->weight(360);
        $p->text('org', 'Organization name')
          ->description('We will use this name in the project and documentation.')
          ->required()
          ->derive(new Derive('{{name}} Org'))
          ->weight(370);
        $p->text('org_machine_name', 'Organization machine name')
          ->description('We will use this name in the code.')
          ->required()
          ->derive(new Derive('{{org}}', 'machine'))
          ->weight(350);
        $p->text('domain', 'Public domain')
          ->description('Domain name without protocol and trailing slash.')
          ->required()
          ->derive(new Derive('{{machine_name}}.com', 'host'))
          ->weight(280);
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->description('Install profile, modules, theme and front-end build.');
        $p->select('starter', 'How would you like your site to be created on the first run?')
          ->description('Applies only on the first run of the installer.')
          ->default(Starter::LOAD_DATABASE_DEMO)
          ->options(Starter::options())
          ->weight(250);
        $p->select('profile', 'Profile')
          ->description('The Drupal installation profile the site is built on.')
          ->default(fn (Context $c): string => ($c->answers['starter'] ?? '') === Starter::INSTALL_PROFILE_DRUPALCMS ? Starter::INSTALL_PROFILE_DRUPALCMS_PATH : Profile::STANDARD)->required()
          ->options(Profile::options())
          ->weight(270);
        $p->text('profile_custom', 'Custom profile machine name')
          ->description('The machine name of your custom profile.')
          ->required()
          ->when(new Condition('profile', eq: Profile::CUSTOM))
          ->weight(260);
        $p->multiselect('modules', 'Modules')
          ->description('Optional contributed modules to include.')
          ->default(array_keys(Modules::options()))
          ->options(Modules::options())
          ->weight(240);
        $p->text('module_prefix', 'Custom modules prefix')
          ->description('We will use this name in custom modules.')
          ->required()
          ->derive(new Derive('{{machine_name}}', 'initials'))
          ->weight(310);
        $p->multiselect('custom_modules', 'Custom modules')
          ->description('Which scaffolded custom modules to keep.')
          ->default([CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO])
          ->options(CustomModules::options())
          ->weight(300);
        $p->select('theme', 'Theme')
          ->description('The base theme for the site front-end.')
          ->default(Theme::CUSTOM)->required()
          ->options(Theme::options())
          ->weight(340);
        $p->text('theme_custom', 'Custom theme machine name')
          ->description('We will use this name as a custom theme name.')
          ->required()
          ->when(new Condition('theme', eq: Theme::CUSTOM))
          ->derive(new Derive('{{machine_name}}', 'machine'))
          ->weight(330);
        $p->confirm('frontend_build', 'Build front-end assets in the container?')
          ->description('Disable to build theme assets on the host or as part of deployment.')
          ->default(TRUE)
          ->when(new Condition('theme', eq: Theme::CUSTOM))
          ->weight(320);
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p): void {
        $p->description('Where the code lives and how releases are versioned.');
        $p->select('code_provider', 'Repository provider')
          ->description('Vortex offers full automation with GitHub; support for other providers is limited.')
          ->default(CodeProvider::GITHUB)
          ->options(CodeProvider::options())
          ->weight(230);
        $p->select('version_scheme', 'Release versioning scheme')
          ->description('CalVer (year.month.patch) or SemVer (major.minor.patch).')
          ->default(VersionScheme::CALVER)
          ->options(VersionScheme::options())
          ->weight(220);
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p): void {
        $p->description('Timezone, Docker services and developer tooling.');
        $p->suggest('timezone', 'Timezone')
          ->description('Start typing to select the timezone for your project.')
          ->default(Timezone::UTC)
          ->options(Timezone::options())
          ->weight(210);
        $p->multiselect('services', 'Services')
          ->description('Optional Docker services to include.')
          ->default([Services::CLAMAV, Services::REDIS, Services::SOLR])
          ->options(Services::options())
          ->weight(200);
        $p->multiselect('tools', 'Development tools')
          ->description('Linting and testing tools to keep.')
          ->default([Tools::BEHAT, Tools::ESLINT, Tools::JEST, Tools::PHPCS, Tools::PHPSTAN, Tools::PHPUNIT, Tools::RECTOR, Tools::STYLELINT])
          ->options(Tools::options())
          ->weight(190);
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p): void {
        $p->description('Target hosting provider and project name.');
        $p->select('hosting_provider', 'Hosting provider')
          ->description('The hosting provider for the project.')
          ->default(HostingProvider::NONE)->required()
          ->options(HostingProvider::options())
          ->weight(180);
        $p->text('hosting_project_name', 'Hosting project name')
          ->description('Name as found in the hosting configuration; usually the site machine name.')
          ->required()
          ->when(new Condition('hosting_provider', in: [HostingProvider::LAGOON, HostingProvider::ACQUIA]))
          ->derive(new Derive('{{machine_name}}'))
          ->weight(290);
        $p->text('webroot', 'Custom web root directory')->description('The directory where the web server serves the site.')->default(fn (Context $c): string => ($c->answers['hosting_provider'] ?? NULL) === HostingProvider::ACQUIA ? Webroot::DOCROOT : Webroot::WEB)->required()->weight(10);
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p): void {
        $p->description('How code is shipped to the hosting environment.');
        $p->multiselect('deploy_types', 'Deployment types')
          ->description('One or more deployment mechanisms.')
          ->default(fn (Context $c): array => match ($c->answers['hosting_provider'] ?? NULL) { HostingProvider::LAGOON => [DeployTypes::LAGOON], HostingProvider::ACQUIA => [DeployTypes::ARTIFACT], default => [DeployTypes::WEBHOOK] })
          ->options(DeployTypes::options())
          ->weight(170);
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p): void {
        $p->description('Provisioning method and database source.');
        $p->select('provision_type', 'Provision type')
          ->description('How the site is provisioned: from a database dump or installed from a profile.')
          ->default(ProvisionType::DATABASE)
          ->options(ProvisionType::options())
          ->weight(150);
        $p->select('database_fetch_source', 'Database source')
          ->description('Where the database dump is fetched from.')
          ->default(fn (Context $c): string => match ($c->answers['hosting_provider'] ?? NULL) { HostingProvider::ACQUIA => DatabaseFetchSource::ACQUIA, HostingProvider::LAGOON => DatabaseFetchSource::LAGOON, default => DatabaseFetchSource::URL })
          ->when(new Condition('provision_type', eq: ProvisionType::DATABASE))
          ->options(DatabaseFetchSource::options())
          ->weight(140);
        $p->text('database_image', 'Database container image name and tag')
          ->description('Use the "latest" tag for the latest version.')
          ->when(new Condition('database_fetch_source', eq: DatabaseFetchSource::CONTAINER_REGISTRY))
          ->derive(new Derive('{{org_machine_name}}/{{machine_name}}-data:latest', 'lower'))
          ->weight(130);
        $p->confirm('migration', 'Use a second database for migrations?')->description('Adds a second database service for Drupal migrations.')->default(FALSE)->weight(120);
        $p->select('migration_fetch_source', 'Migration database source')
          ->description('Where the migration database dump is fetched from.')
          ->default(fn (Context $c): string => match ($c->answers['hosting_provider'] ?? NULL) { HostingProvider::ACQUIA => MigrationFetchSource::ACQUIA, HostingProvider::LAGOON => MigrationFetchSource::LAGOON, default => MigrationFetchSource::URL })
          ->when(new Condition('migration', eq: TRUE))
          ->options(MigrationFetchSource::options())
          ->weight(110);
        $p->text('migration_image', 'Migration database container image name and tag')
          ->description('Use the "latest" tag for the latest version.')
          ->when(new Condition('migration_fetch_source', eq: MigrationFetchSource::CONTAINER_REGISTRY))
          ->derive(new Derive('{{org_machine_name}}/{{machine_name}}-data-migration:latest', 'lower'))
          ->weight(100);
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p): void {
        $p->description('Where build and deployment notifications are sent.');
        $p->multiselect('notification_channels', 'Notification channels')
          ->description('One or more notification channels.')
          ->default([NotificationChannels::EMAIL])
          ->options(NotificationChannels::options())
          ->weight(160);
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p): void {
        $p->description('CI provider and visual regression testing.');
        $p->select('ci_provider', 'Continuous Integration provider')
          ->description('The CI provider for the project.')
          ->default(CiProvider::GITHUB_ACTIONS)
          ->options(CiProvider::options())
          ->weight(90);
        $p->confirm('visual_regression', 'Visual regression testing with Diffy?')->description('Requires a Diffy account.')->default(FALSE)->weight(80);
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p): void {
        $p->description('Dependency updates, coverage and PR automation.');
        $p->select('dependency_updates_provider', 'Dependency updates provider')
          ->description('The dependency updates provider.')
          ->default(DependencyUpdatesProvider::RENOVATEBOT_APP)
          ->options(DependencyUpdatesProvider::options())
          ->weight(70);
        $p->select('code_coverage_provider', 'Code coverage provider')
          ->description('The code coverage provider.')
          ->default(CodeCoverageProvider::NONE)
          ->options(CodeCoverageProvider::options())
          ->weight(60);
        $p->confirm('assign_author_pr', 'Auto-assign the author to their PR?')->description('Helps to keep the PRs organized.')->default(TRUE)->weight(50);
        $p->confirm('label_merge_conflicts_pr', 'Auto-add a CONFLICT label to a PR when conflicts occur?')->description('Helps to quickly identify PRs that need attention.')->default(TRUE)->weight(40);
      })
      ->panel('documentation', 'Documentation', function (PanelBuilder $p): void {
        $p->description('Whether project documentation is kept.');
        $p->confirm('preserve_docs_project', 'Preserve project documentation?')->description('Helps to maintain the project documentation within the repository.')->default(TRUE)->weight(30);
      })
      ->panel('ai', 'AI', function (PanelBuilder $p): void {
        $p->description('Whether AI agent instructions are included.');
        $p->confirm('ai_code_instructions', 'Provide AI agent instructions?')->description('Provides AI coding agents with better context about the project.')->default(TRUE)->weight(20);
      })
      ->build();
  }

}
