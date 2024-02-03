<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * DrevOps internal processor.
 */
class DrevopsInternalProcess extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 510;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    // Remove DrevOps internal files.
    Files::rmdirRecursive($dir . '/scripts/drevops/docs');
    Files::rmdirRecursive($dir . '/scripts/drevops/tests');
    Files::rmdirRecursive($dir . '/scripts/drevops/utils');
    Files::remove($dir . '/.github/FUNDING.yml');

    // Remove other unhandled tokenized comments.
    Tokenizer::removeTokenLineFromDir(Token::COMMENT_INTERNAL_BEGIN, $dir);
    Tokenizer::removeTokenLineFromDir(Token::COMMENT_INTERNAL_END, $dir);
  }

}
