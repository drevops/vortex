<?php

declare(strict_types=1);

namespace DrevOps\Customizer;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Process\Processor;
use DrevOps\Customizer\Resolver\InputResolver;
use DrevOps\Customizer\Schema\AgentHelp;
use DrevOps\Customizer\Schema\SchemaGenerator;
use DrevOps\Customizer\Schema\SchemaValidator;
use DrevOps\Customizer\Tui\PanelController;
use DrevOps\Customizer\Tui\Terminal;
use DrevOps\Customizer\Tui\Theme;

/**
 * The one-class entry point for building and running a customizer.
 *
 * Wraps the config loader, engine, input resolver, schema tools and TUI so a
 * consumer can load a config and collect answers - headlessly or interactively
 * - in a single call, without touching the internals. Those internals stay
 * reachable via config(), engine() and registry() when a consumer does want
 * finer control.
 *
 * @package DrevOps\Customizer
 */
final class Customizer {

  /**
   * The handler registry.
   */
  protected HandlerRegistry $registry;

  /**
   * The engine.
   */
  protected Engine $engine;

  /**
   * Construct a customizer.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration.
   * @param string[] $handler_namespaces
   *   Namespaces the engine searches for field handlers, in order.
   * @param string $envPrefix
   *   The env-variable prefix for per-question overrides.
   */
  public function __construct(protected Config $config, array $handler_namespaces = [], protected string $envPrefix = 'CUSTOMIZER_') {
    $this->registry = new HandlerRegistry($handler_namespaces);
    $this->engine = new Engine($this->config, $this->registry);
  }

  /**
   * Build a customizer from one or more YAML config files (later wins).
   *
   * @param string[] $paths
   *   Paths to YAML config files, merged in order.
   * @param string[] $handler_namespaces
   *   Namespaces the engine searches for field handlers, in order.
   * @param string $env_prefix
   *   The env-variable prefix for per-question overrides.
   *
   * @return self
   *   The customizer.
   */
  public static function fromFiles(array $paths, array $handler_namespaces = [], string $env_prefix = 'CUSTOMIZER_'): self {
    return new self((new ConfigLoader())->loadFiles($paths), $handler_namespaces, $env_prefix);
  }

  /**
   * Collect answers non-interactively from a JSON payload and the environment.
   *
   * @param string $prompts
   *   Answers as a JSON string (or empty to rely on defaults and environment).
   * @param string $directory
   *   The target directory (defaults to the current working directory).
   * @param bool $update
   *   Whether to enable discovery against an existing project.
   * @param string $version
   *   The version stamped into the context.
   *
   * @return \DrevOps\Customizer\Answers\Answers
   *   The collected answers.
   */
  public function collect(string $prompts = '', string $directory = '', bool $update = FALSE, string $version = ''): Answers {
    $inputs = (new InputResolver($this->envPrefix))->resolve($this->config->fields(), $prompts, getenv());
    $this->engine->collect($inputs, $this->context($directory, $update, $version));

    return $this->engine->answers();
  }

  /**
   * Collect answers interactively through the panel TUI.
   *
   * @param string $theme
   *   The theme name or class (defaults to the config's theme, then dark).
   * @param string $banner
   *   An optional start banner.
   * @param string $version
   *   An optional version shown below the banner and stamped into the context.
   * @param string $directory
   *   The target directory (defaults to the current working directory).
   * @param \DrevOps\Customizer\Tui\Terminal|null $terminal
   *   The terminal to drive (defaults to a real one).
   *
   * @return \DrevOps\Customizer\Answers\Answers
   *   The collected answers.
   */
  public function run(string $theme = '', string $banner = '', string $version = '', string $directory = '', ?Terminal $terminal = NULL): Answers {
    // @codeCoverageIgnoreStart
    $this->engine->collect([], $this->context($directory, FALSE, $version));

    // The theme comes from the argument, then the config, then dark; the banner
    // from the argument, then the config.
    $theme_name = $theme !== '' ? $theme : ($this->config->theme !== '' ? $this->config->theme : 'dark');
    $banner_text = $banner !== '' ? $banner : $this->config->banner;

    $controller = new PanelController(
      $this->config,
      Theme::create($theme_name),
      $this->engine->answers()->values,
      $this->engine->answers()->provenance,
      $banner_text,
      $version,
    );

    return $controller->run($terminal ?? new Terminal());
    // @codeCoverageIgnoreEnd
  }

  /**
   * Apply the collected answers to the target project via the handlers.
   *
   * Runs each field's handler process() in the config-driven order (field
   * weights plus any declared processors).
   *
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context (its directory is the target project).
   */
  public function process(array $answers, Context $context): void {
    (new Processor())->apply($this->config, $this->registry, $answers, $context);
  }

  /**
   * The JSON schema describing the questions.
   *
   * @return array<string,mixed>
   *   The schema.
   */
  public function schema(): array {
    return (new SchemaGenerator($this->config))->generate();
  }

  /**
   * Agent-facing help for driving the customizer non-interactively.
   *
   * @return string
   *   The help text.
   */
  public function agentHelp(): string {
    return (new AgentHelp($this->config, $this->envPrefix))->generate();
  }

  /**
   * Validate an answer set against the schema.
   *
   * @param array<string,mixed> $answers
   *   The answers to validate.
   *
   * @return list<string>
   *   The validation errors (empty when valid).
   */
  public function validate(array $answers): array {
    return (new SchemaValidator($this->config))->validate($answers);
  }

  /**
   * The configuration.
   *
   * @return \DrevOps\Customizer\Config\Config
   *   The configuration.
   */
  public function config(): Config {
    return $this->config;
  }

  /**
   * The engine.
   *
   * @return \DrevOps\Customizer\Engine\Engine
   *   The engine.
   */
  public function engine(): Engine {
    return $this->engine;
  }

  /**
   * The handler registry.
   *
   * @return \DrevOps\Customizer\Handler\HandlerRegistry
   *   The handler registry.
   */
  public function registry(): HandlerRegistry {
    return $this->registry;
  }

  /**
   * Build a run context for the target directory.
   *
   * @param string $directory
   *   The target directory (empty for the current working directory).
   * @param bool $update
   *   Whether discovery is enabled.
   * @param string $version
   *   The version stamped into the context.
   *
   * @return \DrevOps\Customizer\Handler\Context
   *   The context.
   */
  protected function context(string $directory, bool $update, string $version): Context {
    return new Context($directory !== '' ? $directory : (string) getcwd(), [], $update, $version);
  }

}
