<?php

declare(strict_types=1);

namespace DrevOps\Installer;

use AlexSkrypnyk\Str2Name\Str2Name;
use DrevOps\Installer\Prompts\InstallerFormBuilder;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class PromptManager {

  protected $config;

  public function __construct(
    protected OutputInterface $output,
  ) {
    Prompt::setOutput($output);
  }

  public function getResponses($config) {
    $this->config = $config;

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    $responses = (new InstallerFormBuilder())
      ->intro('General information')

      ->add(fn($r, $pr, $n) => text(
        label: '🔖 Site name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Site',
        required: TRUE,
        default: $this->default($n, Str2Name::label(static::getEnvOrDefault('VORTEX_PROJECT', basename((string) $this->config->getDstDir())))),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid name' : NULL,
      ), 'name')

      ->add(fn($r, $pr, $n) => text(
        label: '🔖 Site machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_site',
        required: TRUE,
        default: $this->default($n, Str2Name::machine($r['name'])),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), 'machine_name')

      ->add(fn($r, $pr, $n) => text(
        label: '🏢 Organization name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Org',
        required: TRUE,
        default: $this->default('org', Str2Name::label($r['name']) . ' Org'),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid organization name' : NULL,
      ), 'org')

      ->add(fn($r, $pr, $n) => text(
        label: '🏢 Organization machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_org',
        required: TRUE,
        default: $this->default($n, Str2Name::machine($r['org'])),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), 'org_machine_name')

      ->add(fn($r, $pr, $n) => text(
        label: '🌐 Public domain',
        hint: 'Domain name without protocol and trailing slash.',
        placeholder: 'E.g. example.com',
        required: TRUE,
        default: $this->default($n, 'http://' . Str2Name::kebab($r['machine_name']) . '.com'),
        transform: fn(string $v) => Converter::toDomain($v),
        validate: fn($v) => filter_var($v, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === FALSE ? 'Please enter a valid domain name' : NULL,
      ), 'domain')

      ->intro('Code repository')

      ->add(fn($r, $pr, $n) => select(
        label: '⚙️ Repository provider',
        hint: 'Vortex offers full automation with GitHub, while support for other providers is limited.',
        options: [
          'github' => 'GitHub',
          'other' => 'Other',
        ],
        default: $this->default($n, 'github'),
      ), 'code_provider')

        ->addIf(
          fn($r) => $r['code_provider'] === 'github',
          fn($r, $pr, $n) => note("<info>We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.</info>"),
          'github_token_note'
        )
        ->addIf(
          fn($r) => $r['code_provider'] === 'github',
          fn($r, $pr, $n) => text(
            label: '🔑 GitHub personal access token (optional)',
            hint: static::getEnvOrDefault('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new',
            placeholder: 'E.g. ghp_1234567890',
            default: $this->default($n, static::getEnvOrDefault('GITHUB_TOKEN')),
            transform: fn(string $v) => trim($v),
            validate: fn($v) => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : NULL,
          ), 'github_token')

            ->addIf(
              fn($r) => !empty($r['github_token']),
              fn($r, $pr, $n) => text(
                label: 'What is your GitHub project name?',
                hint: 'We will use this name to create new or find an existing repository.',
                placeholder: 'E.g. myorg/myproject',
                default: $this->default($n, $r['org_machine_name'] . '/' . $r['machine_name']),
                transform: fn(string $v) => trim($v),
                validate: fn(string $v) => match (TRUE) {
                  empty($v) => 'Please enter a project name',
                  !str_contains($v, '/') || (count(explode('/', $v)) !== 2 || empty(explode('/', $v)[0]) || empty(explode('/', $v)[1])) => 'Please enter a valid project name in the format "myorg/myproject"',
                  default => NULL,
                },
              ), 'github_repo')

      ->intro('Drupal')

      ->add(fn($r, $pr, $n) => confirm(
        label: 'Use a custom profile?',
        hint: 'Select "yes" to use a custom profile, or "no" to use the "standard" profile.',
        default: $this->default($n, FALSE),
      ), 'use_custom_profile')

        ->addIf(
          fn($r) => $r['use_custom_profile'],
          fn($r, $pr, $n) => text(
            label: 'Custom profile machine name',
            hint: 'Leave empty to use "standard" profile.',
            placeholder: 'E.g. my_profile',
            required: TRUE,
            default: $this->default($n, 'standard'),
            transform: fn(string $v) => trim($v),
            validate: fn($v) => match (TRUE) {
              !empty($v) && Converter::toAbbreviation($v) !== $v => 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
              default => 'standard',
            },
          ), 'profile')

      ->add(fn($r, $pr, $n) => text(
        label: '🧩 Module prefix',
        hint: 'We will use this name for custom modules.',
        placeholder: 'E.g. ms (for My Site)',
        required: TRUE,
        default: $this->default($n, Converter::toAbbreviation($r['machine_name'])),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Converter::toAbbreviation($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), 'module_prefix')

      ->add(fn($r, $pr, $n) => text(
        label: '🎨 Theme machine name',
        hint: 'We will use this name for the theme directory.',
        placeholder: 'E.g. mytheme',
        required: TRUE,
        default: $this->default($n, $r['machine_name']),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), 'theme')

      ->intro('Hosting')

      ->add(fn($r, $pr, $n) => select(
        label: '🏠 Hosting',
        hint: 'Select the hosting provider where the project is hosted. The web root directory will be set accordingly.',
        options: [
          'acquia' => '💧 Acquia Cloud',
          'lagoon' => '🌊 Lagoon',
          'other' => '🧩 Other',
        ],
        required: TRUE,
        default: $this->default($n, NULL),
      ), 'hosting_provider')

        ->addIf(
          fn($r) => $r['hosting_provider'] !== 'other',
          fn($r, $pr, $n) => info(sprintf('Web root will be set to "%s".', match ($r['hosting_provider']) {
            'acquia' => 'docroot',
            'lagoon' => 'web',
            default => $this->default($n, 'web'),
          })), 'webroot_note')

        ->addIf(
          fn($r) => $r['hosting_provider'] === 'other',
          fn($r, $pr, $n) => text(
            label: 'Custom web root directory',
            hint: 'Custom directory where the web server serves the site.',
            placeholder: 'E.g. public',
            required: TRUE,
            transform: fn(string $v) => !empty(trim($v)) ? Converter::toPath($v) : trim($v),
            validate: fn($v) => empty($v) ? 'Please enter a valid directory name' : NULL,
          ), 'webroot_custom')

      ->intro('Deployment')

      ->add(function ($r, $pr, $n) {
        $defaults = [];

        $options = [
          'artifact' => '📦 Code artifact',
          'lagoon' => '🌊 Lagoon webhook',
          'container_image' => '🐳 Container image',
          'webhook' => '🌐 Custom webhook',
        ];

        if ($r['hosting_provider'] === 'lagoon') {
          $defaults[] = 'lagoon';
        }

        if ($r['hosting_provider'] === 'acquia') {
          $defaults[] = 'artifact';
          unset($options['lagoon']);
        }

        if (empty($defaults)) {
          $defaults[] = 'webhook';
        }

        multiselect(
          label: '🚚 Deployment types',
          hint: 'You can deploy code using one or more methods.',
          options: $options,
          default: $this->default($n, $defaults),
          required: FALSE,
        );
      }, 'deploy_type')

      ->intro('Workflow')

      ->add(fn($r, $pr, $n) => note('<info>Provisioning</info> is the process of setting up the site in the environment with an already built codebase.'), 'provision_note')

      ->add(fn($r, $pr, $n) => select(
        label: 'Provision type',
        hint: 'Selecting "Profile" will install site from a profile rather than a database dump.',
        options: [
          'database' => 'Database dump',
          'profile' => 'Install from profile',
        ],
        default: $this->default($n, 'database'),
      ), 'provision_type')

        ->addIf(
          fn($r) => $r['provision_type'] === 'database',
          function ($r, $pr, $n) {
            $options = [
              'url' => '🌍 URL download',
              'ftp' => '📂 FTP download',
              'acquia' => '💧 Acquia backup',
              'lagoon' => '🌊 Lagoon environment',
              'container_registry' => '🐳 Container registry',
            ];

            if ($r['hosting_provider'] === 'acquia') {
              unset($options['lagoon']);
            }

            if ($r['hosting_provider'] === 'lagoon') {
              unset($options['acquia']);
            }

            select(
              label: 'Database dump source',
              hint: 'Database can be downloaded as a dump file or stored in a container image.',
              options: $options,
              default: $this->default($n, match ($r['hosting_provider']) {
                'acquia' => 'acquia',
                'lagoon' => 'lagoon',
                default => 'url',
              }),
            );
          }, 'database_download_source')

            ->addIf(
              fn($r) => $r['database_download_source'] === 'container_registry',
              fn($r, $pr, $n) => select(
                label: 'Database store type for local development',
                hint: 'Importing databases larger than 1GB from a file takes longer, so you can store the database in a container image for faster builds.',
                options: [
                  'file' => 'File',
                  'container_image' => 'Container image',
                ],
                default: $this->default($n, 'file'),
              ), 'database_store_type')

              ->addIf(
                fn($r) => $r['database_store_type'] === 'container_image',
                fn($r, $pr, $n) => text(
                  label: 'What is your database container image name and a tag?',
                  hint: 'Use "latest" for the latest version. CI will be building this image overnight.',
                  placeholder: 'E.g. drevops/mariadb-drupal-data:latest',
                  default: $this->default($n, 'drevops/mariadb-drupal-data:latest'),
                  transform: fn($v) => !empty(trim($v)) ? Converter::toContainerImage(trim($v)) : trim($v),
                  validate: fn($v) => empty(trim($v)) || substr_count($v, ':') > 1 ? 'Please enter a valid image name and a tag' : NULL,
                ), 'database_store_type')

      ->intro('Continuous Integration')

      ->add(function ($r, $pr, $n) {
        $options = [
          'gha' => 'GitHub Actions',
          'circleci' => 'CircleCI',
          'none' => 'None',
        ];

        if ($r['code_provider'] !== 'github') {
          unset($options['gha']);
        }

        select(
          label: '🔁 Continuous Integration provider',
          hint: 'Both providers support equivalent workflow.',
          options: $options,
          default: $this->default($n, 'gha'),
        );
      }, 'ci_provider')

      ->intro('Automations')

      ->add(fn($r, $pr, $n) => select(
        label: '🔄 Dependency updates provider',
        hint: 'Use a self-hosted service if you can’t install a GitHub app.',
        options: [
          'renovatebot_ci' => '🤖 + 🔁 Renovate self-hosted in CI',
          'renovatebot_app' => '🤖 Renovate GitHub app',
          'none' => 'None',
        ],
        default: $this->default($n, 'renovatebot_ci'),
      ), 'dependency_updates_provider')

      ->add(fn($r, $pr, $n) => confirm(
        label: '👤 Auto-assign the author to their PR?',
        hint: 'Helps to keep the PRs organized.',
        default: $this->default($n, TRUE),
      ), 'assign_author_pr')

      ->add(fn($r, $pr, $n) => confirm(
        label: '🎫 Auto-add a <info>CONFLICT</info> label to a PR when conflicts occur?',
        hint: 'Helps to keep quickly identify PRs that need attention.',
        default: $this->default($n, TRUE),
      ), 'label_merge_conflicts_pr')

      ->intro('Documentation')

      ->add(fn($r, $pr, $n) => confirm(
        label: '📚 Preserve project documentation?',
        hint: 'Helps to maintain the project documentation within the repository.',
        default: $this->default($n, TRUE),
      ), 'preserve_project_docs')

      ->add(fn($r, $pr, $n) => confirm(
        label: '📋 Preserve onboarding checklist?',
        hint: 'Helps to track onboarding to Vortex within the repository.',
        default: $this->default($n, TRUE),
      ), 'preserve_onboarding')

      ->submit();

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    // filter out elements with numeric keys
    $responses = array_filter($responses, function ($key) {
      return !is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);

    return $responses;
  }

  protected function default($name, $default = NULL) {
    // @todo Implement this.
    return $default;
  }

  /**
   * Reliable wrapper to work with environment values.
   */
  protected static function getEnvOrDefault(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] === '') {
      return $default;
    }

    return $vars[$name];
  }

}
