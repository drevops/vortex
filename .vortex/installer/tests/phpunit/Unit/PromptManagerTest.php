<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use Laravel\Prompts\Key;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;
use ReflectionClass;

/**
 * @coversDefaultClass \DrevOps\Installer\Prompts\PromptManager
 */
class PromptManagerTest extends UnitTestBase {

  /**
   * Test responses.
   *
   * @covers ::prompt()
   * @covers ::getResponses
   * @dataProvider dataProviderPrompt
   */
  public function testPrompt(array $responses, array $expected) {
    $output = new BufferedConsoleOutput();
    $config = new Config();

    $pm = new PromptManager($output, $config);
    $this->fakePrompt($responses);
    $c = $output->content();
    $a = Prompt::content();

    Prompt::validateUsing(function (Prompt $prompt) {
      if (is_callable($prompt->validate)) {
        $error = ($prompt->validate)($prompt->value());
        if ($error) {
          throw new \RuntimeException(sprintf('Validation for "%s" failed with error "%s".', $prompt->label, $error));
        }
      }

      return NULL;
    });
    $pm->prompt();
    $d = $output->content();
    $b = Prompt::content();

    $this->assertEquals($expected, $pm->getResponses(), $this->dataName());
  }

  public static function dataProviderPrompt() {
    return [
      'defaults' => [
        [
          'myproject',
          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //          NULL,
          //
          //          NULL,

        ],
        [Name::id() => 'installer'],
      ],
    ];
  }

  protected function fakePrompt(array $responses): void {
    $inputs = [];
    foreach ($responses as $response) {
      // Null response means to use the default value.
      if (!is_null($response)) {
        // Clear the input field default value.
        $inputs = array_merge($inputs, array_fill(0, 256, Key::BACKSPACE));
        $inputs = array_merge($inputs, mb_str_split($response));
      }
      $inputs[] = Key::ENTER;
    }
    Prompt::fake($inputs);
  }

}
