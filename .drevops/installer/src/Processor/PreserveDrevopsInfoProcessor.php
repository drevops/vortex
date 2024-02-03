<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve DrevOps info processor.
 */
class PreserveDrevopsInfoProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 430;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if (!$config->get('preserve_drevops_info')) {
      // Remove code required for DrevOps maintenance.
      Tokenizer::removeTokenWithContentFromDir(Token::DREVOPS_DEV, $dir);

      // Remove all other comments.
      Tokenizer::removeTokenLineFromDir(Token::COMMENT_INTERNAL, $dir);
    }
  }

}
