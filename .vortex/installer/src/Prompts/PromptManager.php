<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use DrevOps\VortexInstaller\Prompts\Handlers\AbstractHandler;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeReview;
use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CustomModules;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesAutomerge;
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
use DrevOps\VortexInstaller\Prompts\Handlers\Storybook;
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

/**
 * Centralized place for providing prompts and their processing.
 */
class PromptManager {

  /**
   * Total number of top-level responses.
   *
   * Counted from the steps in runPrompts() and used to display the progress of
   * the prompts.
   */
  protected int $totalResponses = 0;

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
   *
   * @return bool
   *   TRUE when every prompt was answered, FALSE when the user cancelled one.
   */
  public function runPrompts(): bool {
    // Quiet the TUI output in non-interactive mode; the original verbosity is
    // restored after the prompts complete.
    $original_verbosity = Tui::output()->getVerbosity();
    if ($this->config->getNoInteraction()) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    $sections = $this->steps();

    $this->totalResponses = array_sum(array_map(count(...), $sections));

    $responses = [];

    foreach ($sections as $section => $steps) {
      Tui::info($section);

      foreach ($steps as $id => $step) {
        if (!$this->handler($id)->shouldRun($responses)) {
          $responses[$id] = NULL;
          continue;
        }

        $response = $step($responses);

        // A widget answers NULL only when the user cancels it.
        if ($response === NULL) {
          Tui::output()->setVerbosity($original_verbosity);

          return FALSE;
        }

        $responses[$id] = $response;
      }
    }

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

    return TRUE;
  }

