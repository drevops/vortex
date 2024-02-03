<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Enable commented code processor.
 */
class EnableCommentedCodeProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 520;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    Files::dirReplaceContent(Token::COMMENTED_CODE, '', $dir);
  }

}
