<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates an existing project to a template version, re-applying saved answers.
 *
 * A thin facade over the shared install flow: it points the download at the
 * "--to" version (falling back to an explicit "--uri") and defers to
 * doInstall(), which detects the existing project from its destination and
 * collects in update mode so saved answers pre-fill the form.
 *
 * @package DrevOps\VortexCli\Command
 */
class Update extends AbstractInstallCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('update')
      ->setDescription('Update the project to a template version, re-applying your answers.');

    $this->addCommonOptions();
    $this->addOption('to', NULL, InputOption::VALUE_REQUIRED, 'The target template version to update to.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $uri = $this->targetUri($input->getOption('to'), $input->getOption('uri'));
    if ($uri !== NULL) {
      $input->setOption('uri', $uri);
    }

    return $this->doInstall($input, $output);
  }

  /**
   * Resolve the repository URI to download, preferring an explicit URI.
   *
   * @param mixed $to
   *   The "--to" target version, if any.
   * @param mixed $uri
   *   The explicit "--uri", if any.
   *
   * @return string|null
   *   The URI to download, or NULL to leave resolution at its default.
   */
  public function targetUri(mixed $to, mixed $uri): ?string {
    if (is_string($uri) && $uri !== '') {
      return $uri;
    }

    if (is_string($to) && $to !== '') {
      return RepositoryDownloader::DEFAULT_REPO . '#' . $to;
    }

    return NULL;
  }

}
