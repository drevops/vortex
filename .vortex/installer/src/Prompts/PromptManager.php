<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use DrevOps\VortexInstaller\Prompts\Handlers\AbstractHandler;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CustomModules;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexInstaller\Prompts\Handlers\Gitleaks;
use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationImage;
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
use DrevOps\VortexInstaller\Prompts\Handlers\VisualRegression;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Schema\SchemaValidator;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\form;

/**
 * Centralized place for providing prompts and their processing.
 */
class PromptManager {

  /**
   * Array of responses.
   */
  protected array $responses = [];

  /**
   * Responses describing the destination as the handlers found it.
   *
   * Collected while the prompts run, so each value is the one discovery
   * produced with the same preceding responses in context.
   */
  protected array $discoveredResponses = [];

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
   * Prompt overrides from --prompts CLI option.
   *
   * Keyed by handler ID with validated values.
   *
   * @var array<string, mixed>
   */
  protected array $promptOverrides = [];

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
    $this->resolvePromptOverrides();
  }

  /**
   * Run prompts to get responses.
   *
   * In non-interactive mode, each prompt returns its default, which includes
   * values discovered from the existing codebase.
   */
  public function runPrompts(): void {
    // Quiet the TUI output in non-interactive mode; the original verbosity is
    // restored after the form completes.
    $original_verbosity = Tui::output()->getVerbosity();
    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    $form = form();
    $section = NULL;

    foreach ($this->getPromptHandlers() as $id => $handler) {
      $handler_section = $handler::section();

      if ($handler_section instanceof PromptSection && $handler_section !== $section) {
        $section = $handler_section;
        $form->intro($section->value);
      }

      $step = fn(array $responses, mixed $previous, ?string $name): mixed => $this->promptOrResolve($id, $responses);

      if ($handler->dependsOn() === NULL) {
        $form->add($step, $id);
      }
      else {
        $form->addIf(fn(array $responses): bool => $handler->shouldRun($responses), $step, $id);
      }
    }

    $responses = $form->submit();

    // Filter out elements with numeric keys returned by intro() calls.
    $responses = array_filter($responses, fn($key): bool => !is_numeric($key), ARRAY_FILTER_USE_KEY);

    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity($original_verbosity);
    }

    $this->responses = $this->normalizeResponses($responses);

    // A conditional prompt this run skips never reaches args(), so its handler
    // is asked directly. Otherwise the answer describing the destination would
    // be replaced by the one describing this run.
    foreach ($this->handlers as $id => $handler) {
      if (!isset($this->discoveredResponses[$id])) {
        $discovered = $handler->discover();

        if ($discovered !== NULL) {
          $this->discoveredResponses[$id] = $discovered;
        }
      }
    }

    // Discovery covers only the handlers that read the destination, so the
    // collected answers fill the rest.
    $this->discoveredResponses = $this->normalizeResponses(array_replace($responses, $this->discoveredResponses));
  }

  /**
   * Fold internal answers into the responses they qualify.
   *
   * @param array $responses
   *   Raw responses keyed by handler ID.
   *
   * @return array
   *   The responses with internal answers merged and removed.
   */
  protected function normalizeResponses(array $responses): array {
    if (isset($responses[Profile::id()]) && $responses[Profile::id()] === Profile::CUSTOM && isset($responses[ProfileCustom::id()])) {
      $responses[Profile::id()] = $responses[ProfileCustom::id()];
    }

    // ProfileCustom is only used for internal merging; always remove it.
    unset($responses[ProfileCustom::id()]);

    if (isset($responses[Theme::id()]) && $responses[Theme::id()] === Theme::CUSTOM && isset($responses[ThemeCustom::id()])) {
      $responses[Theme::id()] = $responses[ThemeCustom::id()];
    }

    // ThemeCustom is only used for internal merging; always remove it.
    unset($responses[ThemeCustom::id()]);

    if (isset($responses[ProvisionType::id()]) && $responses[ProvisionType::id()] === ProvisionType::PROFILE) {
      $responses[DatabaseFetchSource::id()] = DatabaseFetchSource::NONE;
    }

    // Handle Starter when the installer is running in update mode.
    if ($this->config->isVortexProject() && !isset($responses[Starter::id()])) {
      $responses[Starter::id()] = Starter::LOAD_DATABASE_DEMO;
    }

    return $responses;
  }

  /**
   * Get all received responses.
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
    foreach ($this->getProcessHandlers() as $handler) {
      $handler->setResponses($this->responses)->process();
    }

    // Handlers only queue file operations; this is where they are applied.
    File::runDirectoryTasks($this->config->get(Config::TMP));
  }

  /**
   * Render a template download as the destination has it installed.
   *
   * The answers come from discovery against the destination rather than from
   * the choices this run collected, so the render reproduces the project's
   * current configuration even where this run changes it. This keeps the
   * result comparable to the project's own files.
   *
   * @param string $dir
   *   Directory holding an unprocessed template download.
   * @param string $version
   *   Version to stamp into the rendered content.
   */
  public function renderAsInstalled(string $dir, string $version): void {
    $config = clone $this->config;
    $config->set(Config::TMP, $dir, TRUE);
    $config->set(Config::VERSION, $version, TRUE);

    // Handlers bind to the directory they are constructed with, so rendering
    // into a directory other than this run's staging copy needs its own set.
    $manager = new self($config);
    $manager->responses = $this->discoveredResponses;
    $manager->runProcessors();
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

    foreach ($this->getOrderedHandlers() as $handler) {
      $handler_output = $handler->postBuild($result);

      if (is_string($handler_output) && !empty($handler_output)) {
        $output .= $handler_output;
      }
    }

    return $output;
  }

  /**
   * Check if the installation should proceed.
   *
   * @return bool
   *   TRUE if the installation should proceed, FALSE otherwise.
   */
  public function shouldProceed(): bool {
    $proceed = TRUE;

    if (!$this->config->getNoInteraction()) {
      Tui::line(sprintf('Vortex will be installed into your project\'s directory "%s"', $this->config->getDestination()));
      $proceed = Tui::confirm('Proceed with installing Vortex?');
    }

    // Config::PROCEED is a kill switch: when FALSE, the installer does not
    // proceed regardless of the answer received above.
    if (!$this->config->get(Config::PROCEED)) {
      return FALSE;
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
    $values['Custom modules'] = Converter::toList($responses[CustomModules::id()], ', ');
    $values['Theme machine name'] = $responses[Theme::id()] ?? '<empty>';
    if (isset($responses[FrontendBuild::id()])) {
      $values['Build front-end in container'] = Converter::bool($responses[FrontendBuild::id()]);
    }

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

    if ($responses[ProvisionType::id()] === ProvisionType::DATABASE) {
      $values['Database source'] = $responses[DatabaseFetchSource::id()];

      if ($responses[DatabaseFetchSource::id()] === DatabaseFetchSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    if (isset($responses[Migration::id()])) {
      $values['Migration database'] = Converter::bool($responses[Migration::id()]);
      if ($responses[Migration::id()] === TRUE && isset($responses[MigrationFetchSource::id()])) {
        $values['Migration database source'] = $responses[MigrationFetchSource::id()];

        if ($responses[MigrationFetchSource::id()] === MigrationFetchSource::CONTAINER_REGISTRY && isset($responses[MigrationImage::id()])) {
          $values['Migration database container image'] = $responses[MigrationImage::id()];
        }
      }
    }

    $values['Notifications'] = Tui::LIST_SECTION_TITLE;
    $values['Channels'] = Converter::toList($responses[NotificationChannels::id()]);

    $values['Continuous Integration'] = Tui::LIST_SECTION_TITLE;
    $values['CI provider'] = $responses[CiProvider::id()];
    $values['Visual regression testing'] = Converter::bool($responses[VisualRegression::id()]);
    $values['Secret scanning with Gitleaks'] = Converter::bool($responses[Gitleaks::id()]);

    $values['Automations'] = Tui::LIST_SECTION_TITLE;
    $values['Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['Code coverage provider'] = $responses[CodeCoverageProvider::id()];
    $values['Auto-assign PR author'] = Converter::bool($responses[AssignAuthorPr::id()]);
    $values['Auto-add a CONFLICT label to PRs'] = Converter::bool($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['Preserve project documentation'] = Converter::bool($responses[PreserveDocsProject::id()]);

    $values['AI'] = Tui::LIST_SECTION_TITLE;
    $values['AI agent instructions'] = Converter::bool($responses[AiCodeInstructions::id()]);

    $values['Locations'] = Tui::LIST_SECTION_TITLE;
    $values['Current directory'] = $this->config->getRoot();
    $values['Destination directory'] = $this->config->getDestination();
    $values['Vortex repository'] = $this->config->get(Config::REPO);
    $values['Vortex reference'] = $this->config->get(Config::REF);

    return $values;
  }

  /**
   * Get the initialized handlers.
   *
   * @return array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   *   An associative array of handler instances keyed by handler ID.
   */
  public function getHandlers(): array {
    return $this->handlers;
  }

  /**
   * Get all handlers ordered by their prompt chain weight.
   *
   * @return array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   *   An associative array of handler instances keyed by handler ID.
   */
  public function getOrderedHandlers(): array {
    $handlers = $this->handlers;

    uasort($handlers, fn(HandlerInterface $a, HandlerInterface $b): int => $a::weight() <=> $b::weight());

    return $handlers;
  }

  /**
   * Get the handlers that have a prompt, in the order they are prompted.
   *
   * @return array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   *   An associative array of handler instances keyed by handler ID.
   */
  public function getPromptHandlers(): array {
    return array_filter($this->getOrderedHandlers(), fn(HandlerInterface $handler): bool => $handler::section() instanceof PromptSection);
  }

  /**
   * Get all handlers in the order they are processed.
   *
   * @return array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   *   An associative array of handler instances keyed by handler ID.
   */
  public function getProcessHandlers(): array {
    $handlers = $this->handlers;

    uasort($handlers, fn(HandlerInterface $a, HandlerInterface $b): int => $a::processWeight() <=> $b::processWeight());

    return $handlers;
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
    if ($suffix === NULL) {
      $this->currentResponseIndex++;
    }

    $suffix = $suffix !== NULL ? $this->currentResponseIndex . '.' . $suffix : $this->currentResponseIndex;

    return $text . ' ' . Tui::dim('(' . $suffix . '/' . count($this->getPromptHandlers()) . ')');
  }

  /**
   * Collect and initialize handlers.
   */
  protected function initHandlers(): void {
    $dir = __DIR__ . '/Handlers';

    $files = scandir($dir);

    if ($files === FALSE) {
      throw new \RuntimeException(sprintf('Could not read the directory "%s".', $dir));
    }

    $handler_files = array_filter($files, fn(string $file): bool => !in_array($file, ['.', '..'], TRUE));

    $classes = [];
    foreach ($handler_files as $handler_file) {
      $class = 'DrevOps\\VortexInstaller\\Prompts\\Handlers\\' . basename($handler_file, '.php');

      if (!class_exists($class) || !is_subclass_of($class, HandlerInterface::class) || $class === AbstractHandler::class) {
        continue;
      }

      $classes[] = $class;
    }

    // Discover the web root once and set it on all handlers to help with
    // path resolution.
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
   * Resolve prompt overrides from --prompts CLI option.
   *
   * Reads the raw prompt array from Config, validates values against handler
   * types and options, and stores the validated overrides.
   *
   * @throws \RuntimeException
   *   If any prompt value is invalid.
   */
  protected function resolvePromptOverrides(): void {
    $raw = $this->config->get(Config::PROMPTS);

    if (!is_array($raw) || empty($raw)) {
      return;
    }

    $validator = new SchemaValidator($this->handlers);
    $result = $validator->validate($raw);

    if (!empty($result['errors'])) {
      $messages = array_map(fn(array $error): string => sprintf('%s: %s', $error['prompt'], $error['message']), $result['errors']);
      throw new \RuntimeException(sprintf('Invalid --prompts values: %s.', implode('; ', $messages)));
    }

    foreach ($raw as $key => $value) {
      if (isset($this->handlers[$key])) {
        $this->promptOverrides[$key] = $value;
      }
    }
  }

  /**
   * Use the handler's own value when it has one, otherwise prompt for it.
   *
   * @param string $handler_id
   *   The handler ID.
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return mixed
   *   The resolved value or the prompt result.
   */
  protected function promptOrResolve(string $handler_id, array $responses): mixed {
    $handler = $this->handler($handler_id);
    $resolved = $handler->resolvedValue($responses);

    if (is_string($resolved)) {
      $message = $handler->resolvedMessage($responses, $resolved);

      if ($message) {
        Tui::success($message);
      }

      // A resolved value is read from the destination, so it stands in for
      // discovery for handlers that never reach a prompt.
      $this->discoveredResponses[$handler_id] = $resolved;

      return $resolved;
    }

    return $this->prompt($handler_id, $responses);
  }

  /**
   * Dispatch a prompt using the handler's type enum.
   *
   * @param string $handler_id
   *   The handler ID.
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return mixed
   *   The prompt result.
   */
  protected function prompt(string $handler_id, array $responses = []): mixed {
    $fn = $this->handler($handler_id)->type()->promptFunction();

    return $fn(...$this->args($handler_id, NULL, $responses));
  }

  /**
   * Convert handler properties to Laravel prompts.
   *
   * Kept deliberately unoptimized to ease debugging and future changes.
   *
   * @param string $handler_id
   *   The handler ID.
   * @param mixed $default_override
   *   Optional override for the default value (for response dependencies).
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return array
   *   Array of prompt arguments suitable for Laravel prompts.
   */
  protected function args(string $handler_id, mixed $default_override = NULL, array $responses = []): array {
    $handler = $this->handler($handler_id);

    $args = [
      'label' => $this->label($handler->label()),
      'hint' => $handler->hint($responses),
      'placeholder' => $handler->placeholder($responses),
      'transform' => $handler->transform(),
      'validate' => $handler->validate(),
    ];

    $description = $handler::description($responses);
    if ($description !== NULL) {
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

    $default_from_handler = $handler->default($responses);
    $default_from_prompts = $this->promptOverrides[$handler_id] ?? NULL;
    $default_from_discovery = $handler->discover();

    if ($default_from_discovery !== NULL) {
      $this->discoveredResponses[$handler_id] = $default_from_discovery;
    }

    if ($default_from_prompts !== NULL) {
      $default = $default_from_prompts;
    }
    elseif ($default_from_discovery !== NULL) {
      $default = $default_from_discovery;
    }
    elseif ($default_override !== NULL) {
      $default = $default_override;
    }
    else {
      $default = $default_from_handler;
    }

    if ($default !== NULL && $default !== '') {
      $args['default'] = $default;
    }

    return array_filter($args, fn($value): bool => $value !== NULL);
  }

  /**
   * Get a registered handler.
   *
   * @param string $id
   *   The handler ID.
   *
   * @return \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface
   *   The handler instance.
   *
   * @throws \RuntimeException
   *   If no handler is registered for the ID.
   */
  protected function handler(string $id): HandlerInterface {
    if (!array_key_exists($id, $this->handlers)) {
      throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
    }

    return $this->handlers[$id];
  }

}
