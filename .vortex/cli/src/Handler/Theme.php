<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "theme" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Theme extends AbstractHandler implements OptionsInterface {

  const OLIVERO = 'olivero';

  const CLARO = 'claro';

  const STARK = 'stark';

  const CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $v = is_string($value) ? $value : '';
    $theme_custom = $context->answers['theme_custom'] ?? NULL;

    // If user selected 'custom', use the ThemeCustom response instead.
    if ($v === 'custom' && is_string($theme_custom)) {
      $v = $theme_custom;
    }

    $t = $context->directory;
    $w = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    // Handle core themes (no custom theme files needed).
    if (in_array($v, ['olivero', 'claro', 'stark'], TRUE)) {
      // Remove custom theme files if they exist.
      $file_tmpl = static::findThemeFile($t, $w);
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

    // Handle custom themes.
    Env::writeValueDotenv('DRUPAL_THEME', $v, $t . '/.env');
    Env::writeValueDotenv('DRUPAL_MAINTENANCE_THEME', $v, $t . '/.env');

    // Find the theme file in the destination directory.
    $file_dst = static::findThemeFile($context->directory, $w, $v);

    // Remove the theme-related files from the template if not found OR
    // if found, but the theme is not from Vortex.
    if ($context->update && (empty($file_dst) || !static::isVortexTheme(dirname($file_dst)))) {
      $file_tmpl = static::findThemeFile($t, $w);
      if (!empty($file_tmpl) && is_readable($file_tmpl)) {
        File::remove(dirname($file_tmpl));
      }
    }

    File::replaceContentAsync([
      'your_site_theme' => $v,
      'YourSiteTheme' => Converter::pascal($v),
    ]);

    File::renameInDir($t, 'your_site_theme', $v);
    File::renameInDir($t, 'YourSiteTheme', Converter::pascal($v));
  }

  /**
   * Remove theme-related configuration lines from various files.
   *
   * @param string $tmp_dir
   *   The directory containing the files to update.
   */
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

    File::removeLineInFile($tmp_dir . '/.twig-cs-fixer.php', "\$finder->in(__DIR__ . '/web/themes/custom');");

    File::replaceContentInFile($tmp_dir . '/.ahoy.yml', 'cmd: ahoy lint-be && ahoy lint-fe && ahoy lint-tests', 'cmd: ahoy lint-be && ahoy lint-tests');
    File::replaceContentInFile($tmp_dir . '/.ahoy.yml', 'cmd: ahoy lint-be-fix && ahoy lint-fe-fix', 'cmd: ahoy lint-be-fix');
  }

  /**
   * Find a custom theme info file within a project directory.
   *
   * @param string $dir
   *   The project directory to search in.
   * @param string $webroot
   *   The webroot directory name.
   * @param string|null $text
   *   Optional text that a matching file path must contain.
   *
   * @return string|null
   *   The path to the matching theme file, or NULL when none is found.
   */
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

  /**
   * Check whether a theme directory contains a Vortex-generated theme.
   *
   * @param string $dir
   *   The theme directory to check.
   *
   * @return bool
   *   TRUE when the directory contains a Vortex theme, FALSE otherwise.
   */
  protected static function isVortexTheme(string $dir): bool {
    $c1 = file_exists($dir . '/scss/_variables.scss');
    $c2 = file_exists($dir . '/package.json');
    $c3 = File::contains($dir . '/package.json', 'build-dev');

    return $c1 && $c2 && $c3;
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::OLIVERO => 'Olivero',
      self::CLARO => 'Claro',
      self::STARK => 'Stark',
      self::CUSTOM => 'Custom (next prompt)',
    ];
  }

}
