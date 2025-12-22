<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Docker Compose configuration format and default variables.
 */
class DockerComposeTest extends FunctionalTestCase {

  #[Group('p0')]
  #[DataProvider('dataProviderDockerComposeConfig')]
  public function testDockerComposeConfig(string $expected_file, ?callable $before = NULL): void {
    File::copy(static::$root . '/docker-compose.yml', 'docker-compose.yml');
    $this->forceVolumesUnmounted();
    $this->adjustCodebaseForUnmountedVolumes();

    if (is_callable($before)) {
      $before($this);
    }

    $this->logSubstep('Validate configuration');
    $this->cmd('docker compose -f docker-compose.yml config', txt: 'Docker Compose configuration should be valid');

    $this->logSubstep('Generate actual docker-compose.yml configuration as json');
    $process = $this->cmd(
      cmd: 'docker compose -f docker-compose.yml config --format json',
      txt: 'Docker Compose configuration generation should succeed',
      env: [
        'PACKAGE_TOKEN' => FALSE,
        'CI' => 'true',
      ],
    );
    File::dump('docker-compose.actual.json', $process->getOutput());
    $this->processDockerComposeJson('docker-compose.actual.json');

    $this->logSubstep('Prepare expected fixture');
    File::copy(static::$fixtures . DIRECTORY_SEPARATOR . $expected_file, $expected_file);
    File::replaceContentInFile($expected_file, static::$sut, 'FIXTURE_CUR_DIR');

    if (getenv('UPDATE_SNAPSHOTS')) {
      $this->logSubstep('Updating snapshot file ' . $expected_file);
      File::copy('docker-compose.actual.json', static::$fixtures . DIRECTORY_SEPARATOR . $expected_file);
    }

    $this->logSubstep('Compare with fixture');
    $this->assertFileEquals($expected_file, 'docker-compose.actual.json', 'Docker Compose configuration should match expected fixture');
  }

  public static function dataProviderDockerComposeConfig(): array {
    return [
      ['docker-compose.noenv.json'],
      [
        'docker-compose.env.json',
        function (): void {
          File::copy(static::$root . '/.env', '.env');
        },
      ],
      [
        'docker-compose.env_mod.json',
        function (FunctionalTestCase $test): void {
          File::copy(static::$root . '/.env', '.env');

          // Add modified environment variables.
          $test->fileAddVar('.env', 'COMPOSE_PROJECT_NAME', 'the_matrix');
          $test->fileAddVar('.env', 'WEBROOT', 'docroot');
          $test->fileAddVar('.env', 'VORTEX_DB_IMAGE', 'myorg/my_db_image');
          $test->fileAddVar('.env', 'XDEBUG_ENABLE', '1');
          $test->fileAddVar('.env', 'DRUPAL_SHIELD_USER', 'jane');
          $test->fileAddVar('.env', 'DRUPAL_SHIELD_PASS', 'passw');
          $test->fileAddVar('.env', 'DRUPAL_REDIS_ENABLED', '1');
          $test->fileAddVar('.env', 'LAGOON_ENVIRONMENT_TYPE', 'development');
        },
      ],
      [
        'docker-compose.env_local.json',
        function (): void {
          File::copy(static::$root . '/.env', '.env');
          File::copy(static::$root . '/.env.local.example', '.env.local');
        },
      ],
    ];
  }

  /**
   * Process docker-compose JSON output for comparison.
   *
   * This method normalizes the Docker Compose configuration JSON:
   * - Sorts all values recursively by key in alphabetical order
   * - Removes YAML anchors starting with 'x-'
   * - Normalizes version numbers to 'VERSION' placeholder
   * - Replaces HOME directory references with 'HOME' placeholder.
   */
  protected function processDockerComposeJson(string $from, ?string $to = NULL): void {
    $to = $to ?: $from;

    $data = json_decode(File::read($from), TRUE);

    if (!is_array($data)) {
      throw new \RuntimeException('Invalid JSON in ' . $from);
    }

    $this->ksortRecursive($data);

    // Remove YAML anchors starting with 'x-'.
    $data = array_filter($data, fn($key): bool => !str_starts_with((string) $key, 'x-'), ARRAY_FILTER_USE_KEY);

    array_walk_recursive($data, function (&$value): void {
      if ($value !== NULL && is_string($value) && preg_match('/:\d+\.\d+(\.\d+)?/', $value)) {
        $value = preg_replace('/:\d+\.\d+(?:\.\d+)?/', ':VERSION', $value);
      }
    });

    array_walk_recursive($data, function (&$value): void {
      if (empty($_SERVER['HOME']) || !is_string($_SERVER['HOME'])) {
        throw new \RuntimeException('HOME environment variable is not set.');
      }
      if ($value !== NULL && is_string($value) && str_contains($value, $_SERVER['HOME'])) {
        $value = str_replace($_SERVER['HOME'], 'HOME', $value);
      }
    });

    $processed_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($processed_content === FALSE) {
      throw new \RuntimeException('Failed to encode processed JSON.');
    }

    $processed_content = File::replaceContent($processed_content, static::$sut, 'FIXTURE_CUR_DIR');

    File::dump($to, $processed_content . "\n");
  }

  /**
   * Recursively sort arrays by key.
   */
  protected function ksortRecursive(array &$array): void {
    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->ksortRecursive($value);
      }
    }
    ksort($array);
  }

}
