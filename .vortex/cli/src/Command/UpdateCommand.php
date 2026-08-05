<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Utils\Tui;
use DrevOps\VortexCli\Utils\Version;
use Symfony\Component\Console\Command\Command;
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
    $this->addOption(static::OPTION_TO, NULL, InputOption::VALUE_REQUIRED, 'The template version to update to, resolved against the official repository. Defaults to the latest release of this major. Use --uri to update from a fork.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $to = $input->getOption(static::OPTION_TO);

    try {
      $this->assertTargetMajor($to);
    }
    catch (\RuntimeException $runtime_exception) {
      Tui::init($output);
      Tui::error('Update failed with an error: ' . $runtime_exception->getMessage());

      return Command::FAILURE;
    }

    $uri = $this->targetUri($to, $input->getOption(static::OPTION_URI));

    if ($uri !== NULL) {
      $input->setOption(static::OPTION_URI, $uri);
    }

    return $this->doInstall($input, $output);
  }

  /**
   * Refuse a target version from another major line.
   *
   * The destination gate compares the project against this build, which says
   * nothing about the version being asked for. A named version is resolved
   * straight to an archive, so without this a build could pull a template from
   * across a breaking boundary into a project it considers compatible.
   *
   * @param mixed $to
   *   The target version, if any.
   *
   * @throws \RuntimeException
   *   When the target version's major differs from this build's major.
   */
  protected function assertTargetMajor(mixed $to): void {
    if (!is_string($to) || $to === '') {
      return;
    }

    // A branch, tag alias or commit carries no major to compare.
    $target_major = Version::major($to);
    if ($target_major === NULL) {
      return;
    }

    $cli_major = Version::major((string) $this->getApplication()?->getVersion());
    if ($cli_major === NULL || $cli_major === $target_major) {
      return;
    }

    throw new \RuntimeException(sprintf(
      'This Vortex CLI targets Vortex %1$d.x, but "%2$s" is a Vortex %3$d.x version. Update to it with the %3$d.x CLI instead: https://www.vortextemplate.com/v%3$d/install',
      $cli_major,
      $to,
      $target_major,
    ));
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
