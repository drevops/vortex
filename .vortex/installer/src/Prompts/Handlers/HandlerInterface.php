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
   * Label for of the handler.
   *
   * @return string
   *   The label for the handler.
   */
  public function label(): string;

  /**
   * Optional description for the handler.
   *
   * @param array $responses
   *   Array of collected responses.
   *
   * @return string|null
   *   The description text, or NULL if not applicable.
   */
  public static function description(array $responses): ?string;

  /**
   * Optional hint for the handler.
   *
   * @param array $responses
   *   Array of collected responses.
   *
   * @return string|null
   *   The hint text for the handler, or NULL if none.
   */
  public function hint(array $responses): ?string;

  /**
   * Optional placeholder.
   *
   * @return string|null
   *   The placeholder text for the handler, or NULL if none.
   */
  public function placeholder(array $responses): ?string;

  /**
   * Get whether the handler's value is required.
   *
   * @return bool
   *   TRUE if the handler is required, FALSE otherwise.
   */
  public function isRequired(): bool;

  /**
   * Get the options for select/multiselect handlers.
   *
   * @param array $responses
   *   Array of collected responses.
   *
   * @return array|null
   *   The options array, or null if not applicable.
   */
  public function options(array $responses): ?array;

  /**
   * Check if the handler should run based on collected responses.
   *
   * @param array $responses
   *   Array of collected responses.
   *
   * @return bool
   *   The condition callback, or null if not conditional.
   */
  public function shouldRun(array $responses): bool;

  /**
   * The default value for the handler.
   *
   * @param array $responses
   *   Array of collected responses.
   *
   * @return string|bool|array|null
   *   The default value for the handler.
   */
  public function default(array $responses): null|string|bool|array;

  /**
   * Discover the value from the environment.
   *
   * @return null|string|bool|array
   *   The value of the environment variable.
   */
  public function discover(): null|string|bool|array;

  /**
   * The validate callback.
   *
   * @return callable|null
   *   The validate callback, or null if none.
   */
  public function validate(): ?callable;

  /**
   * The transform callback.
   *
   * @return callable|null
   *   The transform callback, or null if none.
   */
  public function transform(): ?callable;

  /**
   * Get a resolved value if this handler's value is already determined.
   *
   * If this returns a non-empty value, the caller should use this value
   * instead of prompting the user for input. This allows handlers to
   * encapsulate logic for when values are discovered from the environment,
   * auto-selected based on other responses, or otherwise pre-determined.
   *
   * @param array $responses
   *   Current form responses for context-aware resolution.
   *
   * @return string|bool|array|null
   *   The resolved value if determined, null/empty if user input is needed.
   */
  public function resolvedValue(array $responses): null|string|bool|array;

  /**
   * Get a message to display when showing the resolved value.
   *
   * This is used by handlerManager to show an appropriate message (via
   * info(), ok(), etc.) when using a resolved value instead of handlering
   * for input.
   *
   * @param array $responses
   *   Current form responses for context-aware message generation.
   * @param mixed $resolved
   *   The resolved value from resolvedValue().
   *
   * @return string|null
   *   The message to display, or null if no message needed.
   */
  public function resolvedMessage(array $responses, mixed $resolved): ?string;

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

  /**
   * Process the discovered value once all the responses are collected.
   */
  public function process(): void;

  /**
   * Actions to perform and messages to print after installation is complete.
   */
  public function postInstall(): ?string;

}
