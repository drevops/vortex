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
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\Dotenv;
use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
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
  const TOTAL_RESPONSES = 31;

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
      ->add(fn(array $r, $pr, $n): string => text(...$this->args(MachineName::class, NULL, $r)), MachineName::id())
      ->add(fn(array $r, $pr, $n): string => text(...$this->args(Org::class, NULL, $r)), Org::id())
      ->add(fn(array $r, $pr, $n): string => text(...$this->args(OrgMachineName::class, NULL, $r)), OrgMachineName::id())
      ->add(fn(array $r, $pr, $n): string => text(...$this->args(Domain::class, NULL, $r)), Domain::id())

      ->intro('Drupal')
      ->addIf(
          fn(array $r): bool => $this->handlers[Starter::id()]->shouldRun($r),
          fn(array $r, $pr, $n): int|string => select(...$this->args(Starter::class, NULL, $r)),
          Starter::id()
        )
      ->add(
          fn(array $r, $pr, $n): string => $this->resolveOrPrompt(Profile::id(), $r, fn(): int|string => select(...$this->args(Profile::class))),
          Profile::id()
        )
        ->addIf(
            fn(array $r): bool => $this->handlers[ProfileCustom::id()]->shouldRun($r),
            fn($r, $pr, $n): string => text(...$this->args(ProfileCustom::class)),
            ProfileCustom::id()
          )
      ->add(fn(array $r, $pr, $n): array => multiselect(...$this->args(Modules::class, NULL, $r)), Modules::id())
      ->add(fn(array $r, $pr, $n): string => text(...$this->args(ModulePrefix::class, NULL, $r)), ModulePrefix::id())
      ->add(
          fn(array $r, $pr, $n): string => $this->resolveOrPrompt(Theme::id(), $r, fn(): int|string => select(...$this->args(Theme::class))),
          Theme::id()
        )
        ->addIf(
            fn(array $r): bool => $this->handlers[ThemeCustom::id()]->shouldRun($r),
            fn(array $r, $pr, $n): string => text(...$this->args(ThemeCustom::class, NULL, $r)),
            ThemeCustom::id()
          )

      ->intro('Code repository')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(CodeProvider::class)), CodeProvider::id())
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(VersionScheme::class)), VersionScheme::id())

      ->intro('Environment')
      ->add(fn($r, $pr, $n): string => suggest(...$this->args(Timezone::class)), Timezone::id())
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Services::class)), Services::id())
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Tools::class)), Tools::id())

      ->intro('Hosting')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(HostingProvider::class)), HostingProvider::id())
      ->addIf(
          fn(array $r): bool => $this->handlers[HostingProjectName::id()]->shouldRun($r),
          fn(array $r, $pr, $n): string => text(...$this->args(HostingProjectName::class, NULL, $r)),
          HostingProjectName::id()
        )
      ->add(
          fn(array $r, $pr, $n): string => $this->resolveOrPrompt(Webroot::id(), $r, fn(): string => text(...$this->args(Webroot::class, NULL, $r))),
          Webroot::id()
        )

      ->intro('Deployment')
      ->add(fn(array $r, $pr, $n): array => multiselect(...$this->args(DeployTypes::class, NULL, $r)), DeployTypes::id())

      ->intro('Workflow')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(ProvisionType::class)), ProvisionType::id())
      ->addIf(
          fn(array $r): bool => $this->handlers[DatabaseDownloadSource::id()]->shouldRun($r),
          fn(array $r, $pr, $n): int|string => select(...$this->args(DatabaseDownloadSource::class, NULL, $r)),
          DatabaseDownloadSource::id()
        )
        ->addIf(
            fn(array $r): bool => $this->handlers[DatabaseImage::id()]->shouldRun($r),
            fn(array $r, $pr, $n): string => text(...$this->args(DatabaseImage::class, NULL, $r)),
            DatabaseImage::id()
          )
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(Migration::class)), Migration::id())
      ->addIf(
          fn(array $r): bool => $this->handlers[MigrationDownloadSource::id()]->shouldRun($r),
          fn(array $r, $pr, $n): int|string => select(...$this->args(MigrationDownloadSource::class, NULL, $r)),
          MigrationDownloadSource::id()
        )

      ->intro('Notifications')
      ->add(fn($r, $pr, $n): array => multiselect(...$this->args(NotificationChannels::class)), NotificationChannels::id())

      ->intro('Continuous Integration')
      ->add(fn(array $r, $pr, $n): int|string => select(...$this->args(CiProvider::class, NULL, $r)), CiProvider::id())

      ->intro('Automations')
      ->add(fn($r, $pr, $n): int|string => select(...$this->args(DependencyUpdatesProvider::class)), DependencyUpdatesProvider::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(AssignAuthorPr::class)), AssignAuthorPr::id())
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(LabelMergeConflictsPr::class)), LabelMergeConflictsPr::id())

      ->intro('Documentation')
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(PreserveDocsProject::class)), PreserveDocsProject::id())

      ->intro('AI')
      ->add(fn($r, $pr, $n): bool => confirm(...$this->args(AiCodeInstructions::class)), AiCodeInstructions::id());

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:enable Drupal.WhiteSpace.ObjectOperatorIndent.Indent
    // phpcs:enable Drupal.WhiteSpace.ScopeIndent.IncorrectExact

    $responses = $form->submit();

    // Filter out elements with numeric keys returned from intro()'s.
    $responses = array_filter($responses, fn($key): bool => !is_numeric($key), ARRAY_FILTER_USE_KEY);

    // Handle Profile custom name merging.
    if (isset($responses[Profile::id()]) && $responses[Profile::id()] === Profile::CUSTOM && isset($responses[ProfileCustom::id()])) {
      $responses[Profile::id()] = $responses[ProfileCustom::id()];
    }

    // Always remove ProfileCustom key (it's only used for internal merging)
    unset($responses[ProfileCustom::id()]);

    // Handle Theme custom name merging.
    if (isset($responses[Theme::id()]) && $responses[Theme::id()] === Theme::CUSTOM && isset($responses[ThemeCustom::id()])) {
      $responses[Theme::id()] = $responses[ThemeCustom::id()];
    }

    // Always remove ThemeCustom key (it's only used for internal merging)
    unset($responses[ThemeCustom::id()]);

    // Handle DatabaseDownloadSource when ProvisionType is PROFILE.
    if (isset($responses[ProvisionType::id()]) && $responses[ProvisionType::id()] === ProvisionType::PROFILE) {
      $responses[DatabaseDownloadSource::id()] = DatabaseDownloadSource::NONE;
    }

    // Handle Starter when the installer is running in update mode.
    if ($this->config->isVortexProject() && !isset($responses[Starter::id()])) {
      $responses[Starter::id()] = Starter::LOAD_DATABASE_DEMO;
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
      PreserveDocsProject::id(),
      LabelMergeConflictsPr::id(),
      AssignAuthorPr::id(),
      DependencyUpdatesProvider::id(),
      CiProvider::id(),
      MigrationDownloadSource::id(),
      Migration::id(),
      DatabaseImage::id(),
      DatabaseDownloadSource::id(),
      ProvisionType::id(),
      NotificationChannels::id(),
      DeployTypes::id(),
      HostingProvider::id(),
      Tools::id(),
      Services::id(),
      Timezone::id(),
      VersionScheme::id(),
      CodeProvider::id(),
      Modules::id(),
      Starter::id(),
      ProfileCustom::id(),
      Profile::id(),
      Domain::id(),
      HostingProjectName::id(),
      ModulePrefix::id(),
      ThemeCustom::id(),
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
   * Run all post-install processors.
   */
  public function runPostInstall(): string {
    $output = '';

    $ids = [
      Starter::id(),
      HostingProvider::id(),
      CiProvider::id(),
      Internal::id(),
    ];

    foreach ($ids as $id) {
      if (!array_key_exists($id, $this->handlers)) {
        throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
      }

      $handler_output = $this->handlers[$id]->postInstall();

      if (is_string($handler_output) && !empty($handler_output)) {
        $output .= $handler_output;
      }
    }

    return $output;
  }

  /**
   * Run all post-build processors.
   *
   * @param string $result
   *   The result of the build operation.
   *
   * @return string
   *   The combined output from all post-build processors.
   */
  public function runPostBuild(string $result): string {
    $output = '';

    $ids = [
      Starter::id(),
      HostingProvider::id(),
      CiProvider::id(),
    ];

    foreach ($ids as $id) {
      if (!array_key_exists($id, $this->handlers)) {
        throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
      }

      $handler_output = $this->handlers[$id]->postBuild($result);

      if (is_string($handler_output) && !empty($handler_output)) {
        $output .= $handler_output;
      }
    }

    return $output;
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
      Tui::line(sprintf('Vortex will be installed into your project\'s directory "%s"', $this->config->getDst()));
      $proceed = confirm(
        label: 'Proceed with installing Vortex?',
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

    $values['Drupal'] = Tui::LIST_SECTION_TITLE;
    $values['Starter'] = $responses[Starter::id()];
    $values['Modules'] = Converter::toList($responses[Modules::id()], ', ');
    $values['Webroot'] = $responses[Webroot::id()];
    $values['Profile'] = $responses[Profile::id()];
    $values['Module prefix'] = $responses[ModulePrefix::id()];
    $values['Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Code repository'] = Tui::LIST_SECTION_TITLE;
    $values['Code provider'] = $responses[CodeProvider::id()];
    $values['Version scheme'] = $responses[VersionScheme::id()];

    $values['Environment'] = Tui::LIST_SECTION_TITLE;
    $values['Timezone'] = $responses[Timezone::id()];
    $values['Services'] = Converter::toList($responses[Services::id()], ', ');
    $values['Tools'] = Converter::toList($responses[Tools::id()], ', ');

    $values['Hosting'] = Tui::LIST_SECTION_TITLE;
    $values['Hosting provider'] = $responses[HostingProvider::id()];
    if (in_array($this->responses[HostingProvider::id()], [HostingProvider::LAGOON, HostingProvider::ACQUIA])) {
      $values['Hosting project name'] = $responses[HostingProjectName::id()];
    }

    $values['Deployment'] = Tui::LIST_SECTION_TITLE;
    $values['Deployment types'] = Converter::toList($responses[DeployTypes::id()]);

    $values['Workflow'] = Tui::LIST_SECTION_TITLE;
    $values['Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['Database source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    if (isset($responses[Migration::id()])) {
      $values['Migration database'] = Converter::bool($responses[Migration::id()]);
      if ($responses[Migration::id()] === TRUE && isset($responses[MigrationDownloadSource::id()])) {
        $values['Migration database source'] = $responses[MigrationDownloadSource::id()];
      }
    }

    $values['Notifications'] = Tui::LIST_SECTION_TITLE;
    $values['Channels'] = Converter::toList($responses[NotificationChannels::id()]);

    $values['Continuous Integration'] = Tui::LIST_SECTION_TITLE;
    $values['CI provider'] = $responses[CiProvider::id()];

    $values['Automations'] = Tui::LIST_SECTION_TITLE;
    $values['Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['Auto-assign PR author'] = Converter::bool($responses[AssignAuthorPr::id()]);
    $values['Auto-add a CONFLICT label to PRs'] = Converter::bool($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['Preserve project documentation'] = Converter::bool($responses[PreserveDocsProject::id()]);

    $values['AI'] = Tui::LIST_SECTION_TITLE;
    $values['AI agent instructions'] = Converter::bool($responses[AiCodeInstructions::id()]);

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

    $handler_files = array_filter($files, fn(string $file): bool => !in_array($file, ['.', '..']));

    $classes = [];
    foreach ($handler_files as $handler_file) {
      $class = 'DrevOps\\VortexInstaller\\Prompts\\Handlers\\' . basename($handler_file, '.php');

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

    $description = $handler->description($responses);
    if (!is_null($description)) {
      $args['description'] = PHP_EOL . $description . PHP_EOL;
    }

    if ($handler->isRequired()) {
      $args['required'] = TRUE;
    }

    $options = $handler->options($responses);
    if (is_array($options)) {
      $args['options'] = $options;
      $args['scroll'] = 10;
    }

    // Find appropriate default value.
    $default_from_handler = $handler->default($responses);
    // Create the env var name.
    $var_name = static::makeEnvName($id);
    // Get from config.
    $config_val = $this->config->get($var_name);
    $default_from_config = is_null($config_val) ? NULL : $config_val;
    // Get from env.
    $env_val = Env::get($var_name);
    $default_from_env = is_null($env_val) ? NULL : Env::toValue($env_val);
    // Get from discovery.
    $default_from_discovery = $this->handlers[$id]->discover();

    if (!is_null($default_from_config)) {
      $default = $default_from_config;
    }
    elseif (!is_null($default_from_env)) {
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

  /**
   * Resolve a value via handler or prompt the user.
   *
   * This method is used to resolve a value via a handler's resolvedValue()
   * method. If the value is not resolved, it will prompt the user using the
   * provided prompt callable.
   *
   * @param string $handler_id
   *   The handler ID.
   * @param array $r
   *   Current form responses for context-aware methods.
   * @param callable $prompt
   *   The prompt callable to use if the value is not resolved.
   *
   * @return string
   *   The resolved value.
   */
  protected function resolveOrPrompt(string $handler_id, array $r, callable $prompt): string {
    $handler = $this->handlers[$handler_id];
    $resolved = $handler->resolvedValue($r);

    if (is_string($resolved)) {
      $message = $handler->resolvedMessage($r, $resolved);

      if ($message) {
        info($message);
      }

      return $resolved;
    }

    return (string) $prompt();
  }

}
