<?php

declare(strict_types=1);

/**
 * Shared recorder for Vortex documentation videos.
 *
 * Each per-video script constructs an instance of this class and orchestrates
 * a sequence of calls (workspace, bootstrap, record, postprocess, render,
 * install). The class is intentionally self-contained: no framework, no
 * composer dependencies.
 */
final class VideoRecorder {

  public const int TERMINAL_WIDTH = 120;

  public const int TERMINAL_HEIGHT = 36;

  public const int POSTER_TIMESTAMP_MS = 1000;

  public const string LINE_HEIGHT = '1.1';

  public function __construct(
    public readonly string $project_root,
    public readonly string $docs_static_dir,
    public readonly string $renderer_script,
  ) {
    if (!is_dir($this->project_root)) {
      throw new RuntimeException("Project root not found: {$this->project_root}");
    }
    if (!is_dir($this->docs_static_dir)) {
      throw new RuntimeException("Docs static dir not found: {$this->docs_static_dir}");
    }
    if (!is_file($this->renderer_script)) {
      throw new RuntimeException("Renderer script not found: {$this->renderer_script}");
    }
  }

  /**
   * Verify required binaries are on PATH.
   *
   * @param array<string> $extra
   *   Extra binaries to require beyond the base set.
   */
  public function checkDependencies(array $extra = []): void {
    $required = array_values(array_unique(array_merge(
      ['asciinema', 'php', 'composer', 'npx', 'node'],
      $extra,
    )));

    $missing = array_values(array_filter($required, fn(string $cmd): bool => !$this->commandExists($cmd)));

    if ($missing !== []) {
      $this->fail('Missing required dependencies: ' . implode(', ', $missing));
      $this->note('Install commands:');
      $this->note('  brew install asciinema expect composer  # macOS');
      $this->note('  apt-get install asciinema expect-dev composer  # Ubuntu/Debian');
      throw new RuntimeException('Missing dependencies: ' . implode(', ', $missing));
    }

    $this->pass('All required dependencies present');
  }

  /**
   * Create a per-video workspace directory. Registers shutdown cleanup.
   */
  public function workspaceInit(string $prefix): string {
    $base = sys_get_temp_dir();
    $unique = bin2hex(random_bytes(6));
    $workspace = "$base/vortex-video-$prefix-$unique";

    if (!mkdir($workspace, 0o755, TRUE) && !is_dir($workspace)) {
      throw new RuntimeException("Failed to create workspace: $workspace");
    }

    register_shutdown_function(function () use ($workspace): void {
      if (getenv('VORTEX_VIDEO_KEEP_WORKSPACE') === '1') {
        $this->note("Keeping workspace (VORTEX_VIDEO_KEEP_WORKSPACE=1): $workspace");
        return;
      }
      if (is_dir($workspace)) {
        $this->info("Cleaning up workspace: $workspace");
        $this->rmrf($workspace);
      }
    });

    $this->info("Created workspace: $workspace");

    return $workspace;
  }

  /**
   * Build installer.phar from .vortex/installer and copy it to $dest.
   */
  public function buildInstallerPhar(string $dest): void {
    $source_dir = $this->project_root . '/.vortex/installer';
    $built_phar = $source_dir . '/build/installer.phar';

    if (!is_dir($source_dir)) {
      throw new RuntimeException("Installer source not found: $source_dir");
    }

    $this->info('Building installer.phar from source');
    $this->note("Source: $source_dir");

    $this->run(['composer', 'install'], $source_dir);
    $this->run(['composer', 'build'], $source_dir);

    if (!is_file($built_phar)) {
      throw new RuntimeException("Build completed but installer.phar not found at $built_phar");
    }

    if (!copy($built_phar, $dest)) {
      throw new RuntimeException("Failed to copy installer.phar to $dest");
    }

    $this->pass("installer.phar built and copied to $dest");
  }

