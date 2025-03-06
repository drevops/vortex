<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;

abstract class AbstractHandler implements HandlerInterface {

  /**
   * The destination directory.
   *
   * @var string
   */
  protected string $dstDir;

  /**
   * The temporary directory.
   *
   * @var string
   */
  protected string $tmpDir;

  /**
   * The webroot directory name.
   *
   * @var string
   */
  protected string $webroot;

  /**
   * The response value for the current handler.
   *
   * @var string|bool|array
   */
  protected string|bool|array $response;

  /**
   * Array of all responses.
   *
   * @var array
   */
  protected array $responses;

  /**
   * The configuration object.
   */
  public function __construct(protected Config $config) {
    $this->dstDir = $this->config->getDst();
    $this->tmpDir = $this->config->get(Config::TMP);
  }

  /**
   * Set the webroot directory name.
   *
   * @param string $webroot
   *   The webroot directory name.
   *
   * @return $this
   */
  public function setWebroot(string $webroot): static {
    $this->webroot = $webroot;

    return $this;
  }

  /**
   * Set the responses.
   *
   * @param array $responses
   *   The responses.
   *
   * @return $this
   */
  public function setResponses(array $responses): static {
    $this->responses = $responses;
    // Set the response for current handler as a shorthand.
    // Some handlers may want to perform an action on the empty responses, so
    // it is up to the handler's processor to check for the presence of the
    // value in a set response.
    $this->response = $this->responses[static::id()] ?? NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Automatically generate the handler ID based on the class name.
   */
  public static function id(): string {
    $reflector = new \ReflectionClass(static::class);

    return Converter::machine(Converter::pascal2snake(str_replace('Handler', '', basename($reflector->getFileName(), '.php'))));
  }

  /**
   * {@inheritdoc}
   */
  abstract public function discover(): null|string|bool|array;

  /**
   * {@inheritdoc}
   */
  abstract public function process(): void;

  // @todo: Remove this in favour of $this->response.
  public function getAnswer($name) {
    return $this->responses[$name] ?? NULL;
  }

  /**
   * Check that Vortex is installed for this project.
   *
   * @todo Move to another place.
   */
  protected function isInstalled(): bool {
    return $this->config->isVortexProject();
  }

}
