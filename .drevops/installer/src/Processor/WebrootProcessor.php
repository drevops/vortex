<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Webroot processor.
 */
class WebrootProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 10;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $new_name = $config->get('webroot', 'web');

    if ($new_name != 'web') {
      Files::dirReplaceContent('web/', $new_name . '/', $dir);
      Files::dirReplaceContent('web\/', $new_name . '\/', $dir);
      Files::dirReplaceContent(': web', ': ' . $new_name, $dir);
      Files::dirReplaceContent('=web', '=' . $new_name, $dir);
      Files::dirReplaceContent('!web', '!' . $new_name, $dir);
      Files::dirReplaceContent('/web', '/' . $new_name, $dir);
      rename($dir . DIRECTORY_SEPARATOR . 'web', $dir . DIRECTORY_SEPARATOR . $new_name);
    }
  }

}
