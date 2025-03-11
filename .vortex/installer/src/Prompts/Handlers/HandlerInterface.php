<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

/**
 * Interface HandlerInterface.
 *
 * The interface for the prompt handlers.
 *
 * @package DrevOps\Installer\Prompts\Handlers
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

}
