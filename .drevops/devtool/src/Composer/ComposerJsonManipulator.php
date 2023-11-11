<?php

namespace DrevOps\DevTool\Composer;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Semver\VersionParser;
use DrevOps\DevTool\Utils\Arrays;

/**
 * Class ComposerJsonManipulator.
 *
 * Manipulate composer.json.
 *
 * @package DrevOps\DevTool\Composer
 */
class ComposerJsonManipulator extends JsonManipulator {

  /**
   * Get formatted data as a structure.
   */
  public function getFormattedData(): mixed {
    return JsonFile::parseJson($this->getContents());
  }

  /**
   * Save to file.
   *
   * @param string $path
   *   Path to file.
   */
  public function save(string $path): void {
    file_put_contents($path, JsonFile::encode(json_decode($this->getContents()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . $this->getNewline());
  }

  /**
   * Add repository.
   *
   * @param string $name
   *   Repository name.
   * @param mixed $config
   *   Repository config.
   * @param bool $append
   *   Append to existing repositories.
   *
   * @return bool
   *   TRUE if added.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function addRepository(string $name, mixed $config, bool $append = TRUE): bool {
    $json = $this->getFormattedData();

    if (!empty($json['repositories'])) {
      if ($append) {
        $json['repositories'][] = $config;
      }
      else {
        $json['repositories'] = [$config];
      }
    }
    else {
      $json['repositories'] = [$config];
    }

    return $this->addProperty('repositories', $json['repositories']);
  }

  /**
   * Add dependency.
   *
   * @param string $package
   *   Package name.
   * @param string $version
   *   Package version.
   * @param bool $is_dev
   *   Is dev dependency.
   *
   * @return bool
   *   TRUE if added.
   */
  public function addDependency(string $package, string $version, bool $is_dev = FALSE): bool {
    $this->validatePackageName($package);
    $this->validatePackageVersion($version);

    return $this->addLink($is_dev ? 'require-dev' : 'require', $package, $version, TRUE);
  }

  /**
   * Add a dev dependency.
   *
   * @param string $package
   *   Package name.
   * @param string $version
   *   Package version.
   *
   * @return bool
   *   TRUE if added.
   */
  public function addDevDependency(string $package, string $version): bool {
    return $this->addDependency($package, $version, TRUE);
  }

  /**
   * Add property after a specified property.
   *
   * @param string $name
   *   Property name.
   * @param mixed $value
   *   Property value.
   * @param string $after
   *   Property name after which the value should be added.
   */
  public function addPropertyAfter(string $name, mixed $value, string $after): void {
    $json = $this->getFormattedData();

    $name_parents = explode('.', $name);
    $name_single = array_pop($name_parents);

    $after_parents = explode('.', $after);
    $after_single = array_pop($after_parents);

    $existing_value_parent = Arrays::getValue($json, $after_parents);
    if (!is_null($existing_value_parent)) {
      $existing_value_parent = Arrays::insertAfterKey($existing_value_parent, $after_single, $name_single, $value);

      Arrays::setValue($json, $name_parents, $existing_value_parent);

      $this->refreshContents($json);
    }
  }

  /**
   * Merge property.
   *
   * @param string $name
   *   Property name.
   * @param array $value
   *   Property value.
   * @param bool $sort
   *   Should sort after merge.
   */
  public function mergeProperty(string $name, array $value, bool $sort = FALSE): void {
    $json = $this->getFormattedData();

    $existing_value = Arrays::getValue($json, $name);
    $existing_value = $existing_value ?: [];
    $existing_value = Arrays::mergeDeep($existing_value, $value);

    if ($sort) {
      ksort($existing_value);
    }

    Arrays::setValue($json, $name, $existing_value);

    $this->refreshContents($json);
  }

  /**
   * Refresh contents with new data.
   */
  protected function refreshContents(mixed $data): void {
    $reflection = new \ReflectionClass($this);
    $property = $reflection->getParentClass()->getProperty('contents');
    $property->setAccessible(TRUE);
    $property->setValue($this, $this->format($data));
  }

  /**
   * Get newline.
   */
  protected function getNewline(): string {
    $reflection = new \ReflectionClass($this);
    $property = $reflection->getParentClass()->getProperty('newline');
    $property->setAccessible(TRUE);

    return $property->getValue($this);
  }

  /**
   * Validate package name.
   */
  protected function validatePackageName($name) {
    // Regular expression for standard package names.
    $standardPattern = '/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/i';

    // List of special package names.
    $specialPackages = ['php', 'ext-', 'lib-'];

    // Check if package name matches any special package patterns.
    $isSpecialPackage = array_reduce($specialPackages, function ($carry, $item) use ($name): bool {
      return $carry || strpos($name, $item) === 0;
    }, FALSE);

    if (!$isSpecialPackage && !preg_match($standardPattern, $name)) {
      throw new \InvalidArgumentException(sprintf('Invalid package name "%s", should be in the form vendor/package or a special package (php, ext-*, lib-*)', $name));
    }
  }

  /**
   * Validate package version.
   */
  protected function validatePackageVersion($version) {
    $versionParser = new VersionParser();
    $versionParser->parseConstraints($version);
  }

}
