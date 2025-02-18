<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use Laravel\Prompts\Key;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;

class ResponsesTest extends UnitTestBase {

  public function testResponses() {
    $output = new BufferedConsoleOutput();
    Prompt::fake(array_merge(
      mb_str_split('myproject'), [Key::ENTER],
    ));

    $pm = new PromptManager($output);
    $responses = $pm->getResponses(new Config());



    $this->assertEquals([
      'name' => 'myproject',
    ], $responses);
  }

}