  /**
   * Run the installer non-interactively (and optionally `ahoy build`) so that
   * a video can record only the target command, not the bootstrap noise.
   *
   * @param string|null $uri
   *   Optional `--uri` value for the installer. Pass the project_root when
   *   recording against the in-development template; pass NULL to install
   *   the latest stable release from GitHub.
   */
  public function bootstrapProject(string $workspace, bool $with_build, string $compose_project_name, ?string $uri = NULL): string {
    $installer = "$workspace/installer.php";
    if (!is_file($installer)) {
      throw new RuntimeException("Installer not found in workspace: $installer");
    }

    $this->info('Bootstrapping project (silent, outside recording)');
    if ($uri !== NULL) {
      $this->note("URI: $uri");
    }

    $env = [
      'VORTEX_INSTALLER_PROMPT_BUILD_NOW' => '0',
      'VORTEX_INSTALLER_PROMPT_NAME' => 'Star Wars',
      'VORTEX_INSTALLER_PROMPT_ORG' => 'Rebellion',
    ];

    $cmd = ['php', 'installer.php', '--no-interaction', '--destination=star_wars'];
    if ($uri !== NULL) {
      $cmd[] = "--uri=$uri";
    }

    $this->run($cmd, $workspace, $env);

    $project_dir = "$workspace/star_wars";
    if (!is_dir($project_dir)) {
      throw new RuntimeException("Installer did not produce project at $project_dir");
    }

    $this->pass("Installer completed; project at $project_dir");

    if ($with_build) {
      $this->info('Running ahoy build (silent, outside recording)');
      $build_env = [
        'AHOY_CONFIRM_RESPONSE' => 'y',
        'AHOY_CONFIRM_WAIT_SKIP' => '1',
        'COMPOSE_PROJECT_NAME' => $compose_project_name,
      ];
      $this->registerDockerCleanup($project_dir, $compose_project_name);
      $this->run(['ahoy', 'build'], $project_dir, $build_env);
      $this->pass('ahoy build completed');
    }

    return $project_dir;
  }

  /**
   * Register a shutdown hook that tears down a docker compose stack.
   *
   * Called automatically by bootstrapProject() when $with_build=true so that
   * even a failed run releases its Docker network and container slots.
   */
  public function registerDockerCleanup(string $project_dir, string $compose_project_name): void {
    register_shutdown_function(function () use ($project_dir, $compose_project_name): void {
      if (getenv('VORTEX_VIDEO_KEEP_WORKSPACE') === '1') {
        $this->note("Keeping Docker stack (VORTEX_VIDEO_KEEP_WORKSPACE=1): $compose_project_name");
        return;
      }
      if (!is_dir($project_dir)) {
        return;
      }
      $this->info("Tearing down Docker stack: $compose_project_name");
      try {
        $this->run(
          ['docker', 'compose', 'down', '--volumes', '--remove-orphans', '--timeout', '30'],
          $project_dir,
          ['COMPOSE_PROJECT_NAME' => $compose_project_name],
        );
      }
      catch (\Throwable $e) {
        $this->note('Docker cleanup failed (non-fatal): ' . $e->getMessage());
      }
    });
  }

  /**
   * Run asciinema rec against a target command, producing an asciicast v2 JSON file.
   *
   * @param string $cwd
   *   The cwd for asciinema (and therefore for the recorded command).
   * @param string $cast_path
   *   Output cast file path.
   * @param string $command
   *   Command to record. Passed verbatim to asciinema --command.
   * @param string $title
   *   Display title embedded in the asciicast header.
   * @param array<string,string> $env
   *   Additional env vars to merge into the asciinema process environment.
   */
  public function recordSession(string $cwd, string $cast_path, string $command, string $title, array $env = []): void {
    $size = self::TERMINAL_WIDTH . 'x' . self::TERMINAL_HEIGHT;

    $this->info('Recording asciinema session');
    $this->note("cwd: $cwd");
    $this->note("size: $size");
    $this->note("title: $title");
    $this->note("command: $command");
    $this->note("output: $cast_path");

    $this->run([
      'asciinema', 'rec',
      "--window-size=$size",
      '--output-format=asciicast-v2',
      "--title=$title",
      "--command=$command",
      '--overwrite',
      $cast_path,
    ], $cwd, $env);

    if (!is_file($cast_path)) {
      throw new RuntimeException("Recording produced no cast file at $cast_path");
    }

    $this->pass('Recording complete');
  }

