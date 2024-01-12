<?php

namespace DrevOps\DevTool\Docker;

/**
 * Class DockerCommand.
 *
 * A representation of the Dockerfile command.
 */
class DockerCommand {

  /**
   * Possible command keywords to validate against.
   */
  final const KEYWORDS = [
    'FROM',
    'RUN',
    'CMD',
    'LABEL',
    'EXPOSE',
    'ENV',
    'ADD',
    'COPY',
    'ENTRYPOINT',
    'VOLUME',
    'USER',
    'WORKDIR',
    'ARG',
    'ONBUILD',
    'STOPSIGNAL',
    'HEALTHCHECK',
    'SHELL',
  ];

  /**
   * Command keyword.
   */
  protected string $keyword;

  /**
   * DockerCommand constructor.
   *
   * @param string $keyword
   *   Command keyword.
   * @param string $arguments
   *   Command arguments.
   *
   * @throws \Exception
   *   If the keyword is invalid.
   */
  public function __construct(string $keyword, protected string $arguments) {
    if (!in_array($keyword, self::KEYWORDS)) {
      throw new \Exception(sprintf('Invalid docker command keyword %s.', $keyword));
    }
    $this->keyword = $keyword;
  }

  /**
   * Get the command keyword.
   */
  public function getKeyword(): string {
    return $this->keyword;
  }

  /**
   * Get the command arguments.
   */
  public function getArguments(): string {
    return $this->arguments;
  }

}
