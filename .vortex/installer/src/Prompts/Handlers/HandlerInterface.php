<?php

namespace DrevOps\Installer\Prompts\Handlers;

interface HandlerInterface {

  public function discover();

  public function process(array $responses, string $dir):void;

}
