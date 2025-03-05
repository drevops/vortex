<?php

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
   *
   * @return string
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
  public function process():void;

}
