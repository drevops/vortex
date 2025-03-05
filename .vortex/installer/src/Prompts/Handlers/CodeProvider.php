<?php

namespace DrevOps\Installer\Prompts\Handlers;

class CodeProvider extends AbstractHandler {
  const GITHUB = 'github';

  public static function id(): string {
    return 'code_provider';
  }


  public function discover(): null|string|bool|iterable {
    return NULL;
  }


  public function process():void  {
    // @todo Implement this.
  }

}
