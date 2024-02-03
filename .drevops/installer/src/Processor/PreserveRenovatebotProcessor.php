<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve Renovatebot processor.
 */
class PreserveRenovatebotProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 240;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('preserve_renovatebot')) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::RENOVATEBOT, $dir);
    }
    else {
      Files::remove($dir . '/renovate.json');
      Tokenizer::removeTokenWithContentFromDir(Token::RENOVATEBOT, $dir);
    }
  }

}
