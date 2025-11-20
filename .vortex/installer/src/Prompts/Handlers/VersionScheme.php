<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Tui;

class VersionScheme extends AbstractHandler {

  const CALVER = 'calver';

  const SEMVER = 'semver';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Release versioning scheme';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): string {
    $label1 = Tui::bold('Calendar Versioning (CalVer)');
    $label11 = Tui::underscore('year.month.patch');
    $label12 = Tui::underscore('24.1.0');

    $label2 = Tui::bold('Semantic Versioning (SemVer)');
    $label21 = Tui::underscore('major.minor.patch');
    $label22 = Tui::underscore('1.0.0');

    $label3 = Tui::bold('Other');

    return <<<DOC
Choose your versioning scheme:

    ○ {$label1}
      {$label11} (E.g., {$label12})
      https://calver.org

    ○ {$label2}
      {$label21} (E.g., {$label22})
      https://semver.org

    ○ {$label3}
      Custom versioning scheme of your choice.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select your version scheme.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::CALVER => 'Calendar Versioning (CalVer)',
      self::SEMVER => 'Semantic Versioning (SemVer)',
      self::OTHER => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::CALVER;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $scheme = Env::getFromDotenv('VORTEX_RELEASE_VERSION_SCHEME', $this->dstDir);

    if (in_array($scheme, [self::CALVER, self::SEMVER, self::OTHER], TRUE)) {
      return $scheme;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    Env::writeValueDotenv('VORTEX_RELEASE_VERSION_SCHEME', $v, $t . '/.env');

    if ($v === self::SEMVER) {
      File::removeTokenAsync('!VERSION_RELEASE_SCHEME_SEMVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_CALVER');
    }
    elseif ($v === self::CALVER) {
      File::removeTokenAsync('!VERSION_RELEASE_SCHEME_CALVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_SEMVER');
    }
    else {
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_SEMVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_CALVER');
    }
  }

}
