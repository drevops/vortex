<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;

abstract class AbstractHandler implements HandlerInterface {

  /**
   * The destination directory.
   */
  protected string $dstDir;

  /**
   * The temporary directory.
   */
  protected string $tmpDir;

  /**
   * The webroot directory name.
   */
  protected string $webroot;

  /**
   * The response value for the current handler.
   *
   * @var string|bool|array
   */
  protected null|string|bool|array $response = NULL;

  /**
   * Array of all responses.
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
   * {@inheritdoc}
   */
  public function setWebroot(string $webroot): static {
    $this->webroot = $webroot;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponses(array $responses): static {
    $this->responses = $responses;
    $this->setWebroot($responses[Webroot::id()] ?? Webroot::WEB);
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

    $filename = $reflector->getFileName();

    if ($filename === FALSE) {
      throw new \RuntimeException(sprintf('Could not determine the filename of the handler class %s.', static::class));
    }

    return Converter::machine(Converter::pascal2snake(str_replace('Handler', '', basename($filename, '.php'))));
  }

  /**
   * {@inheritdoc}
   */
  abstract public function discover(): null|string|bool|array;

  /**
   * {@inheritdoc}
   */
  abstract public function process(): void;

  /**
   * Check that Vortex is installed for this project.
   *
   * @todo Move to another place.
   */
  protected function isInstalled(): bool {
    return $this->config->isVortexProject();
  }

  /**
   * Get response as string, validating it's scalar first.
   *
   * @return string
   *   The response cast to string.
   *
   * @throws \RuntimeException
   *   When response is not scalar.
   */
  protected function getResponseAsString(): string {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type: expected scalar value.');
    }

    return (string) $this->response;
  }

  /**
   * Get response as array, validating type first.
   *
   * @return array
   *   The response as array.
   *
   * @throws \RuntimeException
   *   When response is not an array.
   */
  protected function getResponseAsArray(): array {
    if (!is_array($this->response)) {
      throw new \RuntimeException('Invalid response type: expected array.');
    }

    return $this->response;
  }

  /**
   * Get response as boolean, validating type first.
   *
   * @return bool
   *   The response as boolean.
   *
   * @throws \RuntimeException
   *   When response is not boolean.
   */
  protected function getResponseAsBool(): bool {
    if (!is_bool($this->response)) {
      throw new \RuntimeException('Invalid response type: expected boolean.');
    }

    return $this->response;
  }

}
