<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait as UpstreamProcessTrait;
use Symfony\Component\Process\Process;

/**
 * Trait ProcessTrait.
 *
 * Runs a test process and provides assertions for its output.
 */
trait ProcessTrait {

  use UpstreamProcessTrait {
    UpstreamProcessTrait::processRun as traitProcessRun;
  }
  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function processRun(
    string $command,
    array $arguments = [],
    array $inputs = [],
    array $env = [],
    int $timeout = 60,
    int $idle_timeout = 60,
  ): Process {
    $env += [
      'AHOY_CONFIRM_RESPONSE' => 'y',
      'AHOY_CONFIRM_WAIT_SKIP' => 1,
      // Credentials for the test container registry to allow fetching public
      // images to overcome the throttle limit of Docker Hub, and also used
      // for pushing images during the build.
      'VORTEX_CONTAINER_REGISTRY_USER' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_USER') ?: '',
      'VORTEX_CONTAINER_REGISTRY_PASS' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_PASS') ?: '',
    ];

    // If process streaming is disabled, also silence the output of the
    // commands.
    if (!$this->processStreamOutput) {
      // Silence the output of the Composer commands (but still output errors).
      $env += ['SHELL_VERBOSITY' => -1];

      // Silence the output of the Docker Composer commands.
      if (str_starts_with($command, 'docker compose') && !str_contains($command, '--progress')) {
        $command = str_replace('docker compose', 'docker compose --progress quiet', $command);
      }
    }

    return $this->traitProcessRun($command, $arguments, $inputs, $env, $timeout, $idle_timeout);
  }

  public function cmd(
    string $cmd,
    array|string|null $out = NULL,
    ?string $txt = NULL,
    array $arg = [],
    array $inp = [],
    array $env = [],
    int $tio = 900,
    int $ito = 180,
  ): Process {
    $this->processRun($cmd, $arg, $inp, $env, $tio, $ito);
    $this->assertProcessSuccessful($txt);
    if ($txt) {
      $this->logNote($txt);
    }

    if ($out) {
      $this->assertProcessAnyOutputContainsOrNot($out);
    }

    return $this->processGet();
  }

  public function cmdFail(
    string $cmd,
    array|string|null $out = NULL,
    ?string $txt = NULL,
    array $arg = [],
    array $inp = [],
    array $env = [],
    int $tio = 900,
    int $ito = 180,
  ): Process {
    $this->processRun($cmd, $arg, $inp, $env, $tio, $ito);
    $this->assertProcessFailed($txt);
    if ($txt) {
      $this->logNote($txt);
    }

    if ($out) {
      $this->assertProcessAnyOutputContainsOrNot($out);
    }

    return $this->processGet();
  }

}
