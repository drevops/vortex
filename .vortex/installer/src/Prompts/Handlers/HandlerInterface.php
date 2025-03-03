<?php

namespace DrevOps\Installer\Prompts\Handlers;

interface HandlerInterface {

  // Discover is called from default() when the question is asked.
  public function discover(): ?string;

  // Process is called when all answers were collected.
  public function process():void;

}
