<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provision use profile processor.
 */
class ProvisionUseProfileProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 30;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('provision_use_profile')) {
      Files::fileReplaceContent('/' . Env::PROVISION_USE_PROFILE . '=.*/', Env::PROVISION_USE_PROFILE . '=1', $dir . '/.env');
      Tokenizer::removeTokenWithContentFromDir('!' . Token::PROVISION_USE_PROFILE, $dir);
    }
    else {
      Files::fileReplaceContent('/' . Env::PROVISION_USE_PROFILE . '=.*/', Env::PROVISION_USE_PROFILE . '=0', $dir . '/.env');
      Tokenizer::removeTokenWithContentFromDir(Token::PROVISION_USE_PROFILE, $dir);
    }
  }

}
