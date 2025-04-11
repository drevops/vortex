<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts;

use DrevOps\Installer\Prompts\Handlers\AbstractHandler;
use DrevOps\Installer\Prompts\Handlers\AssignAuthorPr;
use DrevOps\Installer\Prompts\Handlers\CiProvider;
use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\Installer\Prompts\Handlers\DatabaseImage;
use DrevOps\Installer\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\Installer\Prompts\Handlers\DeployType;
use DrevOps\Installer\Prompts\Handlers\Domain;
use DrevOps\Installer\Prompts\Handlers\GithubRepo;
use DrevOps\Installer\Prompts\Handlers\GithubToken;
use DrevOps\Installer\Prompts\Handlers\HandlerInterface;
use DrevOps\Installer\Prompts\Handlers\HostingProvider;
use DrevOps\Installer\Prompts\Handlers\Internal;
use DrevOps\Installer\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\Installer\Prompts\Handlers\MachineName;
use DrevOps\Installer\Prompts\Handlers\ModulePrefix;
use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\Handlers\Org;
use DrevOps\Installer\Prompts\Handlers\OrgMachineName;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsProject;
use DrevOps\Installer\Prompts\Handlers\Profile;
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Services;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\ThemeRunner;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Tui;
use DrevOps\Installer\Utils\Validator;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * PromptManager.
 *
 * Centralised place for providing prompts and their processing.
 *
 * @package DrevOps\Installer
 */
class PromptManager {

  /**
   * Array of responses.
   */
  protected array $responses = [];

  /**
   * Array of handlers.
   *
   * @var array<string, \DrevOps\Installer\Prompts\Handlers\HandlerInterface>
   */
  protected array $handlers = [];

  /**
   * PromptManager constructor.
   *
   * @param \DrevOps\Installer\Utils\Config $config
   *   The installer config.
   */
  public function __construct(
    protected Config $config,
  ) {
    $this->initHandlers();
  }

