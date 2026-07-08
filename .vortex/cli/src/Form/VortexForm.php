<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Config;

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
      ->panel('general', 'General information', function (PanelBuilder $p): void {
        $p->description('Project name, organization and public domain.');
        $p->text('name', 'Site name')->description('We will use this name in the project and documentation.')->required()->weight(380);
        $p->text('machine_name', 'Site machine name')
          ->description('We will use this name for the project directory and in the code.')
          ->required()
          ->derive(['template' => '{{name}}', 'transform' => 'machine'])
          ->weight(360);
        $p->text('org', 'Organization name')
          ->description('We will use this name in the project and documentation.')
          ->required()
          ->derive(['template' => '{{name}} Org'])
          ->weight(370);
        $p->text('org_machine_name', 'Organization machine name')
          ->description('We will use this name in the code.')
          ->required()
          ->derive(['template' => '{{org}}', 'transform' => 'machine'])
          ->weight(350);
        $p->text('domain', 'Public domain')
          ->description('Domain name without protocol and trailing slash.')
          ->required()
          ->derive(['template' => '{{machine_name}}.com', 'transform' => 'host'])
          ->weight(280);
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->description('Install profile, modules, theme and front-end build.');
        $p->select('starter', 'How would you like your site to be created on the first run?')
          ->description('Applies only on the first run of the installer.')
          ->default('load_demodb')
          ->options([
            'install_profile_core' => 'Drupal, installed from profile',
            'install_profile_drupalcms' => 'Drupal CMS, installed from profile',
            'load_demodb' => 'Drupal, loaded from the demo database',
          ])
          ->weight(250);
        $p->select('profile', 'Profile')
          ->description('The Drupal installation profile the site is built on.')
          ->default('standard')->required()
          ->options([
            'standard' => 'Standard',
            'minimal' => 'Minimal',
            'demo_umami' => 'Demo Umami',
            'custom' => 'Custom (next prompt)',
          ])
          ->weight(270);
        $p->text('profile_custom', 'Custom profile machine name')
          ->description('The machine name of your custom profile.')
          ->required()
          ->when(['field' => 'profile', 'eq' => 'custom'])
          ->weight(260);
        $p->multiselect('modules', 'Modules')
          ->description('Optional contributed modules to include.')
          ->default([
            'admin_toolbar', 'coffee', 'config_split', 'config_update', 'devel', 'drupal_helpers',
            'environment_indicator', 'generated_content', 'pathauto', 'redirect', 'reroute_email',
            'robotstxt', 'sdc_devel', 'seckit', 'shield', 'stage_file_proxy', 'testmode', 'xmlsitemap',
          ])
          ->options([
            'admin_toolbar' => 'Admin toolbar',
            'coffee' => 'Coffee',
            'config_split' => 'Config split',
            'config_update' => 'Config update',
            'devel' => 'Devel',
            'drupal_helpers' => 'Drupal helpers',
            'environment_indicator' => 'Environment indicator',
            'generated_content' => 'Generated content',
            'pathauto' => 'Pathauto',
            'redirect' => 'Redirect',
            'reroute_email' => 'Reroute email',
            'robotstxt' => 'Robots.txt',
            'sdc_devel' => 'SDC Devel',
            'seckit' => 'Seckit',
            'shield' => 'Shield',
            'stage_file_proxy' => 'Stage file proxy',
            'testmode' => 'Testmode',
            'xmlsitemap' => 'XML Sitemap',
          ])
          ->weight(240);
        $p->text('module_prefix', 'Custom modules prefix')
          ->description('We will use this name in custom modules.')
          ->required()
          ->derive(['template' => '{{machine_name}}', 'transform' => 'initials'])
          ->weight(310);
        $p->multiselect('custom_modules', 'Custom modules')
          ->description('Which scaffolded custom modules to keep.')
          ->default(['base', 'search', 'demo'])
          ->options([
            'base' => 'Base - starter module with utilities and test scaffolding',
            'search' => 'Search - custom Solr search integration',
            'demo' => 'Demo - counter block and example tests to demonstrate tooling',
          ])
          ->weight(300);
        $p->select('theme', 'Theme')
          ->description('The base theme for the site front-end.')
          ->default('custom')->required()
          ->options([
            'olivero' => 'Olivero',
            'claro' => 'Claro',
            'stark' => 'Stark',
            'custom' => 'Custom (next prompt)',
          ])
          ->weight(340);
        $p->text('theme_custom', 'Custom theme machine name')
          ->description('We will use this name as a custom theme name.')
          ->required()
          ->when(['field' => 'theme', 'eq' => 'custom'])
          ->derive(['template' => '{{machine_name}}', 'transform' => 'machine'])
          ->weight(330);
        $p->confirm('frontend_build', 'Build front-end assets in the container?')
          ->description('Disable to build theme assets on the host or as part of deployment.')
          ->default(TRUE)
          ->when(['field' => 'theme', 'eq' => 'custom'])
          ->weight(320);
      })
      ->panel('code_repository', 'Code repository', function (PanelBuilder $p): void {
        $p->description('Where the code lives and how releases are versioned.');
        $p->select('code_provider', 'Repository provider')
          ->description('Vortex offers full automation with GitHub; support for other providers is limited.')
          ->default('github')
          ->options(['github' => 'GitHub', 'other' => 'Other'])
          ->weight(230);
        $p->select('version_scheme', 'Release versioning scheme')
          ->description('CalVer (year.month.patch) or SemVer (major.minor.patch).')
          ->default('calver')
          ->options([
            'calver' => 'Calendar Versioning (CalVer)',
            'semver' => 'Semantic Versioning (SemVer)',
            'other' => 'Other',
          ])
          ->weight(220);
      })
      ->panel('environment', 'Environment', function (PanelBuilder $p): void {
        $p->description('Timezone, Docker services and developer tooling.');
        $p->suggest('timezone', 'Timezone')
          ->description('Start typing to select the timezone for your project.')
          ->default('UTC')
          ->option('UTC')->option('Africa/Johannesburg')->option('America/Chicago')->option('America/Los_Angeles')
          ->option('America/New_York')->option('America/Sao_Paulo')->option('America/Toronto')->option('Asia/Dubai')
          ->option('Asia/Hong_Kong')->option('Asia/Kolkata')->option('Asia/Singapore')->option('Asia/Tokyo')
          ->option('Australia/Melbourne')->option('Australia/Sydney')->option('Europe/Amsterdam')->option('Europe/Berlin')
          ->option('Europe/London')->option('Europe/Madrid')->option('Europe/Paris')->option('Pacific/Auckland')
          ->weight(210);
        $p->multiselect('services', 'Services')
          ->description('Optional Docker services to include.')
          ->default(['clamav', 'redis', 'solr'])
          ->options(['clamav' => 'ClamAV', 'solr' => 'Solr', 'redis' => 'Redis'])
          ->weight(200);
        $p->multiselect('tools', 'Development tools')
          ->description('Linting and testing tools to keep.')
          ->default(['behat', 'eslint', 'jest', 'phpcs', 'phpstan', 'phpunit', 'rector', 'stylelint'])
          ->options([
            'phpcs' => 'PHP CodeSniffer',
            'phpstan' => 'PHPStan',
            'rector' => 'Rector',
            'eslint' => 'ESLint',
            'stylelint' => 'Stylelint',
            'phpunit' => 'PHPUnit',
            'behat' => 'Behat',
            'jest' => 'Jest',
          ])
          ->weight(190);
      })
      ->panel('hosting', 'Hosting', function (PanelBuilder $p): void {
        $p->description('Target hosting provider and project name.');
        $p->select('hosting_provider', 'Hosting provider')
          ->description('The hosting provider for the project.')
          ->default('none')->required()
          ->options([
            'acquia' => 'Acquia Cloud',
            'lagoon' => 'Lagoon',
            'other' => 'Other',
            'none' => 'None',
          ])
          ->weight(180);
        $p->text('hosting_project_name', 'Hosting project name')
          ->description('Name as found in the hosting configuration; usually the site machine name.')
          ->required()
          ->when(['field' => 'hosting_provider', 'in' => ['lagoon', 'acquia']])
          ->derive(['template' => '{{machine_name}}'])
          ->weight(290);
        $p->text('webroot', 'Custom web root directory')->description('The directory where the web server serves the site.')->default('web')->required()->weight(10);
      })
      ->panel('deployment', 'Deployment', function (PanelBuilder $p): void {
        $p->description('How code is shipped to the hosting environment.');
        $p->multiselect('deploy_types', 'Deployment types')
          ->description('One or more deployment mechanisms.')
          ->default(['webhook'])
          ->options([
            'artifact' => 'Code artifact',
            'lagoon' => 'Lagoon webhook',
            'webhook' => 'Custom webhook',
          ])
          ->weight(170);
      })
      ->panel('workflow', 'Workflow', function (PanelBuilder $p): void {
        $p->description('Provisioning method and database source.');
        $p->select('provision_type', 'Provision type')
          ->description('How the site is provisioned: from a database dump or installed from a profile.')
          ->default('database')
          ->options(['database' => 'Import from database dump', 'profile' => 'Install from profile'])
          ->weight(150);
        $p->select('database_fetch_source', 'Database source')
          ->description('Where the database dump is fetched from.')
          ->default('url')->when(['field' => 'provision_type', 'eq' => 'database'])
          ->options([
            'url' => 'URL download',
            'ftp' => 'FTP download',
            'acquia' => 'Acquia backup',
            'lagoon' => 'Lagoon environment',
            'container_registry' => 'Container registry',
            's3' => 'S3 bucket',
            'none' => 'None',
          ])
          ->weight(140);
        $p->text('database_image', 'Database container image name and tag')
          ->description('Use the "latest" tag for the latest version.')
          ->when(['field' => 'database_fetch_source', 'eq' => 'container_registry'])
          ->derive(['template' => '{{org_machine_name}}/{{machine_name}}-data:latest', 'transform' => 'lower'])
          ->weight(130);
        $p->confirm('migration', 'Use a second database for migrations?')->description('Adds a second database service for Drupal migrations.')->default(FALSE)->weight(120);
        $p->select('migration_fetch_source', 'Migration database source')
          ->description('Where the migration database dump is fetched from.')
          ->default('url')->when(['field' => 'migration', 'eq' => TRUE])
          ->options([
            'url' => 'URL download',
            'ftp' => 'FTP download',
            'acquia' => 'Acquia backup',
            'lagoon' => 'Lagoon environment',
            'container_registry' => 'Container registry',
            's3' => 'S3 bucket',
          ])
          ->weight(110);
        $p->text('migration_image', 'Migration database container image name and tag')
          ->description('Use the "latest" tag for the latest version.')
          ->when(['field' => 'migration_fetch_source', 'eq' => 'container_registry'])
          ->derive(['template' => '{{org_machine_name}}/{{machine_name}}-data-migration:latest', 'transform' => 'lower'])
          ->weight(100);
      })
      ->panel('notifications', 'Notifications', function (PanelBuilder $p): void {
        $p->description('Where build and deployment notifications are sent.');
        $p->multiselect('notification_channels', 'Notification channels')
          ->description('One or more notification channels.')
          ->default(['email'])
          ->options([
            'email' => 'Email',
            'github' => 'GitHub',
            'jira' => 'JIRA',
            'newrelic' => 'New Relic',
            'slack' => 'Slack',
            'webhook' => 'Webhook',
          ])
          ->weight(160);
      })
      ->panel('continuous_integration', 'Continuous Integration', function (PanelBuilder $p): void {
        $p->description('CI provider and visual regression testing.');
        $p->select('ci_provider', 'Continuous Integration provider')
          ->description('The CI provider for the project.')
          ->default('gha')
          ->options(['gha' => 'GitHub Actions', 'circleci' => 'CircleCI', 'none' => 'None'])
          ->weight(90);
        $p->confirm('visual_regression', 'Visual regression testing with Diffy?')->description('Requires a Diffy account.')->default(FALSE)->weight(80);
      })
      ->panel('automations', 'Automations', function (PanelBuilder $p): void {
        $p->description('Dependency updates, coverage and PR automation.');
        $p->select('dependency_updates_provider', 'Dependency updates provider')
          ->description('The dependency updates provider.')
          ->default('renovatebot_app')
          ->options([
            'renovatebot_app' => 'Renovate GitHub app',
            'renovatebot_ci' => 'Renovate self-hosted in CI',
            'none' => 'None',
          ])
          ->weight(70);
        $p->select('code_coverage_provider', 'Code coverage provider')
          ->description('The code coverage provider.')
          ->default('none')
          ->options(['codecov' => 'Codecov', 'none' => 'None'])
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
