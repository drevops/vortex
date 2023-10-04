<?php

namespace DrevOps\Installer;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Formatter;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrintManager {

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected SymfonyStyle $io;

  /**
   * @var \DrevOps\Installer\Bag\Config
   */
  protected Config $config;

  public function __construct(SymfonyStyle $io, Config $config) {
    $this->config = $config;
    $this->io = $io;
  }

  public function printHeader() {
    if ($this->config->isQuiet()) {
      $this->printHeaderQuiet();
    }
    else {
      $this->printHeaderInteractive();
    }
  }

  public function printHeaderInteractive() {
    $commit = $this->config->get(Env::INSTALLER_COMMIT);

    $content = '';

    if (empty($commit) || $commit == 'HEAD') {
      $content .= 'This will install the latest version of DrevOps into your project.' . PHP_EOL;
    }
    else {
      $content .= "This will install DrevOps into your project at commit \"$commit\"." . PHP_EOL;
    }

    $content .= PHP_EOL;
    if (InstallManager::isInstalled($this->config->getDstDir())) {
      $content .= 'It looks like DrevOps is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= '<bold>No changes will be applied until the last confirmation step.</bold>' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= '<bold>Press Ctrl+C at any time to exit this installer.</bold>';

    Formatter::printBox($this->io, $content, 'WELCOME TO DREVOPS INTERACTIVE INSTALLER');
  }

  public function printHeaderQuiet() {
    $commit = $this->config->get(Env::INSTALLER_COMMIT);

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of DrevOps into your project.' . PHP_EOL;
    }
    else {
      $content .= "This will install DrevOps into your project at commit \"$commit\"." . PHP_EOL;
    }
    $content .= PHP_EOL;
    if (InstallManager::isInstalled($this->config->getDstDir())) {
      $content .= 'It looks like DrevOps is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'DrevOps installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.';

    $verbosity_level = $this->io->getVerbosity();
    $this->io->setVerbosity(Output::VERBOSITY_VERBOSE);
    Formatter::printBox($this->io, $content, 'WELCOME TO DREVOPS QUIET INSTALLER');
    $this->io->setVerbosity($verbosity_level);
  }

  public function printSummary($values, $title) {
    $values = array_map(function ($key, $value) {
      return [$key => $value];
    }, array_keys($values), $values);

    $this->io->writeln("  <options=bold>$title</>");
    $this->io->definitionList(...$values);
  }

  public function printAbort() {
    Formatter::printBox($this->io, 'Aborting DrevOps installation. No files were changed.', 'INSTALLATION ABORTED');
  }

  public function printFooter() {
    $output = '';

    if (InstallManager::isInstalled($this->config->getDstDir())) {
      $title = 'UPDATE COMPLETE';
      $output .= 'Finished updating DrevOps.' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= 'Review changes and commit required files.' . PHP_EOL;
    }
    else {
      $title = 'INSTALLATION COMPLETE';
      $output .= 'Finished installing DrevOps' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= 'Next steps:' . PHP_EOL;
      $output .= '  cd ' . $this->config->getDstDir() . PHP_EOL;
      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
    }

    $output .= PHP_EOL;
    $output .= 'See <href=https://docs.drevops.com/quickstart>https://docs.drevops.com/quickstart</>' . PHP_EOL;
    $output .= PHP_EOL;
    $output .= 'Thank you for using <href=https://www.drevops.com>DrevOps</>!';
    Formatter::printBox($this->io, $output, $title, 'symfony-style-guide');
  }

}
