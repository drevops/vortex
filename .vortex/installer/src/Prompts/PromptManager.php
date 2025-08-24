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
use DrevOps\VortexInstaller\Prompts\Handlers\Dotenv;
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
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
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
   * Total number of top-level responses.
   *
   * Used to display the progress of the prompts.
   */
  const TOTAL_RESPONSES = 24;

  /**
   * Array of responses.
   */
  protected array $responses = [];

  /**
   * Current response index.
   *
   * Used to display the progress of the prompts.
   */
  protected int $currentResponseIndex = 0;

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
   * Run prompts to get responses.
   *
   * If non-interactive mode is used, the values provided by $this->default()
   * method, including discovery from the existing codebase, will be used.
   */
  public function runPrompts(): void {
    // Set verbosity for TUI output based on the config. This will be reset
    // after the prompt is completed.
    $original_verbosity = Tui::output()->getVerbosity();
    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:disable Drupal.WhiteSpace.ObjectOperatorIndent.Indent
    // phpcs:disable Drupal.WhiteSpace.ScopeIndent.IncorrectExact
    $form = form()
      ->intro('General information')
      ->add(fn($r, $pr, $n): string => text(...$this->args(Name::class)), Name::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(MachineName::class, NULL, $r)), MachineName::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Org::class, NULL, $r)), Org::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(OrgMachineName::class, NULL, $r)), OrgMachineName::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Domain::class, NULL, $r)), Domain::id())

      ->intro('Code repository')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(CodeProvider::class)), CodeProvider::id())

      ->intro('Drupal')
      ->add(
          fn($r, $pr, $n): int|string => select(...$this->args(Profile::class)),
          Profile::id()
        )
        ->addIf(
            fn($r): bool => $this->handlers[ProfileCustom::id()]->shouldRun($r),
            fn($r, $pr, $n): string => text(...$this->args(ProfileCustom::class)),
            ProfileCustom::id()
          )
      ->add(fn($r, $pr, $n): string => text(...$this->args(ModulePrefix::class, NULL, $r)), ModulePrefix::id())
      ->add(fn($r, $pr, $n): string => text(...$this->args(Theme::class, NULL, $r)), Theme::id())

      ->intro('Environment')
      ->add(fn($r, $pr, $n): string => suggest(...$this->args(Timezone::class)), Timezone::id())
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Services::class)), Services::id())
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Tools::class)), Tools::id())

      ->intro('Hosting')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(HostingProvider::class)), HostingProvider::id())
      ->add(
          function (array $r, $pr, $n): string {
            $handler = $this->handlers[Webroot::id()];
            $resolved = $handler->resolvedValue($r);
            if (is_string($resolved)) {
              info($handler->resolvedMessage($r));
              return $resolved;
            }
            else {
              return text(...$this->args(Webroot::class, NULL, $r));
            }
          },
          Webroot::id()
        )

      ->intro('Deployment')
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(DeployType::class, NULL, $r)), DeployType::id())

      ->intro('Workflow')
      ->add(fn($r, $pr, $n) => Tui::note('<info>Provisioning</info> is the process of setting up the site in the environment with an already assembled codebase.'))
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(ProvisionType::class)), ProvisionType::id())
      ->addIf(
          fn($r): bool => $this->handlers[DatabaseDownloadSource::id()]->shouldRun($r),
          fn($r, $pr, $n): int|string => select(...$this->args(DatabaseDownloadSource::class, NULL, $r)),
          DatabaseDownloadSource::id()
        )
        ->addIf(
            fn($r): bool => $this->handlers[DatabaseImage::id()]->shouldRun($r),
            fn($r, $pr, $n): string => text(...$this->args(DatabaseImage::class, NULL, $r)),
            DatabaseImage::id()
          )

      ->intro('Continuous Integration')
      ->add(fn(array $r, $pr, $n): int|string => select(...$this->args(CiProvider::class, NULL, $r)), CiProvider::id())

      ->intro('Automations')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(DependencyUpdatesProvider::class)), DependencyUpdatesProvider::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(AssignAuthorPr::class)), AssignAuthorPr::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(LabelMergeConflictsPr::class)), LabelMergeConflictsPr::id())

      ->intro('Documentation')
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(PreserveDocsProject::class)), PreserveDocsProject::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(PreserveDocsOnboarding::class)), PreserveDocsOnboarding::id())

      ->intro('AI')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(AiCodeInstructions::class)), AiCodeInstructions::id());

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:enable Drupal.WhiteSpace.ObjectOperatorIndent.Indent
    // phpcs:enable Drupal.WhiteSpace.ScopeIndent.IncorrectExact

    $responses = $form->submit();

    // Filter out elements with numeric keys returned from intro()'s.
    $responses = array_filter($responses, function ($key): bool {
      return !is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);

    // Handle Profile custom name merging.
    if (isset($responses[Profile::id()]) && $responses[Profile::id()] === Profile::CUSTOM && isset($responses[ProfileCustom::id()])) {
      $responses[Profile::id()] = $responses[ProfileCustom::id()];
    }

    // Always remove ProfileCustom key (it's only used for internal merging)
    unset($responses[ProfileCustom::id()]);

    // Handle DatabaseDownloadSource when ProvisionType is PROFILE.
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
   *
   * Used to provide direct access to the responses values.
   *
   * @return array
   *   An associative array of responses, where keys are handler IDs and values
   *   are the responses provided by the user or discovered by handlers.
   */
  public function getResponses(): array {
    return $this->responses;
  }

  /**
   * Run all processors.
   */
  public function runProcessors(): void {
    // Run processors in the reverse order of how they are defined in the
    // runPrompts() to ensure that the handlers for string replacements process
    // more specific values first, and the more generic ones last.
    $ids = [
      Dotenv::id(),
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
      Tools::id(),
      Services::id(),
      Timezone::id(),
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

  /**
   * Check if the installation should proceed.
   *
   * This method checks the configuration for the no-interaction mode and
   * prompts the user for confirmation if not in no-interaction mode.
   *
   * @return bool
   *   TRUE if the installation should proceed, FALSE otherwise.
   */
  public function shouldProceed(): bool {
    $proceed = TRUE;

    if (!$this->config->getNoInteraction()) {
      $proceed = confirm(
        label: 'Proceed with installing Vortex?',
        hint: sprintf('Vortex will be installed into your project\'s directory "%s"', $this->config->getDst())
      );
    }

    // Kill-switch to not proceed with install. If FALSE, the installer will not
    // proceed despite the answer received above.
    if (!$this->config->get(Config::PROCEED)) {
      $proceed = FALSE;
    }

    return $proceed;
  }

  public function getResponsesSummary(): array {
    $responses = $this->responses;

    $values['General information'] = Tui::LIST_SECTION_TITLE;
    $values['Site name'] = $responses[Name::id()];
    $values['Site machine name'] = $responses[MachineName::id()];
    $values['Organization name'] = $responses[Org::id()];
    $values['Organization machine name'] = $responses[OrgMachineName::id()];
    $values['Public domain'] = $responses[Domain::id()];

    $values['Code repository'] = Tui::LIST_SECTION_TITLE;
    $values['Code provider'] = $responses[CodeProvider::id()];

    $values['Drupal'] = Tui::LIST_SECTION_TITLE;
    $values['Webroot'] = $responses[Webroot::id()];
    $values['Profile'] = $responses[Profile::id()];

    $values['Module prefix'] = $responses[ModulePrefix::id()];
    $values['Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Environment'] = Tui::LIST_SECTION_TITLE;
    $values['Timezone'] = $responses[Timezone::id()];
    $values['ClamAV'] = Converter::bool(in_array(Services::CLAMAV, $responses[Services::id()]));
    $values['Solr'] = Converter::bool(in_array(Services::SOLR, $responses[Services::id()]));
    $values['Valkey'] = Converter::bool(in_array(Services::VALKEY, $responses[Services::id()]));
    $values['PHP CodeSniffer'] = Converter::bool(in_array(Tools::PHPCS, $responses[Tools::id()]));
    $values['PHP Mess Detector'] = Converter::bool(in_array(Tools::PHPMD, $responses[Tools::id()]));
    $values['PHPStan'] = Converter::bool(in_array(Tools::PHPSTAN, $responses[Tools::id()]));
    $values['Rector'] = Converter::bool(in_array(Tools::RECTOR, $responses[Tools::id()]));
    $values['PHPUnit'] = Converter::bool(in_array(Tools::PHPUNIT, $responses[Tools::id()]));
    $values['Behat'] = Converter::bool(in_array(Tools::BEHAT, $responses[Tools::id()]));

    $values['Hosting'] = Tui::LIST_SECTION_TITLE;
    $values['Hosting provider'] = $responses[HostingProvider::id()];

    $values['Deployment'] = Tui::LIST_SECTION_TITLE;
    $values['Deployment types'] = Converter::toList($responses[DeployType::id()]);

    $values['Workflow'] = Tui::LIST_SECTION_TITLE;
    $values['Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['Database source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    $values['Continuous Integration'] = Tui::LIST_SECTION_TITLE;
    $values['CI provider'] = $responses[CiProvider::id()];

    $values['Automations'] = Tui::LIST_SECTION_TITLE;
    $values['Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['Auto-assign PR author'] = Converter::bool($responses[AssignAuthorPr::id()]);
    $values['Auto-add a <info>CONFLICT</info> label to PRs'] = Converter::bool($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['Preserve project documentation'] = Converter::bool($responses[PreserveDocsProject::id()]);
    $values['Preserve onboarding checklist'] = Converter::bool($responses[PreserveDocsOnboarding::id()]);

    $values['AI'] = Tui::LIST_SECTION_TITLE;
    $values['AI code assistant instructions'] = $responses[AiCodeInstructions::id()];

    $values['Locations'] = Tui::LIST_SECTION_TITLE;
    $values['Current directory'] = $this->config->getRoot();
    $values['Destination directory'] = $this->config->getDst();
    $values['Vortex repository'] = $this->config->get(Config::REPO);
    $values['Vortex reference'] = $this->config->get(Config::REF);

    return $values;
  }

  /**
   * Generate an environment variable name for a prompt.
   *
   * @param string $id
   *   The prompt ID.
   *
   * @return string
   *   The environment variable name.
   */
  public static function makeEnvName(string $id): string {
    return Converter::constant('VORTEX_INSTALLER_PROMPT_' . $id);
  }

  /**
   * Generate a label for a prompt.
   *
   * @param string $text
   *   The text to display in the label.
   * @param string|null $suffix
   *   An optional suffix to display in the label.
   *
   * @return string
   *   The formatted label text.
   */
  protected function label(string $text, ?string $suffix = NULL): string {
    if (is_null($suffix)) {
      $this->currentResponseIndex++;
    }

    $suffix = $suffix !== NULL ? $this->currentResponseIndex . '.' . $suffix : $this->currentResponseIndex;

    return $text . ' ' . Tui::dim('(' . $suffix . '/' . static::TOTAL_RESPONSES . ')');
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

    $handler_files = array_filter($files, function (string $file): bool {
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

    // Discover web root and set for all handlers to help with paths resolution.
    $webroot = (new Webroot($this->config))->discover() ?: Webroot::WEB;

    if (!is_string($webroot)) {
      throw new \RuntimeException('Web root could not be discovered.');
    }

    foreach ($classes as $class) {
      $handler = new $class($this->config);
      $handler->setWebroot($webroot);
      $this->handlers[$handler::id()] = $handler;
    }
  }

  /**
   * Convert handler properties to Laravel prompts.
   *
   * Do not optimize this method to ease debugging and future changes.
   *
   * @param string $handler_class
   *   The handler class name.
   *   The handler id.
   * @param mixed $default_override
   *   Optional override for the default value (for response dependencies).
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return array
   *   Array of prompt arguments suitable for Laravel prompts.
   */
  private function args(string $handler_class, mixed $default_override = NULL, array $responses = []): array {
    $id = $handler_class::id();

    if (!array_key_exists($id, $this->handlers)) {
      throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
    }

    $handler = $this->handlers[$handler_class::id()];

    $args = [
      'label' => $this->label($handler->label()),
      'hint' => $handler->hint($responses),
      'placeholder' => $handler->placeholder($responses),
      'transform' => $handler->transform(),
      'validate' => $handler->validate(),
    ];

    if ($handler->isRequired()) {
      $args['required'] = TRUE;
    }

    $options = $handler->options($responses);
    if (is_array($options) && $options !== []) {
      $args['options'] = $options;
      $args['scroll'] = 10;
    }

    // Find appropriate default value.
    $default_from_handler = $handler->default($responses);

    $env_var = static::makeEnvName($id);
    $env_val = Env::get($env_var);
    $default_from_env = is_null($env_val) ? NULL : Env::toValue($env_val);

    $default_from_discovery = $this->handlers[$id]->discover();

    if (!is_null($default_from_env)) {
      $default = $default_from_env;
    }
    elseif (!is_null($default_from_discovery)) {
      $default = $default_from_discovery;
    }
    elseif (!is_null($default_override)) {
      $default = $default_override;
    }
    else {
      $default = $default_from_handler;
    }

    if (!is_null($default) && $default !== '') {
      $args['default'] = $default;
    }

    return array_filter($args, fn($value): bool => $value !== NULL);
  }

}
