<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;

abstract class AbstractHandler implements HandlerInterface {

  protected string|bool $response;
  protected string $key;

  protected array $responses;

  protected string $dstDir;
  protected string $tmpDir;

  public function __construct(protected Config $config) {
    $this->dstDir = $this->config->getDst();
    $this->tmpDir = $this->config->get(Config::TMP);
    $reflector = new \ReflectionClass(static::class);
    $this->key = static::toKey($reflector->getFileName());
  }

  public function setResponses(array $responses): static {
    $this->responses = $responses;
    // @todo $this->response should always have a value  - otherwise there is
    // nothing to process. need to review this correctly.
    $this->response = $this->responses[$this->key]?? FALSE;

    return $this;
  }

  public function getKey() {
    return $this->key;
  }

  protected static function toKey($file = __FILE__) {
    return Converter::machine(Converter::pascal2snake(str_replace('Handler', '', basename($file, '.php'))));
  }

  abstract public function discover(): ?string;

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
