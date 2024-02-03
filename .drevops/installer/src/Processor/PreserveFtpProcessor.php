<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve FTP processor.
 */
class PreserveFtpProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 230;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('preserve_ftp')) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::FTP, $dir);
    }
    else {
      Tokenizer::removeTokenWithContentFromDir(Token::FTP, $dir);
    }
  }

}
