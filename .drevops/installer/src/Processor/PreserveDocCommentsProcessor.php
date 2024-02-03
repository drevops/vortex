<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Preserve doc comments processor.
 */
class PreserveDocCommentsProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 410;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('preserve_doc_comments')) {
      // Replace special "#: " comments with normal "#" comments.
      Files::dirReplaceContent(Token::COMMENT_DOC, '#', $dir);
    }
    else {
      Tokenizer::removeTokenLineFromDir(Token::COMMENT_DOC, $dir);
    }
  }

}
