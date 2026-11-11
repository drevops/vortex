<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\PromptType;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Tui;

abstract class AbstractHandler implements HandlerInterface {

  /**
   * The destination directory.
   */
  protected string $destinationDir;

  /**
   * The temporary directory.
   */
  protected string $tmpDir;

  /**
   * The webroot directory.
   */
  protected string $webroot;

  /**
   * The response value for the current handler.
   *
   * @var string|bool|array
   */
  protected null|string|bool|array $response = NULL;

  /**
   * Array of all responses.
   */
  protected array $responses;

  /**
   * The answer supplied with the --prompts option.
   */
  protected null|string|bool|array $override = NULL;

  /**
   * Constructor.
   */
  public function __construct(
    protected Config $config,
  ) {
    $this->destinationDir = $this->config->getDestination();
    $this->tmpDir = $this->config->get(Config::TMP);
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    $reflector = new \ReflectionClass(static::class);

    $filename = $reflector->getFileName();

    if ($filename === FALSE) {
      throw new \RuntimeException(sprintf('Unable to determine the filename of the handler class "%s".', static::class));
    }

    return Converter::machine(Converter::pascal2snake(str_replace('Handler', '', basename($filename, '.php'))));
  }

  /**
   * {@inheritdoc}
   */
  public static function envName(): string {
    return Converter::constant('VORTEX_INSTALLER_PROMPT_' . static::id());
  }

  /**
   * {@inheritdoc}
   */
  public function type(): PromptType {
    $options = $this->options([]);

    if (is_array($options)) {
      if (array_is_list($options)) {
        return PromptType::Suggest;
      }

      $default = $this->default([]);

      if (is_array($default)) {
        return PromptType::MultiSelect;
      }

      return PromptType::Select;
    }

    $default = $this->default([]);

    if (is_bool($default)) {
      return PromptType::Confirm;
    }

    return PromptType::Text;
  }

  /**
   * {@inheritdoc}
   */
  public function dependsOn(): ?array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedValue(array $responses): null|string|bool|array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses, mixed $resolved): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOverride(null|string|bool|array $override): static {
    $this->override = $override;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return static::id();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(): string {
    return $this->type()->value;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(array $results): bool {
    return $this->shouldRun($results);
  }

  /**
   * {@inheritdoc}
   */
  public function arguments(array $results): array {
    $type = $this->type();
    $options = $this->options($results);

    $arguments = [
      'label' => $this->label(),
      'help' => $this->hint($results),
      'placeholder' => $this->placeholder($results),
      'transform' => $this->transform(),
      'validate' => $this->validate(),
      'description' => static::description($results),
      'required' => $this->isRequired() ?: NULL,
      'options' => $options,
      'scroll' => is_array($options) ? 10 : NULL,
      // A discovered list keeps the order the project already has it in.
      'order' => $type === PromptType::MultiSelect ? 'default' : NULL,
    ];

    // A value read from the destination answers the step without asking, and
    // may name a profile or theme outside the options.
    $resolved = $this->resolvedValue($results);

    if (is_string($resolved)) {
      $message = $this->resolvedMessage($results, $resolved);

      if ($message) {
        Tui::success($message);
      }

      if (is_array($options) && !array_key_exists($resolved, $options)) {
        $arguments['options'] = $options + [$resolved => $resolved];
      }

      $arguments['discovered'] = $resolved;

      return array_filter($arguments, fn(mixed $value): bool => $value !== NULL);
    }

    $default = $this->override ?? $this->discover() ?? $this->default($results);

    if ($default !== NULL && $default !== '') {
      $arguments['default'] = $default;
    }

    return array_filter($arguments, fn(mixed $value): bool => $value !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setResponses(array $responses): static {
    $this->responses = $responses;
    $this->setWebroot($responses[Webroot::id()] ?? Webroot::WEB);

    // Shorthand for the current handler's response. Some handlers process
    // empty responses, so checking for the presence of the value is up to the
    // handler's processor.
    $this->response = $this->responses[static::id()] ?? NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebroot(string $webroot): static {
    $this->webroot = $webroot;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function postBuild(string $result): ?string {
    return NULL;
  }

  /**
   * Check that Vortex is installed for this project.
   *
   * @todo Move to another place.
   */
  protected function isInstalled(): bool {
    return $this->config->isVortexProject();
  }

  /**
   * Get response as string, validating it's scalar first.
   *
   * @return string
   *   The response cast to string.
   *
   * @throws \RuntimeException
   *   When response is not scalar.
   */
  protected function getResponseAsString(): string {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type: expected scalar value.');
    }

    return (string) $this->response;
  }

  /**
   * Get response as array, validating type first.
   *
   * @return array
   *   The response as array.
   *
   * @throws \RuntimeException
   *   When response is not an array.
   */
  protected function getResponseAsArray(): array {
    if (!is_array($this->response)) {
      throw new \RuntimeException('Invalid response type: expected array.');
    }

    return $this->response;
  }

  /**
   * Get response as boolean, validating type first.
   *
   * @return bool
   *   The response as boolean.
   *
   * @throws \RuntimeException
   *   When response is not boolean.
   */
  protected function getResponseAsBool(): bool {
    if (!is_bool($this->response)) {
      throw new \RuntimeException('Invalid response type: expected boolean.');
    }

    return $this->response;
  }

}