  /**
   * Post-process a recorded cast:
   *   - Drop line 2 (the spawn command echo from asciinema).
   *   - Replace the workspace path (if provided) with /home/user/demo.
   *   - Anonymise /Users/<name>/ and /var/folders/... temp paths.
   */
  public function postprocessCast(string $cast_path, ?string $workspace = NULL): void {
    if (!is_file($cast_path)) {
      throw new RuntimeException("Cast file not found: $cast_path");
    }

    $this->info('Post-processing cast');

    $lines = file($cast_path, FILE_IGNORE_NEW_LINES);
    if ($lines === FALSE || count($lines) < 2) {
      throw new RuntimeException("Cast file is empty or malformed: $cast_path");
    }

    array_splice($lines, 1, 1);

    $contents = implode("\n", $lines) . "\n";

    if ($workspace !== NULL && $workspace !== '') {
      $contents = str_replace($workspace, '/home/user/demo', $contents);
      $contents = str_replace(json_encode($workspace) ?: '', json_encode('/home/user/demo'), $contents);
    }

    $contents = preg_replace('#/var/folders/[^/]+/[^/]+/T/[^/\s"\\\\]+#', '/tmp/anon', $contents);
    if ($contents === NULL) {
      throw new RuntimeException("Failed to scrub temp paths in cast: $cast_path");
    }

    $contents = preg_replace('#/Users/[^/]+/#', '/home/user/', $contents);
    if ($contents === NULL) {
      throw new RuntimeException("Failed to anonymise user paths in cast: $cast_path");
    }

    if (file_put_contents($cast_path, $contents) === FALSE) {
      throw new RuntimeException("Failed to write postprocessed cast: $cast_path");
    }

    $this->pass('Cast post-processed');
  }

  /**
   * Render the cast to an animated SVG via svg-term-render.js.
   */
  public function renderSvg(string $cast_path, string $svg_path): void {
    $this->info("Rendering SVG: $svg_path");

    $this->run([
      'node',
      $this->renderer_script,
      $cast_path,
      $svg_path,
      '--line-height', self::LINE_HEIGHT,
    ]);

    if (!is_file($svg_path)) {
      throw new RuntimeException("SVG render produced no file: $svg_path");
    }

    $this->pass("SVG rendered: $svg_path");
  }

  /**
   * Render a single frame at POSTER_TIMESTAMP_MS to PNG (1280px wide).
   */
  public function renderPng(string $cast_path, string $png_path): void {
    $this->info("Rendering PNG poster: $png_path");

    $frame_svg = $png_path . '.svg';

    $this->run([
      'node',
      $this->renderer_script,
      $cast_path,
      $frame_svg,
      '--line-height', self::LINE_HEIGHT,
      '--at', (string) self::POSTER_TIMESTAMP_MS,
    ]);

    $this->run([
      'npx', 'sharp-cli',
      '-i', $frame_svg,
      '-o', $png_path,
      '-f', 'png',
      'resize', '1280',
    ]);

    @unlink($frame_svg);

    if (!is_file($png_path)) {
      throw new RuntimeException("PNG render produced no file: $png_path");
    }

    $this->pass("PNG rendered: $png_path");
  }