  /**
   * Define the prompts in the order they are asked, grouped by section.
   *
   * Every step runs only when its handler's shouldRun() accepts the responses
   * collected so far; a skipped step responds with NULL.
   *
   * @return array<string, array<string, \Closure(array): mixed>>
   *   Steps keyed by handler ID, grouped under their section heading.
   */
  protected function steps(): array {
    return [
      'General information' => [
        Name::id() => fn(array $r): mixed => $this->prompt(Name::class),
        MachineName::id() => fn(array $r): mixed => $this->prompt(MachineName::class, $r),
        Org::id() => fn(array $r): mixed => $this->prompt(Org::class, $r),
        OrgMachineName::id() => fn(array $r): mixed => $this->prompt(OrgMachineName::class, $r),
        Domain::id() => fn(array $r): mixed => $this->prompt(Domain::class, $r),
      ],
      'Drupal' => [
        Starter::id() => fn(array $r): mixed => $this->prompt(Starter::class, $r),
        Profile::id() => fn(array $r): ?string => $this->resolveOrPrompt(Profile::id(), $r, fn(): mixed => $this->prompt(Profile::class)),
        ProfileCustom::id() => fn(array $r): mixed => $this->prompt(ProfileCustom::class),
        Modules::id() => fn(array $r): mixed => $this->prompt(Modules::class, $r),
        ModulePrefix::id() => fn(array $r): mixed => $this->prompt(ModulePrefix::class, $r),
        CustomModules::id() => fn(array $r): mixed => $this->prompt(CustomModules::class, $r),
        Theme::id() => fn(array $r): ?string => $this->resolveOrPrompt(Theme::id(), $r, fn(): mixed => $this->prompt(Theme::class)),
        ThemeCustom::id() => fn(array $r): mixed => $this->prompt(ThemeCustom::class, $r),
        FrontendBuild::id() => fn(array $r): mixed => $this->prompt(FrontendBuild::class, $r),
        Storybook::id() => fn(array $r): mixed => $this->prompt(Storybook::class, $r),
      ],
      'Code repository' => [
        CodeProvider::id() => fn(array $r): mixed => $this->prompt(CodeProvider::class),
        VersionScheme::id() => fn(array $r): mixed => $this->prompt(VersionScheme::class),
      ],
      'Environment' => [
        Timezone::id() => fn(array $r): mixed => $this->prompt(Timezone::class),
        Services::id() => fn(array $r): mixed => $this->prompt(Services::class),
        Tools::id() => fn(array $r): mixed => $this->prompt(Tools::class),
      ],
      'Hosting' => [
        HostingProvider::id() => fn(array $r): mixed => $this->prompt(HostingProvider::class),
        HostingProjectName::id() => fn(array $r): mixed => $this->prompt(HostingProjectName::class, $r),
        Webroot::id() => fn(array $r): ?string => $this->resolveOrPrompt(Webroot::id(), $r, fn(): mixed => $this->prompt(Webroot::class, $r)),
      ],
      'Deployment' => [
        DeployTypes::id() => fn(array $r): mixed => $this->prompt(DeployTypes::class, $r),
      ],
      'Workflow' => [
        ProvisionType::id() => fn(array $r): mixed => $this->prompt(ProvisionType::class),
        DatabaseFetchSource::id() => fn(array $r): mixed => $this->prompt(DatabaseFetchSource::class, $r),
        DatabaseImage::id() => fn(array $r): mixed => $this->prompt(DatabaseImage::class, $r),
        Migration::id() => fn(array $r): mixed => $this->prompt(Migration::class),
        MigrationFetchSource::id() => fn(array $r): mixed => $this->prompt(MigrationFetchSource::class, $r),
        MigrationImage::id() => fn(array $r): mixed => $this->prompt(MigrationImage::class, $r),
      ],
      'Notifications' => [
        NotificationChannels::id() => fn(array $r): mixed => $this->prompt(NotificationChannels::class),
      ],
      'Continuous Integration' => [
        CiProvider::id() => fn(array $r): mixed => $this->prompt(CiProvider::class, $r),
        VisualRegression::id() => fn(array $r): mixed => $this->prompt(VisualRegression::class),
        Gitleaks::id() => fn(array $r): mixed => $this->prompt(Gitleaks::class),
      ],
      'Automations' => [
        DependencyUpdatesProvider::id() => fn(array $r): mixed => $this->prompt(DependencyUpdatesProvider::class),
        DependencyUpdatesAutomerge::id() => fn(array $r): mixed => $this->prompt(DependencyUpdatesAutomerge::class, $r),
        CodeCoverageProvider::id() => fn(array $r): mixed => $this->prompt(CodeCoverageProvider::class),
        AssignAuthorPr::id() => fn(array $r): mixed => $this->prompt(AssignAuthorPr::class),
        LabelMergeConflictsPr::id() => fn(array $r): mixed => $this->prompt(LabelMergeConflictsPr::class),
      ],
      'Documentation' => [
        PreserveDocsProject::id() => fn(array $r): mixed => $this->prompt(PreserveDocsProject::class),
      ],
      'AI' => [
        AiCodeInstructions::id() => fn(array $r): mixed => $this->prompt(AiCodeInstructions::class),
        AiCodeReview::id() => fn(array $r): mixed => $this->prompt(AiCodeReview::class),
      ],
    ];
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

    // Every handler is asked; those with nothing to report return NULL. The
    // reverse of the processing order asks them in roughly the order they were
    // prompted, so HostingProvider reports before CiProvider.
    foreach (array_reverse($this->getProcessHandlers()) as $handler) {
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
    if (isset($responses[Storybook::id()])) {
      $values['Storybook component library'] = Converter::bool($responses[Storybook::id()]);
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
    if (isset($responses[DependencyUpdatesAutomerge::id()])) {
      $values['Auto-merge dependency updates'] = Converter::toList($responses[DependencyUpdatesAutomerge::id()], ', ');
    }
    $values['Code coverage provider'] = $responses[CodeCoverageProvider::id()];
    $values['Auto-assign PR author'] = Converter::bool($responses[AssignAuthorPr::id()]);
    $values['Auto-add a CONFLICT label to PRs'] = Converter::bool($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['Preserve project documentation'] = Converter::bool($responses[PreserveDocsProject::id()]);

    $values['AI'] = Tui::LIST_SECTION_TITLE;
    $values['AI agent instructions'] = Converter::bool($responses[AiCodeInstructions::id()]);
    $values['AI code review'] = Converter::bool($responses[AiCodeReview::id()]);

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

    return $text . ' ' . Tui::dim('(' . $suffix . '/' . $this->totalResponses . ')');
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
   * Dispatch a prompt using the handler's type enum.
   *
   * @param string $handler_class
   *   The handler class name.
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return mixed
   *   The prompt result.
   */
  protected function prompt(string $handler_class, array $responses = []): mixed {
    $type = $this->handler($handler_class::id())->type();
    $args = $this->args($handler_class, NULL, $responses);

    $response = $type->promptFunction()(...$args);

    // The widget answers in option order. Keeping the selected defaults in
    // their own order first leaves a discovered list in the order the project
    // already has it.
    if ($type === PromptType::MultiSelect && is_array($response) && is_array($args['default'] ?? NULL)) {
      $response = array_values(array_unique(array_merge(array_intersect($args['default'], $response), $response)));
    }

    return $response;
  }

  /**
   * Convert handler properties to Prompty widget arguments.
   *
   * Kept deliberately unoptimized to ease debugging and future changes.
   *
   * @param string $handler_class
   *   The handler class name.
   * @param mixed $default_override
   *   Optional override for the default value (for response dependencies).
   * @param array $responses
   *   Current form responses for context-aware methods.
   *
   * @return array
   *   Array of named arguments for the handler's Prompty widget.
   */
  protected function args(string $handler_class, mixed $default_override = NULL, array $responses = []): array {
    $id = $handler_class::id();
    $handler = $this->handler($id);

    $args = [
      'label' => $this->label($handler->label()),
      'help' => $handler->hint($responses),
      'placeholder' => $handler->placeholder($responses),
      'transform' => $handler->transform(),
      'validate' => $this->validator($handler),
      'description' => $handler::description($responses),
    ];

    $options = $handler->options($responses);
    if (is_array($options)) {
      $args['options'] = $options;
      $args['scroll'] = 10;
    }

    $default_from_handler = $handler->default($responses);
    $default_from_prompts = $this->promptOverrides[$id] ?? NULL;
    $default_from_discovery = $handler->discover();

    if ($default_from_discovery !== NULL) {
      $this->discoveredResponses[$id] = $default_from_discovery;
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

    // The widgets reject a default outside the options, so a default that no
    // longer matches one is dropped and the widget starts from its first
    // option.
    if (is_array($options) && $handler->type() === PromptType::Select && !array_key_exists((string) $default, $options)) {
      $default = NULL;
    }

    if (is_array($options) && $handler->type() === PromptType::MultiSelect && is_array($default)) {
      $default = array_values(array_filter($default, fn($value): bool => array_key_exists($value, $options)));
    }

    if ($default !== NULL && $default !== '') {
      $args['default'] = $default;
    }

    return array_filter($args, fn($value): bool => $value !== NULL);
  }

  /**
   * Build the validator for a handler's prompt.
   *
   * A required prompt rejects an empty answer before the handler's own
   * validation runs.
   *
   * @param \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface $handler
   *   The handler.
   *
   * @return \Closure|null
   *   The validator, or NULL when the answer is not validated.
   */
  protected function validator(HandlerInterface $handler): ?\Closure {
    $validate = $handler->validate();
    $is_required = $handler->isRequired();

    if ($validate === NULL && !$is_required) {
      return NULL;
    }

    return function (mixed $value) use ($validate, $is_required): ?string {
      if ($is_required && ($value === '' || $value === [])) {
        return 'Required.';
      }

      return $validate === NULL ? NULL : $validate($value);
    };
  }

  /**
   * Resolve a value via handler or prompt the user.
   *
   * @param string $handler_id
   *   The handler ID.
   * @param array $r
   *   Current form responses for context-aware methods.
   * @param callable $prompt
   *   The prompt callable to use if the value is not resolved.
   *
   * @return string|null
   *   The resolved value, or NULL when the prompt was cancelled.
   */
  protected function resolveOrPrompt(string $handler_id, array $r, callable $prompt): ?string {
    $handler = $this->handler($handler_id);
    $resolved = $handler->resolvedValue($r);

    if (is_string($resolved)) {
      $message = $handler->resolvedMessage($r, $resolved);

      if ($message) {
        Tui::success($message);
      }

      // A resolved value is read from the destination, so it stands in for
      // discovery for handlers that never reach a prompt.
      $this->discoveredResponses[$handler_id] = $resolved;

      return $resolved;
    }

    $value = $prompt();

    return $value === NULL ? NULL : (string) $value;
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
