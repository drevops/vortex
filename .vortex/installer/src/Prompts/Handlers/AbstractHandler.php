<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;

abstract class AbstractHandler implements HandlerInterface {

  protected string|bool|iterable $response;

  protected array $responses;

  protected string $dstDir;
  protected string $tmpDir;

  protected string $webroot;

  public function __construct(protected Config $config) {
    $this->dstDir = $this->config->getDst();
    $this->tmpDir = $this->config->get(Config::TMP);
  }

  public function setWebroot(string $webroot): static {
    $this->webroot = $webroot;
    return $this;
  }

  public function setResponses(array $responses): static {
    $this->responses = $responses;
    // @todo $this->response should always have a value  - otherwise there is
    // nothing to process. need to review this correctly.
    $this->response = $this->responses[static::id()]?? FALSE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    $reflector = new \ReflectionClass(static::class);
    return Converter::machine(Converter::pascal2snake(str_replace('Handler', '', basename($reflector->getFileName(), '.php'))));
  }

  /**
   * {@inheritdoc}
   */
  abstract public function discover(): null|string|bool|iterable;

  /**
   * {@inheritdoc}
   */
  abstract public function process(): void;

  // @todo: Rename to getResponse().
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
