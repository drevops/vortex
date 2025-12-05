<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

use DrevOps\VortexInstaller\Utils\Validator;

/**
 * Represents a downloadable artifact (repository + reference).
 *
 * Immutable value object created via factory method.
 * Validates URI format and reference syntax on creation.
 */
final readonly class Artifact {

  /**
   * Private constructor - use factory method instead.
   *
   * @param string $repo
   *   The repository URL or local path.
   * @param string $ref
   *   The git reference (tag, branch, commit, or special refs).
   */
  private function __construct(
    private string $repo,
    private string $ref,
  ) {}

  /**
   * Create artifact from URI string.
   *
   * @param string|null $uri
   *   The URI in format: repo#ref or GitHub URL patterns.
   *   If null or empty, uses default repository and stable reference.
   *
   * @return self
   *   The created artifact.
   *
   * @throws \RuntimeException
   *   If URI is invalid or ref syntax is incorrect.
   */
  public static function fromUri(?string $uri): self {
    // Use default repository and stable reference if URI is empty or null.
    if ($uri === NULL || $uri === '') {
      return new self(RepositoryDownloader::DEFAULT_REPO, RepositoryDownloader::REF_STABLE);
    }

    [$repo, $ref] = self::parseUri($uri);
    return new self($repo, $ref);
  }

  /**
   * Create artifact from pre-parsed values.
   *
   * @param string $repo
   *   The repository URL or local path.
   * @param string $ref
   *   The git reference.
   *
   * @return self
   *   The created artifact.
   *
   * @throws \RuntimeException
   *   If ref syntax is invalid.
   */
  public static function create(string $repo, string $ref): self {
    // Validate ref syntax.
    if (!Validator::gitRef($ref)) {
      throw new \RuntimeException(sprintf('Invalid git reference: "%s". Reference must be a valid git tag, branch, or commit hash.', $ref));
    }
    return new self($repo, $ref);
  }

  /**
   * Get repository URL or path.
   */
  public function getRepo(): string {
    return $this->repo;
  }

  /**
   * Get git reference.
   */
  public function getRef(): string {
    return $this->ref;
  }

  /**
   * Get normalized repository URL (without .git extension).
   */
  public function getRepoUrl(): string {
    return self::normalizeRepoUrl($this->repo);
  }

  /**
   * Check if this is a remote repository (not local path).
   */
  public function isRemote(): bool {
    // Check for scp-style git URL (git@host:path).
    if (str_starts_with($this->repo, 'git@')) {
      return TRUE;
    }

    // Check for URLs with schemes.
    $parsed = parse_url($this->repo);
    if ($parsed !== FALSE && isset($parsed['scheme'])) {
      $scheme = strtolower($parsed['scheme']);
      return in_array($scheme, ['http', 'https', 'ssh', 'git'], TRUE);
    }

    return FALSE;
  }

  /**
   * Check if this is a local repository (not remote URL).
   */
  public function isLocal(): bool {
    return !$this->isRemote();
  }

  /**
   * Check if this artifact uses default repository and reference.
   */
  public function isDefault(): bool {
    // Check if using default repository (with or without .git).
    $default_repo_without_git = self::normalizeRepoUrl(RepositoryDownloader::DEFAULT_REPO);
    $is_default_repo = ($this->repo === RepositoryDownloader::DEFAULT_REPO || $this->repo === $default_repo_without_git);

    // Check if using default reference.
    $is_default_ref = ($this->ref === RepositoryDownloader::REF_STABLE || $this->ref === RepositoryDownloader::REF_HEAD);

    return $is_default_repo && $is_default_ref;
  }

  /**
   * Check if this artifact uses the stable reference.
   */
  public function isStable(): bool {
    return $this->ref === RepositoryDownloader::REF_STABLE;
  }

  /**
   * Check if this artifact uses the development reference (HEAD).
   */
  public function isDevelopment(): bool {
    return $this->ref === RepositoryDownloader::REF_HEAD;
  }

  /**
   * Parse URI into repository and reference.
   */
  protected static function parseUri(string $src): array {
    // @todo Remove @ref syntax support in 1.1.0 - use #ref instead.
    // Support deprecated @ref syntax by converting to #ref.
    // Match @ref at the end of URL, but not @ that's part of user@host.
    // For ssh:// and git://, @ in user@host comes before /, so @ref is
    // after last /.
    // For git@host:path, the host@ is before :, so @ref is after :.
    if (preg_match('~^(https?://[^#]+?)@([^/@]+)$~', $src, $matches)) {
      // https://example.com/repo@ref.
      $src = $matches[1] . '#' . $matches[2];
    }
    elseif (preg_match('~^((?:ssh|git)://(?:[^@/]+@)?[^#/]+/.+?)@([^/@]+)$~', $src, $matches)) {
      // ssh://git@host/path@ref or git://host/path@ref.
      $src = $matches[1] . '#' . $matches[2];
    }
    elseif (preg_match('~^(git@[^:]+:.+?)@([^/@]+)$~', $src, $matches)) {
      // git@host:path@ref.
      $src = $matches[1] . '#' . $matches[2];
    }

    // Try GitHub-specific patterns first.
    $github_pattern = self::detectGitHubUrlPattern($src);
    if ($github_pattern !== NULL) {
      [$repo, $ref] = $github_pattern;

      // Validate the extracted ref.
      if (!Validator::gitRef($ref)) {
        throw new \RuntimeException(sprintf('Invalid git reference: "%s". Reference must be a valid git tag, branch, or commit hash.', $ref));
      }

      return [$repo, $ref];
    }

    // Fall back to #ref parsing (standard git reference syntax).
    if (str_starts_with($src, 'https://') || str_starts_with($src, 'http://')) {
      if (!preg_match('~^(https?://[^/]+/[^/]+/[^#]+)(?:#(.+))?$~', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s". Use # to specify a reference (e.g., repo.git#tag).', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? RepositoryDownloader::REF_HEAD;
    }
    elseif (str_starts_with($src, 'ssh://') || str_starts_with($src, 'git://')) {
      if (!preg_match('~^((?:ssh|git)://(?:[^@/]+@)?[^#/]+/.+?)(?:#(.+))?$~', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s". Use # to specify a reference (e.g., git://host/repo#tag).', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? RepositoryDownloader::REF_HEAD;
    }
    elseif (str_starts_with($src, 'git@')) {
      if (!preg_match('~^(git@[^:]+:[^#]+)(?:#(.+))?$~', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s". Use # to specify a reference (e.g., git@host:repo#tag).', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? RepositoryDownloader::REF_HEAD;
    }
    elseif (str_starts_with($src, 'file://')) {
      if (!preg_match('~^file://(.+?)(?:#(.+))?$~', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid local repository format: "%s". Use # to specify a reference.', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? RepositoryDownloader::REF_HEAD;
    }
    else {
      if (!preg_match('~^(.+?)(?:#(.+))?$~', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid local repository format: "%s". Use # to specify a reference.', $src));
      }
      $repo = rtrim($matches[1], '/');
      $ref = $matches[2] ?? RepositoryDownloader::REF_HEAD;
    }

    if (!Validator::gitRef($ref)) {
      throw new \RuntimeException(sprintf('Invalid git reference: "%s". Reference must be a valid git tag, branch, or commit hash.', $ref));
    }

    return [$repo, $ref];
  }

  /**
   * Detect and parse GitHub-specific URL patterns.
   *
   * Supports direct GitHub URLs copied from browser.
   */
  protected static function detectGitHubUrlPattern(string $uri): ?array {
    // Pattern 1: /releases/tag/{ref}
    // Example: https://github.com/drevops/vortex/releases/tag/25.11.0
    if (preg_match('#^(https://github\.com/[^/]+/[^/]+)/releases/tag/(.+)$#', $uri, $matches)) {
      return [$matches[1], $matches[2]];
    }

    // Pattern 2: /tree/{ref}
    // Example: https://github.com/drevops/vortex/tree/1.2.3
    if (preg_match('#^(https://github\.com/[^/]+/[^/]+)/tree/(.+)$#', $uri, $matches)) {
      return [$matches[1], $matches[2]];
    }

    // Pattern 3: /commit/{ref}
    // Example: https://github.com/drevops/vortex/commit/abcd123
    if (preg_match('#^(https://github\.com/[^/]+/[^/]+)/commit/(.+)$#', $uri, $matches)) {
      return [$matches[1], $matches[2]];
    }

    // Pattern 4: .git#{ref} (HTTPS) - alternative to @ syntax
    // Example: https://github.com/drevops/vortex.git#25.11.0
    if (preg_match('~^(https://[^#]+\.git)#(.+)$~', $uri, $matches)) {
      return [$matches[1], $matches[2]];
    }

    // Pattern 5: git@...#{ref} (SSH) - alternative to @ syntax
    // Example: git@github.com:drevops/vortex#stable.
    if (preg_match('~^(git@[^#]+)#(.+)$~', $uri, $matches)) {
      return [$matches[1], $matches[2]];
    }

    return NULL;
  }

  /**
   * Normalize repository URL by stripping trailing .git extension.
   */
  protected static function normalizeRepoUrl(string $repo): string {
    return str_ends_with($repo, '.git') ? substr($repo, 0, -4) : $repo;
  }

}
