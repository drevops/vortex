<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts;

use AlexSkrypnyk\Str2Name\Str2Name;
use DrevOps\Installer\Prompts\Handlers\AbstractHandler;
use DrevOps\Installer\Prompts\Handlers\AssignAuthorPr;
use DrevOps\Installer\Prompts\Handlers\CiProvider;
use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\Installer\Prompts\Handlers\DatabaseStoreType;
use DrevOps\Installer\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\Installer\Prompts\Handlers\DeployType;
use DrevOps\Installer\Prompts\Handlers\Domain;
use DrevOps\Installer\Prompts\Handlers\GithubRepo;
use DrevOps\Installer\Prompts\Handlers\GithubToken;
use DrevOps\Installer\Prompts\Handlers\HandlerInterface;
use DrevOps\Installer\Prompts\Handlers\HostingProvider;
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
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\UseCustomProfile;
use DrevOps\Installer\Prompts\Handlers\WebrootCustom;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Validator;
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

  protected array $responses = [];

  /**
   * @var array<string, \DrevOps\Installer\Prompts\Handlers\HandlerInterface>
   */
  protected array $handlers = [];

  public function __construct(
    protected OutputInterface $output,
    protected Config $config,
  ) {
    Prompt::setOutput($output);

    if ($this->config->getNoInteraction()) {
      Prompt::interactive(FALSE);
    }

    $this->initHandlers();
  }

  public function prompt() {
    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    $responses = form()
      ->intro('General information')

      ->add(fn($r, $pr, $n) => text(
        label: '🔖 Site name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Site',
        required: TRUE,
        default: $this->default($n, Str2Name::label(Env::get('VORTEX_PROJECT', basename((string) $this->config->getDst())))),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid name' : NULL,
      ), Name::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🔖 Site machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_site',
        required: TRUE,
        default: $this->default($n, Str2Name::machine($r['name'])),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), MachineName::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🏢 Organization name',
        hint: 'We will use this name in the project and in the documentation.',
        placeholder: 'E.g. My Org',
        required: TRUE,
        default: $this->default('org', Str2Name::label($r['name']) . ' Org'),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid organization name' : NULL,
      ), Org::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🏢 Organization machine name',
        hint: 'We will use this name for the project directory and in the code.',
        placeholder: 'E.g. my_org',
        required: TRUE,
        default: $this->default($n, Str2Name::machine($r['org'])),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), OrgMachineName::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🌐 Public domain',
        hint: 'Domain name without protocol and trailing slash.',
        placeholder: 'E.g. example.com',
        required: TRUE,
        default: $this->default($n, 'http://' . Str2Name::kebab($r['machine_name']) . '.com'),
        transform: fn(string $v) => Converter::domain($v),
        validate: fn($v) => filter_var($v, FILTER_VALIDATE_URL) === FALSE ? 'Please enter a valid domain name' : NULL,
      ), Domain::id())

      ->intro('Code repository')

      ->add(fn($r, $pr, $n) => select(
        label: '⚙️ Repository provider',
        hint: 'Vortex offers full automation with GitHub, while support for other providers is limited.',
        options: [
          CodeProvider::GITHUB => 'GitHub',
          CodeProvider::OTHER => 'Other',
        ],
        default: $this->default($n, 'github'),
      ), CodeProvider::id())

        ->addIf(
          fn($r) => $r['code_provider'] === 'github',
          fn($r, $pr, $n) => note("<info>We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.</info>"),
          'github_token_note'
        )

        ->addIf(
          fn($r) => $r['code_provider'] === 'github',
          fn($r, $pr, $n) => text(
            label: '🔑 GitHub access token (optional)',
            hint: Env::get('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new',
            placeholder: 'E.g. ghp_1234567890',
            default: $this->default($n),
            transform: fn(string $v) => trim($v),
            validate: fn($v) => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : NULL,
          ), GithubToken::id())

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
              ), GithubRepo::id())

      ->intro('Drupal')

      ->add(fn($r, $pr, $n) => confirm(
        label: 'Use a custom profile?',
        hint: 'Select "yes" to use a custom profile, or "no" to use the "standard" profile.',
        default: $this->default($n, FALSE),
      ), UseCustomProfile::id())

        ->addIf(
          fn($r) => $r[UseCustomProfile::id()],
          fn($r, $pr, $n) => text(
            label: 'Custom profile machine name',
            hint: 'Leave empty to use "standard" profile.',
            placeholder: 'E.g. my_profile',
            required: TRUE,
            default: $this->default($n, 'standard'),
            transform: fn(string $v) => trim($v),
            validate: fn($v) => match (TRUE) {
              !empty($v) && Converter::abbreviation($v) !== $v => 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
              default => 'standard',
            },
          ), Profile::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🧩 Module prefix',
        hint: 'We will use this name for custom modules.',
        placeholder: 'E.g. ms (for My Site)',
        required: TRUE,
        default: $this->default($n, Converter::abbreviation($r['machine_name'], 4, ['_'])),
        transform: fn(string $v) => trim($v),
        // @todo Fix validation  here.
        validate: fn($v) => Converter::machine(strtolower($v)) !== strtolower($v) ? 'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), ModulePrefix::id())

      ->add(fn($r, $pr, $n) => text(
        label: '🎨 Theme machine name',
        hint: 'We will use this name for the theme directory.',
        placeholder: 'E.g. mytheme',
        required: TRUE,
        default: $this->default($n, $r['machine_name']),
        transform: fn(string $v) => trim($v),
        validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
      ), Theme::id())

      ->intro('Hosting')

      ->add(fn($r, $pr, $n) => select(
        label: '🏠 Hosting provider',
        hint: 'Select the hosting provider where the project is hosted. The web root directory will be set accordingly.',
        options: [
          'none' => '⭕ None',
          'acquia' => '💧 Acquia Cloud',
          'lagoon' => '🌊 Lagoon',
          'other' => '🧩 Other',
        ],
        required: TRUE,
        default: $this->default($n, 'none'),
      ), HostingProvider::id())

        ->add(
          function ($r, $pr, $n) {
            if ($r[HostingProvider::id()] !== HostingProvider::OTHER){
              $webroot = match ($r[HostingProvider::id()]) {
                HostingProvider::ACQUIA => WebrootCustom::DOCROOT,
                HostingProvider::LAGOON => WebrootCustom::WEB,
                default => $this->default($n, WebrootCustom::DEFAULT)
              };

              info(sprintf('Web root will be set to "%s".',$webroot));
            }
            else {
              $webroot = text(
                label: '📁 Custom web root directory',
                hint: 'Custom directory where the web server serves the site.',
                placeholder: 'E.g. '. implode(', ',[WebrootCustom::WEB, WebrootCustom::DOCROOT]),
                required: TRUE,
                default: $this->default($n, WebrootCustom::DEFAULT),
                transform: fn(string $v) => !empty(trim($v)) ? Converter::path($v) : trim($v),
                validate: fn($v) => empty($v) ? 'Please enter a valid directory name' : NULL,
              );
            }
            return $webroot;
          }, WebrootCustom::id())

      ->intro('Deployment')

      ->add(function ($r, $pr, $n) {
        $defaults = [];

        $options = [
          DeployType::NONE => '⭕ None',
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
          required: FALSE,
        );
      }, DeployType::id())

      ->intro('Workflow')

      ->add(fn($r, $pr, $n) => note('<info>Provisioning</info> is the process of setting up the site in the environment with an already built codebase.'))

      ->add(fn($r, $pr, $n) => select(
        label: 'Provision type',
        hint: 'Selecting "Profile" will install site from a profile rather than a database dump.',
        options: [
          ProvisionType::DATABASE => 'Import from database dump',
          ProvisionType::PROFILE => 'Install from profile',
        ],
        default: $this->default($n, ProvisionType::DATABASE),
      ), ProvisionType::id())

        ->addIf(
          fn($r) => $r[ProvisionType::id()] === ProvisionType::DATABASE,
          function ($r, $pr, $n) {
            $options = [
              DatabaseDownloadSource::URL => '🌍 URL download',
              DatabaseDownloadSource::FTP => '📂 FTP download',
              DatabaseDownloadSource::ACQUIA => '💧 Acquia backup',
              DatabaseDownloadSource::LAGOON => '🌊 Lagoon environment',
              DatabaseDownloadSource::CONTAINER_REGISTRY => '🐳 Container registry',
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
              fn($r) => $r[DatabaseDownloadSource::id()] === DatabaseDownloadSource::CONTAINER_REGISTRY,
              fn($r, $pr, $n) => text(
                label: 'What is your database container image name and a tag?',
                hint: 'Use "latest" tag for the latest version. CI will be building this image overnight.',
                placeholder: sprintf('E.g. %s/%s-data:latest', Converter::phpNamespace($r[OrgMachineName::id()]), Converter::phpNamespace($r[MachineName::id()])),
                default: $this->default($n, sprintf('%s/%s-data:latest', Converter::phpNamespace($r[OrgMachineName::id()]), Converter::phpNamespace($r[MachineName::id()]))),
                transform: fn($v) => strtolower(trim($v)),
                validate: fn($v) => !Validator::containerImage($v) ? 'Please enter a valid image name and a tag' : NULL,
              ), DatabaseStoreType::id())

      ->intro('Continuous Integration')

      ->add(function ($r, $pr, $n) {
        $options = [
          CiProvider::NONE => '⭕ None',
          CiProvider::GHA => 'GitHub Actions',
          CiProvider::CIRCLECI => 'CircleCI',
        ];

        if ($r[CodeProvider::id()] !== CodeProvider::GITHUB) {
          unset($options[CiProvider::GHA]);
        }

        return select(
          label: '🔁 Continuous Integration provider',
          hint: 'Both providers support equivalent workflow.',
          options: $options,
          default: $this->default($n, CiProvider::GHA),
        );
      }, CiProvider::id())

      ->intro('Automations')

      ->add(fn($r, $pr, $n) => select(
        label: '🔄 Dependency updates provider',
        hint: 'Use a self-hosted service if you can’t install a GitHub app.',
        options: [
          DependencyUpdatesProvider::RENOVATEBOT_CI  => '🤖 + 🔁 Renovate self-hosted in CI',
          DependencyUpdatesProvider::RENOVATEBOT_APP => '🤖 Renovate GitHub app',
          DependencyUpdatesProvider::NONE => '⭕ None',
        ],
        default: $this->default($n, DependencyUpdatesProvider::RENOVATEBOT_CI),
      ), DependencyUpdatesProvider::id())

      ->add(fn($r, $pr, $n) => confirm(
        label: '👤 Auto-assign the author to their PR?',
        hint: 'Helps to keep the PRs organized.',
        default: $this->default($n, TRUE),
      ), AssignAuthorPr::id())

      ->add(fn($r, $pr, $n) => confirm(
        label: '🎫 Auto-add a <info>CONFLICT</info> label to a PR when conflicts occur?',
        hint: 'Helps to keep quickly identify PRs that need attention.',
        default: $this->default($n, TRUE),
      ), LabelMergeConflictsPr::id())

      ->intro('Documentation')

      ->add(fn($r, $pr, $n) => confirm(
        label: '📚 Preserve project documentation?',
        hint: 'Helps to maintain the project documentation within the repository.',
        default: $this->default($n, TRUE),
      ), PreserveDocsProject::id())

      ->add(fn($r, $pr, $n) => confirm(
        label: '📋 Preserve onboarding checklist?',
        hint: 'Helps to track onboarding to Vortex within the repository.',
        default: $this->default($n, TRUE),
      ), PreserveDocsOnboarding::id())

      ->submit();

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    // Filter out elements with numeric keys returned from intro()'s.
    $responses = array_filter($responses, function ($key) {
      return !is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);

    $this->responses = $responses;

    return $this->responses;
  }

  public function getResponses(): array {
    return $this->responses;
  }

  protected function default($name, $default = '') {
    if (!array_key_exists($name, $this->handlers)) {
      return $default;
    }

    return $this->handlers[$name]->discover() ?: $default;
  }

  public function process(?callable $cb = NULL) {
    // @todo: All processors should be based on handlers defined by PromptFields.
    // @todo: Re-order processors based on questions.
    $processors = [
      PromptFields::WEBROOT_CUSTOM,
      PromptFields::PROFILE,
      PromptFields::PROVISION_TYPE,
      PromptFields::THEME,
      PromptFields::DATABASE_DOWNLOAD_SOURCE,
      //      PromptFields::DATABASE_STORE_TYPE_CONTAINER_IMAGE,
      PromptFields::CI_PROVIDER,
      DeployType::class,
      PromptFields::HOSTING_PROVIDER,
      PromptFields::DOCS_ONBOARDING,
      PromptFields::DOCS_PROJECT,
      'internal',
    ];

    foreach ($processors as $name) {
      if (!array_key_exists($name, $this->handlers)) {
        throw new \RuntimeException(sprintf('Handler for "%s" not found.', $name));
      }

      // @todo Do not run process if there is no value in the responses (the \question was not asked).
      $this->handlers[$name]->setResponses($this->responses)->process();

      if (is_callable($cb)) {
        $cb($name, $processors);
      }
    }
  }

  protected function initHandlers() {
    $dir = __DIR__ . '/Handlers';

    $handler_files = array_filter(scandir($dir), function ($file) {
      return !in_array($file, ['.', '..']);
    });

    $classes = [];
    foreach ($handler_files as $file) {
      $class = 'DrevOps\\Installer\\Prompts\\Handlers\\' . basename($file, '.php');

      if (!class_exists($class) || !is_subclass_of($class, HandlerInterface::class) || $class == AbstractHandler::class) {
        continue;
      }

      $classes[] = $class;
    }

    // Discover webroot and set for all handlers to help with paths resolution.
    $webroot = (new WebrootCustom($this->config))->discover() ?: WebrootCustom::DEFAULT;

    foreach ($classes as $class) {
      $handler = new $class($this->config);
      $handler->setWebroot($webroot);
      $this->handlers[$handler->getKey()] = $handler;
    }
  }

  public function printFooter(): void {
    print PHP_EOL;

    // @todo Fix the footer.
    print 'Would print footer';
    //
    //    if ($this->isInstalled()) {
    //      $this->printBox('Finished updating Vortex. Review changes and commit required files.');
    //    }
    //    else {
    //      $this->printBox('Finished installing Vortex.');
    //
    //      $output = '';
    //      $output .= PHP_EOL;
    //      $output .= 'Next steps:' . PHP_EOL;
    //      $output .= '  cd ' . $this->config->getDst() . PHP_EOL;
    //      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
    //      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
    //      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
    //      $output .= PHP_EOL;
    //      $output .= '  See https://vortex.drevops.com/quickstart';
    //      $this->status($output, self::INSTALLER_STATUS_SUCCESS, TRUE, FALSE);
    //    }
  }

  // @todo Refactor this.
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

}
