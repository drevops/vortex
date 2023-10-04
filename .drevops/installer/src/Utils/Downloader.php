<?php

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Bag\Config;
use RuntimeException;

class Downloader {

  public static function findLatestDrevopsRelease($org, $project, $release_prefix) {
    $release_url = "https://api.github.com/repos/{$org}/{$project}/releases";
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);
    foreach ($records as $record) {
      if (isset($record['tag_name']) && strpos($record['tag_name'], $release_prefix) === 0) {
        return $record['tag_name'];
      }
    }
  }

  public static function downloadRemote() {
    $dst = Config::get(Env::DREVOPS_INSTALLER_TMP_DIR);
    $org = 'drevops';
    $project = 'drevops';
    $ref = Config::get(Env::DREVOPS_INSTALLER_COMMIT);
    $release_prefix = Config::get(Env::DREVOPS_VERSION);

    if ($ref == 'HEAD') {
      $ref = Downloader::findLatestDrevopsRelease($org, $project, $release_prefix);
    }

    $url = "https://github.com/{$org}/{$project}/archive/{$ref}.tar.gz";
    Output::status(sprintf('Downloading DrevOps from the remote repository "%s" at ref "%s".', $url, $ref), Output::INSTALLER_STATUS_MESSAGE, FALSE);
    Executor::doExec("curl -sS -L \"$url\" | tar xzf - -C \"{$dst}\" --strip 1", $output, $code);

    if ($code != 0) {
      throw new RuntimeException(implode(PHP_EOL, $output));
    }

    Output::status(sprintf('Downloaded to "%s".', $dst), Output::INSTALLER_STATUS_DEBUG);

    Output::status('Done', Output::INSTALLER_STATUS_SUCCESS);
  }

  public static function downloadLocal() {
    $dst = Config::get(Env::DREVOPS_INSTALLER_TMP_DIR);
    $repo = Config::get(Env::DREVOPS_INSTALLER_LOCAL_REPO);
    $ref = Config::get(Env::DREVOPS_INSTALLER_COMMIT);

    Output::status(sprintf('Downloading DrevOps from the local repository "%s" at ref "%s".', $repo, $ref), Output::INSTALLER_STATUS_MESSAGE, FALSE);

    $command = "git --git-dir=\"{$repo}/.git\" --work-tree=\"{$repo}\" archive --format=tar \"{$ref}\" | tar xf - -C \"{$dst}\"";
    Executor::doExec($command, $output, $code);

    Output::status(implode(PHP_EOL, $output), Output::INSTALLER_STATUS_DEBUG);

    if ($code != 0) {
      throw new RuntimeException(implode(PHP_EOL, $output));
    }

    Output::status(sprintf('Downloaded to "%s".', $dst), Output::INSTALLER_STATUS_DEBUG);

    print ' ';
    Output::status('Done', Output::INSTALLER_STATUS_SUCCESS);
  }

  /**
   * Download DrevOps source files.
   */
  public function download() {
    if (Config::get(Env::DREVOPS_INSTALLER_LOCAL_REPO)) {
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
      throw new RuntimeException(sprintf('Unable to download file from "%s".', $url));
    }

    return $dst;
  }

}
