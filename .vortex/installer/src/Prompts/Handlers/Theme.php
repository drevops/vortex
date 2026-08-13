<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class Theme extends AbstractHandler {

  const OLIVERO = 'olivero';

  const CLARO = 'claro';

  const STARK = 'stark';

  const CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Theme';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select which Drupal theme to use.';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::OLIVERO => 'Olivero',
      self::CLARO => 'Claro',
      self::STARK => 'Stark',
      self::CUSTOM => 'Custom (next prompt)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::CUSTOM;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = $this->discoverName();

    if ($value !== NULL) {
      return in_array($value, [self::OLIVERO, self::CLARO, self::STARK], TRUE) ? $value : self::CUSTOM;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedValue(array $responses): null|string|bool|array {
    return $this->discover();
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses, mixed $resolved): ?string {
    if (is_string($resolved)) {
      return sprintf('Theme will be set to "%s".', $resolved);
    }

    return NULL;
  }

  /**
   * Discover the theme name from the filesystem or environment.
   *
   * @return null|string|bool|array
   *   The theme name if found, NULL if not found.
   */
  public function discoverName(): null|string|bool|array {
    if ($this->isInstalled()) {
      $value = Env::getFromDotenv('DRUPAL_THEME', $this->destinationDir);
      if (!empty($value)) {
        return $value;
      }
    }

    $path = self::findThemeFile($this->destinationDir, $this->webroot);

    if (empty($path)) {
      return NULL;
    }

    return str_replace(['.info.yml', '.info'], '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if ($v === self::CUSTOM && isset($this->responses[ThemeCustom::id()])) {
      $v = $this->responses[ThemeCustom::id()];
    }

    $t = $this->tmpDir;
    $w = $this->webroot;

    if (in_array($v, [self::OLIVERO, self::CLARO, self::STARK])) {
      $file_tmpl = self::findThemeFile($t, $w);
      if (!empty($file_tmpl) && is_readable($file_tmpl)) {
        File::remove(dirname($file_tmpl));
        File::rmdirIfEmpty(dirname($file_tmpl));

        $this->removeThemeConfigLines($t);
      }

      File::removeTokenAsync('DRUPAL_THEME');

      Env::writeValueDotenv('DRUPAL_THEME', $v, $t . '/.env');
      Env::writeValueDotenv('DRUPAL_MAINTENANCE_THEME', $v, $t . '/.env');

      return;
    }

    Env::writeValueDotenv('DRUPAL_THEME', $v, $t . '/.env');
    Env::writeValueDotenv('DRUPAL_MAINTENANCE_THEME', $v, $t . '/.env');

    $file_dst = self::findThemeFile($this->destinationDir, $w, $v);

    if (
      $this->isInstalled()
      &&
      (
        empty($file_dst)
        ||
        !self::isVortexTheme(dirname($file_dst))
      )
    ) {
      $file_tmpl = self::findThemeFile($t, $w);
      if (!empty($file_tmpl) && is_readable($file_tmpl)) {
        File::remove(dirname($file_tmpl));
      }
    }

    File::replaceContentAsync([
      'your_site_theme' => $v,
      'yourSiteTheme' => Converter::camel($v),
      'YourSiteTheme' => Converter::pascal($v),
    ]);

    File::renameInDir($t, 'your_site_theme', $v);
    File::renameInDir($t, 'YourSiteTheme', Converter::pascal($v));
  }

  protected function removeThemeConfigLines(string $tmp_dir): void {
    File::removeLineInFile($tmp_dir . '/phpcs.xml', '<file>web/themes/custom</file>');
    File::removeLineInFile($tmp_dir . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/build\/.*</exclude-pattern>');
    File::removeLineInFile($tmp_dir . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/fonts\/.*</exclude-pattern>');
    File::removeLineInFile($tmp_dir . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/images\/.*</exclude-pattern>');
    File::removeLineInFile($tmp_dir . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/node_modules\/.*</exclude-pattern>');

    File::removeLineInFile($tmp_dir . '/phpstan.neon', '- web/themes/custom');

    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Unit</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/**/tests/src/Unit</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Kernel</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/**/tests/src/Kernel</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Functional</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/**/tests/src/Functional</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/FunctionalJavascript</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/**/tests/src/FunctionalJavascript</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory suffix="Test.php">web/themes/custom</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/*/node_modules</directory>');
    File::removeLineInFile($tmp_dir . '/phpunit.xml', '<directory>web/themes/custom/**/node_modules</directory>');

    File::removeLineInFile($tmp_dir . '/rector.php', "__DIR__ . '/web/themes/custom',");
    File::removeLineInFile($tmp_dir . '/rector.php', "__DIR__ . '/web/themes/custom/*/src/Hook/*',");

    File::removeLineInFile($tmp_dir . '/.twig-cs-fixer.php', "\$finder->in(__DIR__ . '/web/themes/custom');");

    File::replaceContentInFile($tmp_dir . '/.ahoy.yml', 'cmd: ahoy lint-be && ahoy lint-fe && ahoy lint-tests', 'cmd: ahoy lint-be && ahoy lint-tests');
    File::replaceContentInFile($tmp_dir . '/.ahoy.yml', 'cmd: ahoy lint-be-fix && ahoy lint-fe-fix', 'cmd: ahoy lint-be-fix');
  }

  protected static function findThemeFile(string $dir, string $webroot, ?string $text = NULL): ?string {
    $locations = [
      sprintf('%s/%s/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/sites/all/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/sites/all/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/profiles/*/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/profiles/*/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/profiles/custom/*/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/profiles/custom/*/themes/custom/*/*.info.yml', $dir, $webroot),
    ];

    return File::findMatchingPath($locations, $text);
  }

  protected static function isVortexTheme(string $dir): bool {
    $c1 = file_exists($dir . '/scss/_variables.scss');
    $c2 = file_exists($dir . '/package.json');
    $c3 = File::contains($dir . '/package.json', 'build-dev');

    return $c1 && $c2 && $c3;
  }

}
