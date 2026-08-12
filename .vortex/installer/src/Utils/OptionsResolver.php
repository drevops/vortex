<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use DrevOps\VortexInstaller\Downloader\Artifact;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Resolves CLI options and environment variables into Config and Artifact.
 */
class OptionsResolver {

  /**
   * Check that required commands are available.
   *
   * @param \Symfony\Component\Process\ExecutableFinder $finder
   *   The executable finder.
   *
   * @throws \RuntimeException
   *   If a required command is missing.
   */
  public static function checkRequirements(ExecutableFinder $finder): void {
    $required_commands = [
      'git',
      'tar',
      'composer',
    ];

    foreach ($required_commands as $required_command) {
      if ($finder->find($required_command) === NULL) {
        throw new \RuntimeException(sprintf('Missing required command: "%s".', $required_command));
      }
    }
  }

  /**
   * Instantiate configuration from CLI options and environment variables.
   *
   * Installer configuration is a set of internal installer variables
   * prefixed with "VORTEX_INSTALLER_" and used to control the installation.
   * They are read from the environment variables with $this->config->get().
   *
   * For simplicity of naming, internal installer config variables used in
   * $this->config->get() match environment variables names.
   *
   * @param array<mixed> $options
   *   Array of CLI options.
   *
   * @return array{Config, Artifact}
   *   A tuple of [Config, Artifact].
   */
  public static function resolve(array $options): array {
    $config_json = '{}';
    if (isset($options['config']) && is_scalar($options['config'])) {
      $config_candidate = (string) $options['config'];
      $config_json = is_file($config_candidate) ? (string) file_get_contents($config_candidate) : $config_candidate;
    }

    $config = Config::fromString($config_json);

    $config->setQuiet($options['quiet']);
    $config->setNoInteraction($options['no-interaction']);

    // Set root directory to resolve relative paths.
    $root = !empty($options['root']) && is_scalar($options['root']) ? (string) $options['root'] : NULL;
    if ($root) {
      $config->set(Config::ROOT, $root);
    }

    $destination_from_option = !empty($options['destination']) && is_scalar($options['destination']) ? (string) $options['destination'] : NULL;
    $destination_from_env = Env::get(Config::DESTINATION);
    $destination_from_config = $config->get(Config::DESTINATION);
    $destination_from_root = $config->get(Config::ROOT);

    $destination = $destination_from_option ?: ($destination_from_env ?: ($destination_from_config ?: $destination_from_root));
    $destination = File::realpath($destination);
    $config->set(Config::DESTINATION, $destination, TRUE);

    $dest_env_file = $config->getDestination() . '/.env';

    if (File::exists($dest_env_file)) {
      Env::putFromDotenv($dest_env_file);
    }

    $uri_from_option = !empty($options['uri']) && is_scalar($options['uri']) ? (string) $options['uri'] : NULL;
    $repo = Env::get(Config::REPO) ?: ($config->get(Config::REPO) ?: NULL);
    $ref = Env::get(Config::REF) ?: ($config->get(Config::REF) ?: NULL);

    // Priority: option URI > env/config repo+ref > default.
    $uri = $uri_from_option;
    if (!$uri && $repo) {
      $uri = $ref ? $repo . '#' . $ref : $repo;
    }

    try {
      $artifact = Artifact::fromUri($uri);
      $config->set(Config::REPO, $artifact->getRepo());
      $config->set(Config::REF, $artifact->getRef());
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException(sprintf('Invalid repository URI: %s.', $e->getMessage()), $e->getCode(), $e);
    }

    $config->set(Config::IS_VORTEX_PROJECT, File::contains($config->getDestination() . '/README.md', '/badge\/Vortex-/'));

    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    $config->set(Config::PROCEED, TRUE);

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (Env::get(Config::IS_DEMO) !== NULL) {
      $config->set(Config::IS_DEMO, (bool) Env::get(Config::IS_DEMO));
    }

    $config->set(Config::IS_DEMO_DB_FETCH_SKIP, (bool) Env::get(Config::IS_DEMO_DB_FETCH_SKIP, FALSE));

    if (isset($options['prompts']) && is_scalar($options['prompts'])) {
      $prompts_candidate = (string) $options['prompts'];
      if (is_file($prompts_candidate)) {
        if (!is_readable($prompts_candidate)) {
          throw new \RuntimeException(sprintf('Unable to read --prompts file: "%s".', $prompts_candidate));
        }
        $prompts_json = (string) file_get_contents($prompts_candidate);
      }
      else {
        $prompts_json = $prompts_candidate;
      }
      $prompts = json_decode($prompts_json, TRUE);

      if (!is_array($prompts)) {
        throw new \RuntimeException('Invalid JSON provided for --prompts.');
      }

      // Schema validation against prompt handlers is performed in
      // PromptManager::resolvePromptOverrides().
      $config->set(Config::PROMPTS, $prompts, TRUE);
    }

    $config->set(Config::NO_CLEANUP, (bool) $options['no-cleanup']);

    $config->set(Config::BUILD_NOW, (bool) $options['build']);

    return [$config, $artifact];
  }

}
