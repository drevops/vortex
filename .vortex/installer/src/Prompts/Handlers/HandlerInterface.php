<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

/**
 * Interface HandlerInterface.
 *
 * The interface for the prompt handlers.
 *
 * @package DrevOps\VortexInstaller\Prompts\Handlers
 */
interface HandlerInterface {

  /**
   * The unique identifier of the handler.
   */
  public static function id(): string;

  /**
   * Discover the value from the environment.
   *
   * @return null|string|bool|array
   *   The value of the environment variable.
   */
  public function discover(): null|string|bool|array;

  /**
   * Process the discovered value once all the responses are collected.
   */
  public function process(): void;

  /**
   * Set the responses.
   *
   * @param array $responses
   *   The responses.
   *
   * @return $this
   */
  public function setResponses(array $responses): static;

  /**
   * Set the webroot directory name.
   *
   * @param string $webroot
   *   The webroot directory name.
   *
   * @return $this
   */
  public function setWebroot(string $webroot): static;

  // New prompt property methods - handlers provide values/callbacks

  /**
   * Get the prompt label.
   *
   * @return string
   *   The label for the prompt.
   */
  public function getLabel(): string;

  /**
   * Get the prompt hint.
   *
   * @return string|null
   *   The hint text for the prompt, or null if none.
   */
  public function getHint(): ?string;

  /**
   * Get the prompt placeholder.
   *
   * @return string|null
   *   The placeholder text for the prompt, or null if none.
   */
  public function getPlaceholder(): ?string;

  /**
   * Get the default value for the prompt.
   *
   * @return mixed
   *   The default value for the prompt.
   */
  public function getDefault(): mixed;

  /**
   * Get the transform callback for the prompt.
   *
   * @return callable|null
   *   The transform callback, or null if none.
   */
  public function getTransform(): ?callable;

  /**
   * Get the validate callback for the prompt.
   *
   * @return callable|null
   *   The validate callback, or null if none.
   */
  public function getValidate(): ?callable;

  /**
   * Get whether the prompt is required.
   *
   * @return bool
   *   TRUE if the prompt is required, FALSE otherwise.
   */
  public function getRequired(): bool;

  /**
   * Get the options for select/multiselect prompts.
   *
   * @return array|null
   *   The options array, or null if not applicable.
   */
  public function getOptions(): ?array;

  /**
   * Get the intro text for section grouping.
   *
   * @return string|null
   *   The intro text, or null if none.
   */
  public function getIntro(): ?string;

  /**
   * Check if this handler is conditional.
   *
   * @return bool
   *   TRUE if this handler should only be shown conditionally.
   */
  public function isConditional(): bool;

  /**
   * Get the condition callback for conditional prompts.
   *
   * @return callable|null
   *   The condition callback, or null if not conditional.
   */
  public function getCondition(): ?callable;

  /**
   * Context-aware options that can be filtered based on current responses.
   * 
   * This allows handlers to encapsulate business logic for filtering options
   * based on other responses, rather than PromptManager making these decisions.
   *
   * @param array $responses Current form responses
   * @return array|null Filtered options based on current context
   */
  public function getOptionsForContext(array $responses): ?array;

  /**
   * Context-aware default that can be calculated based on current responses.
   * 
   * This allows handlers to encapsulate business logic for determining defaults
   * based on other responses, rather than PromptManager making these decisions.
   *
   * @param array $responses Current form responses  
   * @return mixed Default value based on current context
   */
  public function getDefaultForContext(array $responses): mixed;

}
