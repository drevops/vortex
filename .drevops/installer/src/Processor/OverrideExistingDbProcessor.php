<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Override existing db processor.
 */
class OverrideExistingDbProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 60;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if ($config->get('override_existing_db')) {
      Files::fileReplaceContent('/' . Env::PROVISION_OVERRIDE_DB . '=.*/', Env::PROVISION_OVERRIDE_DB . "=1", $dir . '/.env');
    }
    else {
      Files::fileReplaceContent('/' . Env::PROVISION_OVERRIDE_DB . '=.*/', Env::PROVISION_OVERRIDE_DB . "=0", $dir . '/.env');
    }
  }

}
