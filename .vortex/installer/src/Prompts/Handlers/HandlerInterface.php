<?php

namespace DrevOps\Installer\Prompts\Handlers;

interface HandlerInterface {

  public static function id(): string;

  // Discover is called from default() when the question is asked.
  public function discover(): null|string|bool|iterable;

  // Process is called when all answers were collected.
  public function process():void;

}