  /**
   * Render the cast to an animated GIF via `agg`. No-op if `agg` is missing.
   */
  public function renderGif(string $cast_path, string $gif_path): void {
    if (!$this->commandExists('agg')) {
      $this->note('Skipping GIF render (`agg` not installed)');
      return;
    }

    $this->info("Rendering GIF: $gif_path");

    $this->run([
      'agg',
      '--cols', (string) self::TERMINAL_WIDTH,
      '--rows', (string) self::TERMINAL_HEIGHT,
      $cast_path,
      $gif_path,
    ]);

    $this->pass("GIF rendered: $gif_path");
  }

  /**
   * Copy $workspace/new.{json,svg,png,gif} to $docs_static_dir/$prefix.<ext>.
   * JSON, SVG, and PNG are required; GIF is optional.
   */
  public function installArtifacts(string $workspace, string $prefix): void {
    $this->info("Installing artifacts under prefix '$prefix'");

    $required = ['json', 'svg', 'png'];
    foreach ($required as $ext) {
      $src = "$workspace/new.$ext";
      if (!is_file($src)) {
        throw new RuntimeException("Required artifact missing: $src");
      }
    }

    foreach (['json', 'svg', 'png', 'gif'] as $ext) {
      $src = "$workspace/new.$ext";
      if (!is_file($src)) {
        continue;
      }
      $dst = $this->docs_static_dir . "/$prefix.$ext";
      if (!copy($src, $dst)) {
        throw new RuntimeException("Failed to copy $src to $dst");
      }
      $this->note("Installed: $dst");
    }

    $this->pass("Artifacts installed under prefix '$prefix'");
  }

  /**
   * Run a subprocess inheriting parent stdio. Throw on non-zero exit.
   *
   * @param array<string> $command
   *   Argv array; passed to proc_open verbatim.
   * @param string|null $cwd
   *   Working directory for the child.
   * @param array<string,string> $env_overrides
   *   Env vars merged on top of the current environment.
   */
  public function run(array $command, ?string $cwd = NULL, array $env_overrides = []): int {
    $current_env = getenv();
    if (!is_array($current_env)) {
      $current_env = [];
    }
    $env = $env_overrides === [] ? NULL : array_merge($current_env, $env_overrides);

    $pretty = implode(' ', array_map(fn($arg): string => escapeshellarg((string) $arg), $command));
    $this->note('Running: ' . $pretty . ($cwd !== NULL ? " (in $cwd)" : ''));

    $proc = proc_open($command, [], $pipes, $cwd, $env);
    if (!is_resource($proc)) {
      throw new RuntimeException("Failed to start: $pretty");
    }

    $exit_code = proc_close($proc);
    if ($exit_code !== 0) {
      throw new RuntimeException("Command failed (exit $exit_code): $pretty");
    }

    return $exit_code;
  }

  public function info(string $msg): void {
    fwrite(STDERR, "\033[0;34m[INFO]\033[0m $msg\n");
  }

  public function note(string $msg): void {
    fwrite(STDERR, "       $msg\n");
  }

  public function pass(string $msg): void {
    fwrite(STDERR, "\033[0;32m[ OK ]\033[0m $msg\n");
  }

  public function fail(string $msg): void {
    fwrite(STDERR, "\033[0;31m[FAIL]\033[0m $msg\n");
  }

  private function commandExists(string $cmd): bool {
    $path = getenv('PATH');
    if (!is_string($path) || $path === '') {
      return FALSE;
    }
    foreach (explode(PATH_SEPARATOR, $path) as $dir) {
      if ($dir === '') {
        continue;
      }
      $candidate = $dir . DIRECTORY_SEPARATOR . $cmd;
      if (is_file($candidate) && is_executable($candidate)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function rmrf(string $path): void {
    if (is_link($path) || is_file($path)) {
      @unlink($path);
      return;
    }
    if (!is_dir($path)) {
      return;
    }
    $entries = scandir($path);
    if ($entries === FALSE) {
      return;
    }
    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $this->rmrf("$path/$entry");
    }
    @rmdir($path);
  }

}
