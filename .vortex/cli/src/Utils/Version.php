<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

/**
 * Helpers for reasoning about Vortex major versions.
 *
 * A single CLI build serves one major line (for example, the 1.x build
 * resolves the latest 1.x release, the 2.x build resolves the latest 2.x
 * release). These helpers derive the major from the CLI's own stamped
 * version and from an installed project, so the CLI can both target the
 * right major and refuse cross-major operations.
 */
class Version {

  /**
   * Pattern matching the Vortex badge in a project README.
   *
   * The capture group holds the git reference the badge was stamped with, so
   * a README that matches is one a reference can be read from.
   */
  const BADGE_REGEX = '#badge/Vortex-(.+?)-65ACBC\.svg#';

  /**
   * Extract the major version number from a version string.
   *
   * @param string|null $version
   *   A version string such as "1.40.0", "v2.0.0", "2.x-dev" or
   *   "1.0.0+2025.11.0". Unstamped or development values (for example
   *   "develop" or "@vortex-cli-version@") have no derivable major.
   *
   * @return int|null
   *   The major version number, or NULL when it cannot be determined.
   */
  public static function major(?string $version): ?int {
    if ($version === NULL) {
      return NULL;
    }

    return preg_match('/^\s*v?(\d+)\./', $version, $matches) ? (int) $matches[1] : NULL;
  }

  /**
   * Build a release tag prefix for a version's major (for example "1.").
   *
   * The trailing dot ensures "1." matches "1.40.0" but not "11.0.0".
   *
   * @param string|null $version
   *   A version string.
   *
   * @return string|null
   *   The dot-terminated major prefix, or NULL when the major is unknown.
   */
  public static function releasePrefix(?string $version): ?string {
    $major = self::major($version);

    return $major === NULL ? NULL : $major . '.';
  }

  /**
   * Extract the major version number from a Composer version constraint.
   *
   * @param string|null $constraint
   *   A constraint such as "^1.1.0", "~2.0" or "2.x-dev".
   *
   * @return int|null
   *   The first major version number in the constraint, or NULL.
   */
  public static function majorFromConstraint(?string $constraint): ?int {
    if ($constraint === NULL) {
      return NULL;
    }

    return preg_match('/(\d+)/', $constraint, $matches) ? (int) $matches[1] : NULL;
  }

  /**
   * Detect the Vortex reference an installed project was last installed from.
   *
   * The README badge records the reference of the installed release, which is
   * the only place the exact version is preserved: composer.json pins the
   * tooling package's major rather than the template's version. Shields.io
   * escapes a literal dash in the label as a double dash.
   *
   * @param string $dir
   *   The project directory.
   *
   * @return string|null
   *   The git reference, or NULL when the badge is absent or unreadable.
   */
  public static function detectProjectRef(string $dir): ?string {
    $readme = $dir . DIRECTORY_SEPARATOR . 'README.md';

    if (!is_file($readme) || !is_readable($readme)) {
      return NULL;
    }

    if (!preg_match(self::BADGE_REGEX, File::read($readme), $matches)) {
      return NULL;
    }

    $ref = str_replace('--', '-', $matches[1]);

    return Validator::gitRef($ref) ? $ref : NULL;
  }

  /**
   * Detect the Vortex major of an installed project from its composer.json.
   *
   * The project's pinned 'drevops/vortex-tooling' major is the provenance
   * signal: a 1.x project requires '^1', a 2.x project requires '^2'. When the
   * package is absent (a fresh directory or a pre-tooling project), the major
   * is undeterminable and the caller treats it as compatible.
   *
   * @param string $dir
   *   The project directory.
   *
   * @return int|null
   *   The project's major, or NULL when it cannot be determined.
   */
  public static function detectProjectMajor(string $dir): ?int {
    $composer_json = $dir . DIRECTORY_SEPARATOR . 'composer.json';

    if (!is_file($composer_json)) {
      return NULL;
    }

    $data = json_decode((string) file_get_contents($composer_json), TRUE);
    if (!is_array($data)) {
      return NULL;
    }

    $constraint = $data['require']['drevops/vortex-tooling'] ?? NULL;

    return is_string($constraint) ? self::majorFromConstraint($constraint) : NULL;
  }

}
