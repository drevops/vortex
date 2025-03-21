<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Theme extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if ($this->isInstalled()) {
      $value = Env::getFromDotenv('DRUPAL_THEME', $this->dstDir);
      if (!empty($value)) {
        return $value;
      }
    }

    $path = static::findThemeFile($this->dstDir, $this->webroot);

    if (empty($path)) {
      return NULL;
    }

    return str_replace(['.info.yml', '.info'], '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $v = (string) $this->response;
    $t = $this->tmpDir;
    $w = $this->webroot;

    if (empty($v)) {
      $file_tmpl = static::findThemeFile($t, $w);
      if (!empty($file_tmpl) && is_readable($file_tmpl)) {
        File::rmdir(dirname($file_tmpl));
        File::rmdirEmpty(dirname($file_tmpl));

        File::removeLine($t . '/phpcs.xml', '<file>web/themes/custom</file>');
        File::removeLine($t . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/build\/.*</exclude-pattern>');
        File::removeLine($t . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/fonts\/.*</exclude-pattern>');
        File::removeLine($t . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/images\/.*</exclude-pattern>');
        File::removeLine($t . '/phpcs.xml', '<exclude-pattern>web\/themes\/custom\/.*\/node_modules\/.*</exclude-pattern>');

        File::removeLine($t . '/phpstan.neon', '- web/themes/custom');

        File::removeLine($t . '/phpmd.xml', '<exclude-pattern>*/web/themes/contrib/*</exclude-pattern>');

        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Unit</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Kernel</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom/*/tests/src/Functional</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory suffix="Test.php">web/themes/custom</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom/*/node_modules</directory>');

        File::removeLine($t . '/rector.php', "\$drupalRoot . '/themes/custom',");

        File::removeLine($t . '/.twig-cs-fixer.php', "\$finder->in(__DIR__ . '/web/themes/custom');");

        File::replaceContent($t . '/.ahoy.yml', 'cmd: ahoy lint-be && ahoy lint-fe && ahoy lint-tests', 'cmd: ahoy lint-be && ahoy lint-tests');
        File::replaceContent($t . '/.ahoy.yml', 'cmd: ahoy lint-be-fix && ahoy lint-fe-fix', 'cmd: ahoy lint-be-fix');
      }

      File::removeTokenInDir($this->tmpDir, 'DRUPAL_THEME');

      return;
    }

    File::replaceContent($this->dstDir . '/.env', '/DRUPAL_THEME=.*/', 'DRUPAL_THEME=' . $v);

    // Find the theme file in the destination directory.
    $file_dst = static::findThemeFile($this->dstDir, $w, $v);

    // Remove the theme files from the template if not found OR if found, but
    // the theme is not from Vortex.
    if (
      $this->isInstalled()
      &&
      (
        empty($file_dst)
        ||
        !static::isVortexTheme(dirname($file_dst))
      )
    ) {
      $file_tmpl = static::findThemeFile($t, $w);
      if (!empty($file_tmpl) && is_readable($file_tmpl)) {
        File::rmdir(dirname($file_tmpl));

        File::removeLine($t . '/phpunit.xml', '<directory suffix="Test.php">web/themes/custom</directory>');
        File::removeLine($t . '/phpunit.xml', '<directory>web/themes/custom/*/node_modules</directory>');

        return;
      }
    }

    File::replaceContentInDir($t, 'your_site_theme', $v);
    File::replaceContentInDir($t, 'YourSiteTheme', Converter::pascal($v));

    File::renameInDir($t, 'your_site_theme', $v);
    File::renameInDir($t, 'YourSiteTheme', Converter::pascal($v));
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
    $c2 = file_exists($dir . '/Gruntfile.js');
    $c3 = file_exists($dir . '/package.json');
    $c4 = File::contains($dir . '/package.json', 'build-dev');

    return $c1 && $c2 && $c3 && $c4;
  }

}
