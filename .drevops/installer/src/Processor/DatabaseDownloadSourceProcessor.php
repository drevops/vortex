<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\Concrete\DatabaseDownloadSourcePrompt;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database download source processor.
 */
class DatabaseDownloadSourceProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 40;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $type = $config->get('database_download_source');
    Files::fileReplaceContent('/' . Env::DB_DOWNLOAD_SOURCE . '=.*/', Env::DB_DOWNLOAD_SOURCE . ('=' . $type), $dir . '/.env');

    if ($type == DatabaseDownloadSourcePrompt::CHOICE_DOCKER_REGISTRY) {
      Tokenizer::removeTokenWithContentFromDir('!' . Token::DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY, $dir);
    }
    else {
      Tokenizer::removeTokenWithContentFromDir(Token::DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY, $dir);
    }
  }

}
