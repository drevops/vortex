<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use DrevOps\VortexInstaller\Downloader\Artifact;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Resolves CLI options and environment variables into Config and Artifact.
 *
 * @package DrevOps\VortexInstaller\Utils
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
        throw new \RuntimeException(sprintf('Missing required command: %s.', $required_command));
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
      $config_candidate = strval($options['config']);
      $config_json = is_file($config_candidate) ? (string) file_get_contents($config_candidate) : $config_candidate;
    }

    $config = Config::fromString($config_json);

    $config->setQuiet($options['quiet']);
    $config->setNoInteraction($options['no-interaction']);

    // Set root directory to resolve relative paths.
    $root = !empty($options['root']) && is_scalar($options['root']) ? strval($options['root']) : NULL;
    if ($root) {
      $config->set(Config::ROOT, $root);
    }

    // Set destination directory.
    $dst_from_option = !empty($options['destination']) && is_scalar($options['destination']) ? strval($options['destination']) : NULL;
    $dst_from_env = Env::get(Config::DST);
    $dst_from_config = $config->get(Config::DST);
    $dst_from_root = $config->get(Config::ROOT);

    $dst = $dst_from_option ?: ($dst_from_env ?: ($dst_from_config ?: $dst_from_root));
    $dst = File::realpath($dst);
    $config->set(Config::DST, $dst, TRUE);

    // Load values from the destination .env file, if it exists.
    $dest_env_file = $config->getDst() . '/.env';

    if (File::exists($dest_env_file)) {
      Env::putFromDotenv($dest_env_file);
    }

    // Build URI for artifact.
    $uri_from_option = !empty($options['uri']) && is_scalar($options['uri']) ? strval($options['uri']) : NULL;
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
      throw new \RuntimeException(sprintf('Invalid repository URI: %s', $e->getMessage()), $e->getCode(), $e);
    }

    // Check if the project is a Vortex project.
    $config->set(Config::IS_VORTEX_PROJECT, File::contains($config->getDst() . DIRECTORY_SEPARATOR . 'README.md', '/badge\/Vortex-/'));

    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    $config->set(Config::PROCEED, TRUE);

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(Env::get(Config::IS_DEMO))) {
      $config->set(Config::IS_DEMO, (bool) Env::get(Config::IS_DEMO));
    }

    // Internal flag to skip processing of the demo mode.
    $config->set(Config::IS_DEMO_DB_DOWNLOAD_SKIP, (bool) Env::get(Config::IS_DEMO_DB_DOWNLOAD_SKIP, FALSE));

    // Set no-cleanup flag.
    $config->set(Config::NO_CLEANUP, (bool) $options['no-cleanup']);

    // Set build-now flag.
    $config->set(Config::BUILD_NOW, (bool) $options['build']);

    return [$config, $artifact];
  }

}