  /**
   * Prompt for responses.
   *
   * If non-interactive mode is used, the values provided by $this->default()
   * method, including discovery from the existing codebase, will be used.
   */
  public function prompt(): void {
    $original_verbosity = Tui::output()->getVerbosity();
    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    $responses = form()
      ->intro('General information')

      ->add(fn($r, $pr, $n): string => text(
        label: '🔖 Site name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Site',
        required: TRUE,
        default: $this->default($n, Converter::label(Env::get('VORTEX_PROJECT', basename((string) $this->config->getDst())))),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => Converter::label($v) !== $v ? 'Please enter a valid project name.' : NULL,
      ), Name::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🔖 Site machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_site',
        required: TRUE,
        default: $this->default($n, Converter::machine($r['name'])),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => Converter::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), MachineName::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🏢 Organization name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Org',
        required: TRUE,
        default: $this->default('org', Converter::label($r['name']) . ' Org'),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => Converter::label($v) !== $v ? 'Please enter a valid organization name.' : NULL,
      ), Org::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🏢 Organization machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_org',
        required: TRUE,
        default: $this->default($n, Converter::machine($r['org'])),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => Converter::machine($v) !== $v ? 'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), OrgMachineName::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🌐 Public domain',
        hint: 'Domain name without protocol and trailing slash.',
        placeholder: 'E.g. example.com',
        required: TRUE,
        default: $this->default($n, Converter::kebab($r['machine_name']) . '.com'),
        transform: fn(string $v): string => Converter::domain($v),
        validate: fn($v): ?string => Validator::domain($v) ? NULL : 'Please enter a valid domain name.',
      ), Domain::id())

      ->intro('Code repository')

      ->add(fn($r, $pr, $n): int|string => select(
        label: '⚙️ Repository provider',
        hint: 'Vortex offers full automation with GitHub, while support for other providers is limited.',
        options: [
          CodeProvider::GITHUB => 'GitHub',
          CodeProvider::OTHER => 'Other',
        ],
        default: $this->default($n, 'github'),
      ), CodeProvider::id())

      ->addIf(
          fn($r): bool => $r['code_provider'] === 'github',
          fn($r, $pr, $n) => Tui::note("<info>We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.</info>"),
        )

      ->addIf(
          fn($r): bool => $r['code_provider'] === 'github',
          function ($r, $pr, $n): string {
            $value = $this->default($n);
            if (!empty($value)) {
              Tui::ok('GitHub access token is already set in the environment.');
              return $value;
            }

            return password(
              label: '🔑 GitHub access token (optional)',
              hint: Env::get('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new',
              placeholder: 'E.g. ghp_1234567890',
              transform: fn(string $v): string => trim($v),
              validate: fn($v): ?string => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : NULL,
            );
          }, GithubToken::id())

      ->addIf(
              fn($r): bool => !empty($r['github_token']),
              fn($r, $pr, $n): string => text(
                label: 'What is your GitHub project name?',
                hint: 'We will use this name to create new or find an existing repository.',
                placeholder: 'E.g. myorg/myproject',
                default: $this->default($n, $r['org_machine_name'] . '/' . $r['machine_name']),
                transform: fn(string $v): string => trim($v),
                validate: fn(string $v): ?string => !empty($v) && !Validator::githubProject($v) ? 'Please enter a valid project name in the format "myorg/myproject"' : NULL,
              ), GithubRepo::id())

      ->intro('Drupal')

      ->add(
        function ($r, $pr, $n): int|string {
          $profile = select(
            label: 'Profile',
            hint: 'Select which profile to use',
            options: [
              Profile::STANDARD => 'Standard',
              Profile::MINIMAL => 'Minimal',
              Profile::DEMO_UMAMI => 'Demo Umami',
              Profile::CUSTOM => 'Custom',
            ],
            required: TRUE,
            default: empty($this->default($n)) ? Profile::STANDARD : Profile::CUSTOM,
          );

          if ($profile === Profile::CUSTOM) {
            $profile = text(
              label: 'Custom profile machine name',
              placeholder: 'E.g. my_profile',
              required: TRUE,
              default: $this->default($n),
              transform: fn(string $v): string => trim($v),
              validate: fn(string $v): ?string => !empty($v) && Converter::machine($v) !== $v ? 'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
            );
          }

          return $profile;
        }, Profile::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🧩 Module prefix',
        hint: 'We will use this name for custom modules.',
        placeholder: 'E.g. ms (for My Site)',
        required: TRUE,
        default: $this->default($n, Converter::abbreviation($r['machine_name'], 4, ['_'])),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => Converter::machine($v) !== $v ? 'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), ModulePrefix::id())

      ->add(fn($r, $pr, $n): string => text(
        label: '🎨 Theme machine name',
        hint: 'We will use this name for the theme directory. Leave empty to skip the theme scaffold.',
        placeholder: 'E.g. mytheme',
        default: $this->default($n, $r['machine_name']),
        transform: fn(string $v): string => trim($v),
        validate: fn($v): ?string => !empty($v) && Converter::machine($v) !== $v ? 'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), Theme::id())

      ->addIf(
        fn($r): bool => !empty($r[Theme::id()]),
        fn($r, $pr, $n): int|string => select(
          label: 'Compile theme assest during build using a task runner?',
          hint: 'Useful to avoid committing compiled theme assets to the repository.',
          options: [
            ThemeRunner::GRUNT => '🐗 Grunt',
            ThemeRunner::GULP => '🥤 Gulp',
            ThemeRunner::WEBPACK => '📦 Webpack',
            ThemeRunner::NONE => '⭕  None',
          ],
          required: TRUE,
          default: $this->default($n, ThemeRunner::GRUNT),
        ), ThemeRunner::id())

      ->intro('Services')

      ->add(fn($r, $pr, $n): array => multiselect(
        label: '🔌 Services',
        hint: 'Select the services you want to use in the project.',
        options: [
          Services::CLAMAV => '🦠 ClamAV',
          Services::SOLR => '🔍 Solr',
          Services::REDIS => '🔴 Redis',
        ],
        default: $this->default($n, [Services::CLAMAV, Services::REDIS, Services::SOLR]),
      ), Services::id())

      ->intro('Hosting')

      ->add(fn($r, $pr, $n): int|string => select(
        label: '🏠 Hosting provider',
        hint: 'Select the hosting provider where the project is hosted. The web root directory will be set accordingly.',
        options: [
          HostingProvider::NONE => '⭕  None',
          HostingProvider::ACQUIA => '💧 Acquia Cloud',
          HostingProvider::LAGOON => '🌊 Lagoon',
          HostingProvider::OTHER => '🧩 Other',
        ],
        required: TRUE,
        default: $this->default($n, 'none'),
      ), HostingProvider::id())

      ->add(
          function (array $r, $pr, $n): string|bool|array {
            if ($r[HostingProvider::id()] !== HostingProvider::OTHER) {
              $webroot = match ($r[HostingProvider::id()]) {
                HostingProvider::ACQUIA => Webroot::DOCROOT,
                HostingProvider::LAGOON => Webroot::WEB,
                default => $this->default($n, Webroot::WEB)
              };

              info(sprintf('Web root will be set to "%s".', $webroot));
            }
            else {
              $webroot = text(
                label: '📁 Custom web root directory',
                hint: 'Custom directory where the web server serves the site.',
                placeholder: 'E.g. ' . implode(', ', [Webroot::WEB, Webroot::DOCROOT]),
                required: TRUE,
                default: $this->default($n, Webroot::WEB),
                transform: fn(string $v): string => rtrim($v, DIRECTORY_SEPARATOR),
                validate: fn($v): ?string => Validator::dirname($v) ? NULL : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
              );
            }
            return $webroot;
          }, Webroot::id())

      ->intro('Deployment')

      ->add(function (array $r, $pr, $n): array {
              $defaults = [];

              $options = [
                DeployType::ARTIFACT => '📦 Code artifact',
                DeployType::LAGOON => '🌊 Lagoon webhook',
                DeployType::CONTAINER_IMAGE => '🐳 Container image',
                DeployType::WEBHOOK => '🌐 Custom webhook',
              ];

              if ($r[HostingProvider::id()] === HostingProvider::LAGOON) {
                $defaults[] = DeployType::LAGOON;
              }

              if ($r[HostingProvider::id()] === HostingProvider::ACQUIA) {
                $defaults[] = DeployType::ARTIFACT;
                unset($options[DeployType::LAGOON]);
              }

              if (empty($defaults)) {
                $defaults[] = DeployType::WEBHOOK;
              }

              return multiselect(
              label: '🚚 Deployment types',
              hint: 'You can deploy code using one or more methods.',
              options: $options,
              default: $this->default($n, $defaults),
              );
      }, DeployType::id())

      ->intro('Workflow')

      ->add(fn($r, $pr, $n) => Tui::note('<info>Provisioning</info> is the process of setting up the site in the environment with an already built codebase.'))

      ->add(fn($r, $pr, $n): int|string => select(
        label: 'Provision type',
        hint: 'Selecting "Profile" will install site from a profile rather than a database dump.',
        options: [
          ProvisionType::DATABASE => 'Import from database dump',
          ProvisionType::PROFILE => 'Install from profile',
        ],
        default: $this->default($n, ProvisionType::DATABASE),
      ), ProvisionType::id())

      ->add(function (array $r, $pr, $n): int|string {
        if ($r[ProvisionType::id()] === ProvisionType::PROFILE) {
          return DatabaseDownloadSource::NONE;
        }

              $options = [
                DatabaseDownloadSource::URL => '🌍 URL download',
                DatabaseDownloadSource::FTP => '📂 FTP download',
                DatabaseDownloadSource::ACQUIA => '💧 Acquia backup',
                DatabaseDownloadSource::LAGOON => '🌊 Lagoon environment',
                DatabaseDownloadSource::CONTAINER_REGISTRY => '🐳 Container registry',
                DatabaseDownloadSource::NONE => '⭕  None',
              ];

              if ($r[HostingProvider::id()] === HostingProvider::ACQUIA) {
                unset($options[DatabaseDownloadSource::LAGOON]);
              }

              if ($r[HostingProvider::id()] === HostingProvider::LAGOON) {
                unset($options[DatabaseDownloadSource::ACQUIA]);
              }

              return select(
              label: 'Database dump source',
              hint: 'The database can be downloaded as an exported dump file or pre-packaged in a container image.',
              options: $options,
              default: $this->default($n, match ($r[HostingProvider::id()]) {
                HostingProvider::ACQUIA => DatabaseDownloadSource::ACQUIA,
                HostingProvider::LAGOON => DatabaseDownloadSource::LAGOON,
                default => DatabaseDownloadSource::URL,
              }),
              );
      }, DatabaseDownloadSource::id())

      ->addIf(
            fn($r): bool => $r[DatabaseDownloadSource::id()] === DatabaseDownloadSource::CONTAINER_REGISTRY,
            fn($r, $pr, $n): string => text(
              label: 'What is your database container image name and a tag?',
              hint: 'Use "latest" tag for the latest version. CI will be building this image overnight.',
              placeholder: sprintf('E.g. %s/%s-data:latest', Converter::phpNamespace($r[OrgMachineName::id()]), Converter::phpNamespace($r[MachineName::id()])),
              default: $this->default($n, sprintf('%s/%s-data:latest', Converter::phpNamespace($r[OrgMachineName::id()]), Converter::phpNamespace($r[MachineName::id()]))),
              transform: fn($v): string => trim($v),
              validate: fn($v): ?string => Validator::containerImage($v) ? NULL : 'Please enter a valid container image name with an optional tag.',
          ), DatabaseImage::id())

      ->intro('Continuous Integration')

      ->add(function (array $r, $pr, $n): int|string {
              $options = [
                CiProvider::NONE => 'None',
                CiProvider::GITHUB_ACTIONS => 'GitHub Actions',
                CiProvider::CIRCLECI => 'CircleCI',
              ];

              if ($r[CodeProvider::id()] !== CodeProvider::GITHUB) {
                unset($options[CiProvider::GITHUB_ACTIONS]);
              }

              return select(
              label: '♻️ Continuous Integration provider',
              hint: 'Both providers support equivalent workflow.',
              options: $options,
              default: $this->default($n, CiProvider::GITHUB_ACTIONS),
              );
      }, CiProvider::id())

      ->intro('Automations')

      ->add(fn($r, $pr, $n): int|string => select(
        label: '⬆️ Dependency updates provider',
        hint: 'Use a self-hosted service if you can’t install a GitHub app.',
        options: [
          DependencyUpdatesProvider::RENOVATEBOT_CI  => '🤖 + ♻️ Renovate self-hosted in CI',
          DependencyUpdatesProvider::RENOVATEBOT_APP => '🤖 Renovate GitHub app',
          DependencyUpdatesProvider::NONE => '⭕  None',
        ],
        default: $this->default($n, DependencyUpdatesProvider::RENOVATEBOT_CI),
      ), DependencyUpdatesProvider::id())

      ->add(fn($r, $pr, $n): bool => confirm(
        label: '👤 Auto-assign the author to their PR?',
        hint: 'Helps to keep the PRs organized.',
        default: $this->default($n, TRUE),
      ), AssignAuthorPr::id())

      ->add(fn($r, $pr, $n): bool => confirm(
        label: '🎫 Auto-add a <info>CONFLICT</info> label to a PR when conflicts occur?',
        hint: 'Helps to keep quickly identify PRs that need attention.',
        default: $this->default($n, TRUE),
      ), LabelMergeConflictsPr::id())

      ->intro('Documentation')

      ->add(fn($r, $pr, $n): bool => confirm(
        label: '📚 Preserve project documentation?',
        hint: 'Helps to maintain the project documentation within the repository.',
        default: $this->default($n, TRUE),
      ), PreserveDocsProject::id())

      ->add(fn($r, $pr, $n): bool => confirm(
        label: '📋 Preserve onboarding checklist?',
        hint: 'Helps to track onboarding to Vortex within the repository.',
        default: $this->default($n, TRUE),
      ), PreserveDocsOnboarding::id())

      ->submit();

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    // Filter out elements with numeric keys returned from intro()'s.
    $responses = array_filter($responses, function ($key): bool {
      return !is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);

    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity($original_verbosity);
    }

    $this->responses = $responses;
  }

  /**
   * Get all received responses.
   */
  public function getResponses(): array {
    return $this->responses;
  }

  /**
   * Run all processors.
   */
  public function process(): void {
    $ids = [
      Webroot::id(),
      Theme::id(),
      Profile::id(),
      ModulePrefix::id(),
      MachineName::id(),
      OrgMachineName::id(),
      Name::id(),
      Org::id(),
      Domain::id(),
      CodeProvider::id(),
      GithubRepo::id(),
      ThemeRunner::id(),
      Services::id(),
      HostingProvider::id(),
      DeployType::id(),
      ProvisionType::id(),
      DatabaseDownloadSource::id(),
      DatabaseImage::id(),
      CiProvider::id(),
      DependencyUpdatesProvider::id(),
      AssignAuthorPr::id(),
      LabelMergeConflictsPr::id(),
      PreserveDocsProject::id(),
      PreserveDocsOnboarding::id(),
      Internal::id(),
    ];

    foreach ($ids as $id) {
      if (!array_key_exists($id, $this->handlers)) {
        throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
      }

      $this->handlers[$id]->setResponses($this->responses)->process();
    }
  }

  public function shouldProceed(): bool {
    $proceed = TRUE;

    if (!$this->config->getNoInteraction()) {
      $proceed = confirm(
        label: 'Proceed with installing Vortex?',
        hint: sprintf('Vortex will be installed into your project\'s directory "%s"', $this->config->getDst())
      );
    }

    // Kill-switch to not proceed with install. If false, the installer will not
    // proceed despite the answer received above.
    if (!$this->config->get(Config::PROCEED)) {
      $proceed = FALSE;
    }

    return $proceed;
  }

  public static function makeEnvName(string $id): string {
    return Converter::constant('VORTEX_INSTALL_PROMPT_' . $id);
  }

  public function getResponsesSummary(): array {
    $responses = $this->getResponses();

    $values['General information'] = Tui::LIST_SECTION_TITLE;
    $values['🔖 Site name'] = $responses[Name::id()];
    $values['🔖 Site machine name'] = $responses[MachineName::id()];
    $values['🏢 Organization name'] = $responses[Org::id()];
    $values['🏢 Organization machine name'] = $responses[OrgMachineName::id()];
    $values['🌐 Public domain'] = $responses[Domain::id()];

    $values['Code repository'] = Tui::LIST_SECTION_TITLE;
    $values['Code provider'] = $responses[CodeProvider::id()];

    if (!empty($responses[GithubToken::id()])) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    $values['GitHub repository'] = $responses[GithubRepo::id()] ?? '<empty>';

    $values['Drupal'] = Tui::LIST_SECTION_TITLE;
    $values['📁 Webroot'] = $responses[Webroot::id()];
    $values['Profile'] = $responses[Profile::id()];

    $values['🧩 Module prefix'] = $responses[ModulePrefix::id()];
    $values['🎨 Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Hosting'] = Tui::LIST_SECTION_TITLE;
    $values['🏠 Hosting provider'] = $responses[HostingProvider::id()];

    $values['Deployment'] = Tui::LIST_SECTION_TITLE;
    $values['🚚 Deployment types'] = Converter::toList($responses[DeployType::id()]);

    $values['Workflow'] = Tui::LIST_SECTION_TITLE;
    $values['Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['Database dump source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    $values['Continuous Integration'] = Tui::LIST_SECTION_TITLE;
    $values['♻️ CI provider'] = $responses[CiProvider::id()];

    $values['Automations'] = Tui::LIST_SECTION_TITLE;
    $values['⬆️ Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['👤 Auto-assign PR author'] = Converter::bool($responses[AssignAuthorPr::id()]);
    $values['🎫 Auto-add a <info>CONFLICT</info> label to PRs'] = Converter::bool($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['📚 Preserve project documentation'] = Converter::bool($responses[PreserveDocsProject::id()]);
    $values['📋 Preserve onboarding checklist'] = Converter::bool($responses[PreserveDocsOnboarding::id()]);

    $values['Locations'] = Tui::LIST_SECTION_TITLE;
    $values['Current directory'] = $this->config->getRoot();
    $values['Destination directory'] = $this->config->getDst();
    $values['Vortex repository'] = $this->config->get(Config::REPO);
    $values['Vortex reference'] = $this->config->get(Config::REF);

    return $values;
  }

  /**
   * Get a default value for a response.
   *
   * @param string $id
   *   The response name.
   * @param string $default
   *   The default value to return.
   */
  protected function default(string $id, string|bool|array $default = ''): mixed {
    // Allow to set the value from the environment variable.
    $env_var = static::makeEnvName($id);
    $env_val = getenv($env_var);
    if (is_string($env_val)) {
      return Env::toValue($env_val);
    }

    if (!array_key_exists($id, $this->handlers)) {
      return $default;
    }

    $discovered = $this->handlers[$id]->discover();

    return is_null($discovered) ? $default : $discovered;
  }

  /**
   * Collect and initialise handlers.
   */
  protected function initHandlers(): void {
    $dir = __DIR__ . '/Handlers';

    $files = scandir($dir);

    if ($files === FALSE) {
      throw new \RuntimeException(sprintf('Could not read the directory "%s".', $dir));
    }

    $handler_files = array_filter($files, function ($file): bool {
      return !in_array($file, ['.', '..']);
    });

    $classes = [];
    foreach ($handler_files as $file) {
      $class = 'DrevOps\\Installer\\Prompts\\Handlers\\' . basename($file, '.php');

      if (!class_exists($class) || !is_subclass_of($class, HandlerInterface::class) || $class === AbstractHandler::class) {
        continue;
      }

      $classes[] = $class;
    }

    // Discover webroot and set for all handlers to help with paths resolution.
    $webroot = (new Webroot($this->config))->discover() ?: Webroot::WEB;

    if (!is_string($webroot)) {
      throw new \RuntimeException('Webroot could not be discovered.');
    }

    foreach ($classes as $class) {
      $handler = new $class($this->config);
      $handler->setWebroot($webroot);
      $this->handlers[$handler::id()] = $handler;
    }
  }

}
