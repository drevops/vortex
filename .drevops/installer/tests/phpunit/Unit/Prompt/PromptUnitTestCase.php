<?php

namespace Drevops\Installer\Tests\Unit\Prompt;

use Drevops\Installer\Tests\Unit\UnitTestBase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class PromptUnitTestCase extends UnitTestBase {

  const HUMAN_NAME_VALID = 'Human name valid';

  const HUMAN_NAME_INVALID = ' .Human name valid.';

  const MACHINE_NAME_VALID = 'machine_name_valid';

  const MACHINE_NAME_INVALID = ' .machine_name_valid.';

  const PROJECT_NAME_VALID = 'Star Wars';

  const PROJECT_NAME_INVALID = ' .Star Wars';

  const PROJECT_MACHINE_NAME_VALID = 'star_wars';

  const PROJECT_MACHINE_NAME_INVALID = '.star_wars.';

  const ORGANIZATION_NAME_VALID = 'Lucasfilm Ltd';

  const ORGANIZATION_NAME_INVALID = ' .Lucasfilm Ltd';

  const ORGANIZATION_MACHINE_NAME_VALID = 'lucasfilm_ltd';

  const ORGANIZATION_MACHINE_NAME_INVALID = '.lucasfilm_ltd.';

  // Note that this is based on PROJECT_NAME_VALID and ORGANIZATION_NAME_VALID.
  const COMPOSERJSON_DESCRIPTION_VALID = 'Drupal 10 implementation of Star Wars for Lucasfilm Ltd';

  const COMPOSERJSON_DESCRIPTION_INVALID = 'Other CMS 10 implementation of Star Wars for Lucasfilm Ltd';

  const DEFAULT_ANSWER = 'fixture answer';

  /**
   * Comes from the QuestionHelper.
   */
  const DEFAULT_ABORT_MESSAGE = 'Aborted.';

  protected function io(mixed $answer = NULL) {
    $answer = $answer ?: static::DEFAULT_ANSWER;
    $inputStream = fopen('php://memory', 'r+');
    fwrite($inputStream, $answer . \PHP_EOL);
    rewind($inputStream);
    $input = $this->createMock(Input::class);
    $sections = [];
    $output = new ConsoleSectionOutput(fopen('php://memory', 'r+', FALSE), $sections, StreamOutput::VERBOSITY_NORMAL, TRUE, new OutputFormatter());
    $input
      ->method('isInteractive')
      ->willReturn(TRUE);
    $input
      ->method('getStream')
      ->willReturn($inputStream);

    return new SymfonyStyle($input, $output);
  }

}
