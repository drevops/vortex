<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use DrevOps\VortexInstaller\Prompts\Handlers\AbstractHandler;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployType;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\GithubRepo;
use DrevOps\VortexInstaller\Prompts\Handlers\GithubToken;
use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\Tui;
use DrevOps\VortexInstaller\Utils\Validator;
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
 * @package DrevOps\VortexInstaller
 */
class PromptManager {

  /**
   * Array of responses.
   */
  protected array $responses = [];

  /**
   * Total number of top-level responses.
   *
   * Used to display the progress of the prompts.
   */
  protected int $totalResponses = 21;

  /**
   * Current response number.
   *
   * Used to display the progress of the prompts.
   */
  protected int $currentResponse = 0;

  /**
   * Array of handlers.
   *
   * @var array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   */
  protected array $handlers = [];

  /**
   * PromptManager constructor.
   *
   * @param \DrevOps\VortexInstaller\Utils\Config $config
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
    // phpcs:disable Drupal.WhiteSpace.ObjectOperatorIndent.Indent
    // phpcs:disable Drupal.WhiteSpace.ScopeIndent.IncorrectExact
    $responses = form()
      ->intro('General information')
      ->add(fn($r, $pr, $n): string => text(...$this->args(Name::class, $n)), Name::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(MachineName::class, $n, NULL, $r)), MachineName::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Org::class, $n, NULL, $r)), Org::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(OrgMachineName::class, $n, NULL, $r)), OrgMachineName::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Domain::class, $n, NULL, $r)), Domain::id())

      ->intro('Code repository')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(CodeProvider::class, $n)), CodeProvider::id())
      ->addIf(
          fn($r): bool => $this->handlers[GithubToken::id()]->condition()($r),
          fn($r, $pr, $n) => Tui::note('<info>' . (string)GithubToken::explanation() . '</info>')
        )
      ->addIf(
          fn($r): bool => $this->handlers[GithubToken::id()]->condition()($r),
          function ($r, $pr, $n): string {
            $handler = $this->handlers[GithubToken::id()];
            $resolved_value = $handler->resolved($r);
            if (!empty($resolved_value)) {
              Tui::ok($this->label((string) $handler->resolvedMessage($r), 'a'));
              return $resolved_value;
            } else {
              return password(...$this->args(GithubToken::class, $n));
            }
          },
          GithubToken::id()
        )
        ->addIf(
            fn($r): bool => $this->handlers[GithubRepo::id()]->condition()($r),
            fn($r, $pr, $n): string => text(...$this->args(GithubRepo::class, $n, NULL, $r)),
            GithubRepo::id()
          )

      ->intro('Drupal')
      ->add(
          function ($r, $pr, $n): int|string {
            $args = $this->args(Profile::class, $n);
            $args['default'] = $this->handlers[Profile::id()]->default();
            return select(...$args);
          },
          Profile::id()
        )
      ->addIf(
          fn($r): bool => $this->handlers[ProfileCustom::id()]->condition()($r),
          fn($r, $pr, $n): string => text(...$this->args(ProfileCustom::class, $n)),
          ProfileCustom::id()
        )
      ->add(fn($r, $pr, $n): string => text(...$this->args(ModulePrefix::class, $n, NULL, $r)), ModulePrefix::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Theme::class, $n, NULL, $r)), Theme::id())

      ->intro('Services')
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Services::class, $n)), Services::id())

      ->intro('Hosting')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(HostingProvider::class, $n)), HostingProvider::id())
      ->add(
          function (array $r, $pr, $n): string {
            $handler = $this->handlers[Webroot::id()];
            $resolved = $handler->resolved($r);
            if (!empty($resolved)) {
              info($handler->resolvedMessage($r));
              return $resolved;
            } else {
              return text(...$this->args(Webroot::class, $n, NULL, $r));
            }
          },
          Webroot::id()
        )

      ->intro('Deployment')
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(DeployType::class, $n, NULL, $r)), DeployType::id())

      ->intro('Workflow')
      ->add(fn($r, $pr, $n) => Tui::note('<info>Provisioning</info> is the process of setting up the site in the environment with an already assembled codebase.'))
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(ProvisionType::class, $n)), ProvisionType::id())
      ->addIf(
          fn($r): bool => $this->handlers[DatabaseDownloadSource::id()]->condition()($r),
          fn($r, $pr, $n): int|string => select(...$this->args(DatabaseDownloadSource::class, $n, NULL, $r)),
          DatabaseDownloadSource::id()
        )
      ->addIf(
          fn($r): bool => $this->handlers[DatabaseImage::id()]->condition()($r),
          function ($r, $pr, $n): string {
            $handler = $this->handlers[DatabaseImage::id()];
            $args = $this->args(DatabaseImage::class, $n, NULL, $r);
            $args['placeholder'] = $handler->getPlaceholderForContext($r);
            return text(...$args);
          },
          DatabaseImage::id()
        )

      ->intro('Continuous Integration')
      ->add(fn(array $r, $pr, $n): int|string => select(...$this->args(CiProvider::class, $n, NULL, $r)), CiProvider::id())

      ->intro('Automations')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(DependencyUpdatesProvider::class, $n)), DependencyUpdatesProvider::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(AssignAuthorPr::class, $n)), AssignAuthorPr::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(LabelMergeConflictsPr::class, $n)), LabelMergeConflictsPr::id())

      ->intro('Documentation')
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(PreserveDocsProject::class, $n)), PreserveDocsProject::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(PreserveDocsOnboarding::class, $n)), PreserveDocsOnboarding::id())

      ->intro('AI')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(AiCodeInstructions::class, $n)), AiCodeInstructions::id())

      ->submit();

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:enable Drupal.WhiteSpace.ObjectOperatorIndent.Indent
    // phpcs:enable Drupal.WhiteSpace.ScopeIndent.IncorrectExact

    // Filter out elements with numeric keys returned from intro()'s.
    $responses = array_filter($responses, function ($key): bool {
      return !is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);

    // Handle Profile custom name merging
    if (isset($responses[Profile::id()]) && $responses[Profile::id()] === Profile::CUSTOM && isset($responses[ProfileCustom::id()]) && $responses[ProfileCustom::id()] !== NULL) {
      $responses[Profile::id()] = $responses[ProfileCustom::id()];
    }

    // Always remove ProfileCustom key (it's only used for internal merging)
    unset($responses[ProfileCustom::id()]);

    // Handle DatabaseDownloadSource when ProvisionType is PROFILE
    if (isset($responses[ProvisionType::id()]) && $responses[ProvisionType::id()] === ProvisionType::PROFILE) {
      $responses[DatabaseDownloadSource::id()] = DatabaseDownloadSource::NONE;
    }

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
    // Run processors in the reverse order of how they are defined in the
    // PromptManager to ensure that the handlers for string replacements process
    // more specific values first, and the more generic ones last.
    $ids = [
      Webroot::id(),
      AiCodeInstructions::id(),
      PreserveDocsOnboarding::id(),
      PreserveDocsProject::id(),
      LabelMergeConflictsPr::id(),
      AssignAuthorPr::id(),
      DependencyUpdatesProvider::id(),
      CiProvider::id(),
      DatabaseImage::id(),
      DatabaseDownloadSource::id(),
      ProvisionType::id(),
      DeployType::id(),
      HostingProvider::id(),
      Services::id(),
      GithubRepo::id(),
      GithubToken::id(),
      CodeProvider::id(),
      ProfileCustom::id(),
      Profile::id(),
      Domain::id(),
      ModulePrefix::id(),
      Theme::id(),
      OrgMachineName::id(),
      MachineName::id(),
      Org::id(),
      Name::id(),
      // Always last.
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
    $values['🏷️ Site name'] = $responses[Name::id()];
    $values['🏷️ Site machine name'] = $responses[MachineName::id()];
    $values['🏢 Organization name'] = $responses[Org::id()];
    $values['🏢 Organization machine name'] = $responses[OrgMachineName::id()];
    $values['🌐 Public domain'] = $responses[Domain::id()];

    $values['Code repository'] = Tui::LIST_SECTION_TITLE;
    $values['🗄 Code provider'] = $responses[CodeProvider::id()];

    if (!empty($responses[GithubToken::id()])) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    $values['🏷️ GitHub repository'] = $responses[GithubRepo::id()] ?? '<empty>';

    $values['Drupal'] = Tui::LIST_SECTION_TITLE;
    $values['📁 Webroot'] = $responses[Webroot::id()];
    $values['🧾 Profile'] = $responses[Profile::id()];

    $values['🧩 Module prefix'] = $responses[ModulePrefix::id()];
    $values['🎨 Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Services'] = Tui::LIST_SECTION_TITLE;
    $values['🦠 ClamAV'] = Converter::bool(in_array(Services::CLAMAV, $responses[Services::id()]));
    $values['🔍 Solr'] = Converter::bool(in_array(Services::SOLR, $responses[Services::id()]));
    $values['🗃️ Valkey'] = Converter::bool(in_array(Services::VALKEY, $responses[Services::id()]));

    $values['Hosting'] = Tui::LIST_SECTION_TITLE;
    $values['☁️ Hosting provider'] = $responses[HostingProvider::id()];

    $values['Deployment'] = Tui::LIST_SECTION_TITLE;
    $values['🚚 Deployment types'] = Converter::toList($responses[DeployType::id()]);

    $values['Workflow'] = Tui::LIST_SECTION_TITLE;
    $values['🦋 Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['📡 Database source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['🏷️ Database container image'] = $responses[DatabaseImage::id()];
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

    $values['AI'] = Tui::LIST_SECTION_TITLE;
    $values['🤖 AI code assistant instructions'] = $responses[AiCodeInstructions::id()];

    $values['Locations'] = Tui::LIST_SECTION_TITLE;
    $values['Current directory'] = $this->config->getRoot();
    $values['Destination directory'] = $this->config->getDst();
    $values['Vortex repository'] = $this->config->get(Config::REPO);
    $values['Vortex reference'] = $this->config->get(Config::REF);

    return $values;
  }

  /**
   * Generate a label for a prompt.
   *
   * @param string $text
   *   The text to display in the label.
   * @param string|NULL $suffix
   *   An optional suffix to display in the label.
   *
   * @return string
   *   The formatted label text.
   */
  protected function label(string $text, ?string $suffix = NULL): string {
    if (is_null($suffix)) {
      $this->currentResponse++;
    }

    $suffix = $suffix !== NULL ? $this->currentResponse . '.' . $suffix : $this->currentResponse;

    return $text . ' ' . Tui::dim('(' . $suffix . '/' . $this->totalResponses . ')');
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
      $class = 'DrevOps\\VortexInstaller\\Prompts\\Handlers\\' . basename($file, '.php');

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

  /**
   * Helper function that converts handler properties to Laravel prompt arguments.
   *
   * @param string $handler_class
   *   The handler class name.
   * @param string $name
   *   The prompt name/key for default handling.
   * @param mixed $default
   *   Optional override for the default value (for response dependencies).
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return array
   *   Array of prompt arguments suitable for Laravel prompts.
   */
  private function args(string $handler_class, string $name, mixed $default_override = NULL, array $responses = []): array {
    $handler = $this->handlers[$handler_class::id()];

    if (!is_null($default_override)) {
      $default = $default_override;
    }
    else {
      $default = $handler->default($responses);
    }

    $args = [
      'label' => $this->label($handler->label()),
      'hint' => $handler->hint(),
      'placeholder' => $handler->placeholder(),
      'default' => $this->default($name, $default ?? ''),
      'transform' => $handler->transform(),
      'validate' => $handler->validate(),
    ];

    $options = $handler->options($responses);
    if ($options !== []) {
      $args['options'] = $options;
    }

    if ($handler->isRequired()) {
      $args['required'] = TRUE;
    }

    return array_filter($args, fn($value) => $value !== NULL);
  }

}
