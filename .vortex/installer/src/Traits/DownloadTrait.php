<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

/**
 * Environment trait.
 */
trait DownloadTrait {

  protected function downloadScaffoldLocal(): void {
    $dst = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $repo = $this->config->get('VORTEX_INSTALL_LOCAL_REPO');
    $ref = $this->config->get('VORTEX_INSTALL_COMMIT');

    $this->status(sprintf('Downloading Vortex from the local repository "%s" at ref "%s".', $repo, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);

    $command = sprintf('git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"', $repo, $repo, $ref, $dst);
    $this->doExec($command, $output, $code);

    $this->status(implode(PHP_EOL, $output), self::INSTALLER_STATUS_DEBUG);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    $this->status(sprintf('Downloaded to "%s".', $dst), self::INSTALLER_STATUS_DEBUG);

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function downloadScaffoldRemote(): void {
    $dst = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $org = 'drevops';
    $project = 'vortex';
    $ref = $this->config->get('VORTEX_INSTALL_COMMIT');
    $release_prefix = $this->config->get('VORTEX_VERSION');

    if ($ref == 'HEAD') {
      $release_prefix = $release_prefix == 'develop' ? NULL : $release_prefix;
      $ref = $this->findLatestVortexRelease($org, $project, $release_prefix);
      $this->config->set('VORTEX_VERSION', $ref);
    }

    $url = sprintf('https://github.com/%s/%s/archive/%s.tar.gz', $org, $project, $ref);
    $this->status(sprintf('Downloading Vortex from the remote repository "%s" at ref "%s".', $url, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);
    $this->doExec(sprintf('curl -sS -L "%s" | tar xzf - -C "%s" --strip 1', $url, $dst), $output, $code);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    $this->status(sprintf('Downloaded to "%s".', $dst), self::INSTALLER_STATUS_DEBUG);

    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function findLatestVortexRelease(string $org, string $project, ?string $release_prefix): ?string {
    $release_url = sprintf('https://api.github.com/repos/%s/%s/releases', $org, $project);
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);

    if (!$release_prefix) {
      return is_scalar($records[0]['tag_name']) ? strval($records[0]['tag_name']) : NULL;
    }

    foreach ($records as $record) {
      $tag_name = is_scalar($record['tag_name']) ? strval($record['tag_name']) : '';
      if (str_contains($tag_name, $release_prefix)) {
        return $tag_name;
      }
    }

    return NULL;
  }

}
