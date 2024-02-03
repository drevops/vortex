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
 * Demo mode processor.
 */
class DemoModeProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 420;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    // Only discover demo mode if not explicitly set.
    if (is_null($config->get(Env::INSTALLER_DEMO_MODE))) {
      if (!$config->get('provision_use_profile')) {
        $download_source = $config->get('database_download_source');
        $db_file = Env::get(Env::DB_DIR, './.data') . DIRECTORY_SEPARATOR . Env::get(Env::DB_FILE, 'db.sql');
        $has_comment = Files::fileContains('to allow to demonstrate how DrevOps works without', $config->getDstDir() . '/.env');

        // Enable DrevOps demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source != DatabaseDownloadSourcePrompt::CHOICE_DOCKER_REGISTRY) {
          if ($has_comment || !file_exists($db_file)) {
            $config->set(Env::INSTALLER_DEMO_MODE, TRUE);
          }
          else {
            $config->set(Env::INSTALLER_DEMO_MODE, FALSE);
          }
        }
        elseif ($has_comment || $download_source == DatabaseDownloadSourcePrompt::CHOICE_DOCKER_REGISTRY) {
          $config->set(Env::INSTALLER_DEMO_MODE, TRUE);
        }
        else {
          $config->set(Env::INSTALLER_DEMO_MODE, FALSE);
        }
      }
      else {
        $config->set(Env::INSTALLER_DEMO_MODE, FALSE);
      }
    }

    if (!$config->get(Env::INSTALLER_DEMO_MODE)) {
      Tokenizer::removeTokenWithContentFromDir(Token::DEMO, $dir);
    }
  }

}
