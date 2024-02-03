<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\Concrete\DeployTypePrompt;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Token;
use DrevOps\Installer\Utils\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deploy type processor.
 */
class DeployTypeProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 70;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $type = $config->get('deploy_type');
    if ($type && $type != DeployTypePrompt::CHOICE_NONE) {
      Files::fileReplaceContent('/' . Env::DEPLOY_TYPES . '=.*/', Env::DEPLOY_TYPES . ('=' . $type), $dir . '/.env');

      if (!str_contains((string) $type, DeployTypePrompt::CHOICE_ARTIFACT)) {
        Files::remove($dir . '/.gitignore.deployment');
      }

      Tokenizer::removeTokenWithContentFromDir('!' . Token::DEPLOYMENT, $dir);
    }
    else {
      Files::remove($dir . '/docs/DEPLOYMENT.md');
      Files::remove($dir . '/.gitignore.deployment');
      Tokenizer::removeTokenWithContentFromDir(Token::DEPLOYMENT, $dir);
    }
  }

}
