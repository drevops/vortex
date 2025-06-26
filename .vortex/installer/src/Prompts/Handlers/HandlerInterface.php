<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\handlers\Handlers;

/**
 * Interface HandlerInterface.
 *
 * The interface for the handler handlers.
 *
 * @package DrevOps\VortexInstaller\handlers\Handlers
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
   * Optional placeholder.
   *
   * @return string|null
   *   The placeholder text for the handler, or NULL if none.
   */
  public function placeholder(): ?string;

  /**
   * Optional hint for the handler.
   *
   * @return string|null
   *   The hint text for the handler, or NULL if none.
   */
  public function hint(): ?string;

  public static function explanation(): ?string;

  /**
   * Get the options for select/multiselect handlers.
   *
   * @return array|null
   *   The options array, or null if not applicable.
   */
  public function options(): ?array;

  public function optionsAlter(array &$options, array $responses): void;

  /**
   * Get the condition callback for conditional handlers.
   *
   * @return callable|null
   *   The condition callback, or null if not conditional.
   */
  public function condition(): ?callable;

  /**
   * The default value for the handler.
   *
   * @return mixed
   *   The default value for the handler.
   */
  public function default(): mixed;

  public function defaultAlter(mixed &$default, array $responses): void;

  /**
   * Get whether the handler's value is required.
   *
   * @return bool
   *   TRUE if the handler is required, FALSE otherwise.
   */
  public function isRequired(): bool;

  /**
   * Discover the value from the environment.
   *
   * @return null|string|bool|array
   *   The value of the environment variable.
   */
  public function discover(): null|string|bool|array;

  /**
   * The validate callback for the handler.
   *
   * @return callable|null
   *   The validate callback, or null if none.
   */
  public function validate(): ?callable;

  /**
   * Get the transform callback for the handler.
   *
   * @return callable|null
   *   The transform callback, or null if none.
   */
  public function transform(): ?callable;

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

  /**
   * Get a resolved value if this handler's value is already determined.
   *
   * If this returns a non-empty value, the handlerManager should use this value
   * instead of handlering the user for input. This allows handlers to
   * encapsulate logic for when values are discovered from environment,
   * auto-selected based on other responses, or otherwise pre-determined.
   *
   * @param array $responses
   *   Current form responses for context-aware resolution.
   *
   * @return string|bool|array|null
   *   The resolved value if determined, null/empty if user input is needed.
   */
  public function resolved(array $responses): null|string|bool|array;

  /**
   * Get a message to display when showing the resolved value.
   *
   * This is used by handlerManager to show an appropriate message (via info(), ok(), etc.)
   * when using a resolved value instead of handlering for input.
   *
   * @param array $responses
   *   Current form responses for context-aware message generation.
   *
   * @return string|null
   *   The message to display, or null if no message needed.
   */
  public function resolvedMessage(array $responses): ?string;

}
