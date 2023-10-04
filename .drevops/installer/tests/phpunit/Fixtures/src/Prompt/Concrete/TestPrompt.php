<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;

class TestPrompt extends AbstractPrompt {

  const ID = 'test';

  public static function title() {
    return 'Fixture title';
  }

  public static function question() {
    return 'Fixture question';
  }

  public function ask(Config $config, Answers $answers) {
    return 'Fixture answer';
  }

  public static function getFormattedValue(mixed $value): string {
   return 'Fixture formatted value';
  }

}
