<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database download source processor.
 */
class DatabaseImageProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 50;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $image = $config->get('database_image');
    Files::fileReplaceContent('/' . Env::DB_DOCKER_IMAGE . '=.*/', Env::DB_DOCKER_IMAGE . ('=' . $image), $dir . '/.env');

    if ($image) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::DB_DOCKER_IMAGE, $dir);
    }
    else {
      Tokenizer::removeTokenWithContentFromDir(Token::DB_DOCKER_IMAGE, $dir);
    }
  }

}
