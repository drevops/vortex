<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve Acquia processor.
 */
class PreserveAcquiaProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 210;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('preserve_acquia')) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::ACQUIA, $dir);
    }
    else {
      Files::rmdirRecursive($dir . '/hooks');
      Tokenizer::removeTokenWithContentFromDir(Token::ACQUIA, $dir);
    }
  }

}
