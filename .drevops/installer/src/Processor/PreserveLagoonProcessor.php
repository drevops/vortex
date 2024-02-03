<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve Lagoon processor.
 */
class PreserveLagoonProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 220;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('preserve_lagoon')) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::LAGOON, $dir);
    }
    else {
      Files::remove($dir . '/drush/sites/lagoon.site.yml');
      Files::remove($dir . '/.lagoon.yml');
      Files::remove($dir . '/.github/workflows/dispatch-webhook-lagoon.yml');

      Tokenizer::removeTokenWithContentFromDir(Token::LAGOON, $dir);
    }
  }

}
