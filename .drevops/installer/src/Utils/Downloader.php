<?php

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Bag\Config;
use Symfony\Component\Console\Output\Output;

/**
 * Downloader.
 */
class Downloader {

  public static function findLatestDrevopsRelease($org, $project, $release_prefix) {
    $release_url = sprintf('https://api.github.com/repos/%s/%s/releases', $org, $project);
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);
    foreach ($records as $record) {
      if (isset($record['tag_name']) && str_starts_with((string) $record['tag_name'], (string) $release_prefix)) {
        return $record['tag_name'];
      }
    }
  }

  public static function downloadRemote(): void {
    $dst = Config::getInstance()->get(Env::INSTALLER_TMP_DIR);
    $org = 'drevops';
    $project = 'drevops';
    $ref = Config::getInstance()->get(Env::INSTALLER_COMMIT);
    $release_prefix = Config::getInstance()->get(Env::DREVOPS_VERSION);

    if ($ref == 'HEAD') {
      $ref = Downloader::findLatestDrevopsRelease($org, $project, $release_prefix);
    }

    $url = sprintf('https://github.com/%s/%s/archive/%s.tar.gz', $org, $project, $ref);
    Output::status(sprintf('Downloading DrevOps from the remote repository "%s" at ref "%s".', $url, $ref), Output::INSTALLER_STATUS_MESSAGE, FALSE);
    Executor::doExec(sprintf('curl -sS -L "%s" | tar xzf - -C "%s" --strip 1', $url, $dst), $output, $code);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    Output::status(sprintf('Downloaded to "%s".', $dst), Output::INSTALLER_STATUS_DEBUG);

    Output::status('Done', Output::INSTALLER_STATUS_SUCCESS);
  }

  public static function downloadLocal(): void {
    $dst = Config::getInstance()->get(Env::DREVOPS_INSTALLER_TMP_DIR);
    $repo = Config::getInstance()->get(Env::DREVOPS_INSTALLER_LOCAL_REPO);
    $ref = Config::getInstance()->get(Env::DREVOPS_INSTALLER_COMMIT);

    Output::status(sprintf('Downloading DrevOps from the local repository "%s" at ref "%s".', $repo, $ref), Output::INSTALLER_STATUS_MESSAGE, FALSE);

    $command = sprintf('git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"', $repo, $repo, $ref, $dst);
    Executor::doExec($command, $output, $code);

    Output::status(implode(PHP_EOL, $output), Output::INSTALLER_STATUS_DEBUG);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    Output::status(sprintf('Downloaded to "%s".', $dst), Output::INSTALLER_STATUS_DEBUG);

    print ' ';
    Output::status('Done', Output::INSTALLER_STATUS_SUCCESS);
  }

  /**
   * Download DrevOps source files.
   */
  public function download(): void {
    if (Config::getInstance()->get(Env::DREVOPS_INSTALLER_LOCAL_REPO)) {
      Downloader::downloadLocal();
    }
    else {
      Downloader::downloadRemote();
    }
  }

  public static function downloadFromUrl($url, $dst, $verbose = FALSE) {
    // @todo Replace this with a proper curl library.
    Executor::doExec(sprintf('curl -s -L "%s" -o "%s"', $url, $dst), $output, $code);

    if ($code !== 0) {
      throw new \RuntimeException(sprintf('Unable to download file from "%s".', $url));
    }

    return $dst;
  }

}
