<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update command.
 *
 * Updates an existing project to a template version, re-applying the answers
 * discovered from the project. A thin facade over the shared flow: it points
 * the download at the named version and defers the rest.
 *
 * @package DrevOps\VortexCli\Command
 */
class UpdateCommand extends AbstractInstallCommand {

  const OPTION_TO = 'to';

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'update';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('update');
    $this->setDescription('Update the project to a template version, re-applying your answers.');
    $this->setHelp(<<<EOF
  <info>Update the current directory to the latest release of this major:</info>
  php vortex.phar update

  <info>Update to a named template version:</info>
  php vortex.phar update --to=1.2.3

  <info>Update a project in another directory, without asking any question:</info>
  php vortex.phar update --no-interaction --destination=path/to/project

  <info>Update from a specific repository, with an optional git ref after #:</info>
  php vortex.phar update --uri=https://github.com/drevops/vortex.git#main

  Answers are pre-filled from the existing project, so unchanged settings can be
  accepted as they are. A project of a different major version is refused: run
  that major's release against it instead.
EOF
    );
    $this->addCommonOptions();
    $this->addOption(static::OPTION_TO, NULL, InputOption::VALUE_REQUIRED, 'The template version to update to. Defaults to the latest release of this major.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $uri = $this->targetUri($input->getOption(static::OPTION_TO), $input->getOption(static::OPTION_URI));

    if ($uri !== NULL) {
      $input->setOption(static::OPTION_URI, $uri);
    }

    return $this->doInstall($input, $output);
  }

  /**
   * Resolve the repository URI to download.
   *
   * @param mixed $to
   *   The target version, if any.
   * @param mixed $uri
   *   The explicit repository URI, if any.
   *
   * @return string|null
   *   The URI to download, or NULL to leave resolution at its default.
   */
  public function targetUri(mixed $to, mixed $uri): ?string {
    // An explicit URI names both the repository and the ref, so it is the more
    // specific input and wins over a bare version.
    if (is_string($uri) && $uri !== '') {
      return $uri;
    }

    if (is_string($to) && $to !== '') {
      return RepositoryDownloader::DEFAULT_REPO . '#' . $to;
    }

    return NULL;
  }

}
