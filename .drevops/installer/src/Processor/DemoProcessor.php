<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Downloader;
use DrevOps\Installer\Utils\Env;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Demo processor.
 */
class DemoProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    if (empty($config->get(Env::INSTALLER_DEMO_MODE)) || !empty($config->get(Env::INSTALLER_DEMO_MODE_SKIP))) {
      return;
    }

    // Reload variables from destination's .env.
    // @todo Fix this - introduce config refresh.
    DotEnv::loadDotenv($config->getDstDir() . '/.env');

    $url = Env::get(Env::DB_DOWNLOAD_CURL_URL);
    if (empty($url)) {
      return;
    }

    $data_dir = $config->getDstDir() . DIRECTORY_SEPARATOR . Env::get(Env::DB_DIR, './.data');
    $file = Env::get(Env::DB_FILE, 'db.sql');

    $output->writeln(sprintf('No database dump file found in "%s" directory. Downloading DEMO database from %s.', $data_dir, $url));

    if (!file_exists($data_dir)) {
      mkdir($data_dir);
    }

    Downloader::downloadFromUrl($url, $data_dir . DIRECTORY_SEPARATOR . $file, TRUE);

    $output->writeln('Done');
  }

}
