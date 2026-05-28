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

  public const int TERMINAL_WIDTH = 140;

  public const int TERMINAL_HEIGHT = 42;

  /** Poster timestamp for the installer recording (captures the welcome banner). */
  public const int POSTER_TIMESTAMP_MS = 2000;

  /** Poster timestamp for command videos that spend their first seconds bootstrapping inside Docker. */
  public const int POSTER_TIMESTAMP_MS_LATE = 30000;

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
   * Create a per-video workspace directory under `.artifacts/tmp/` within
   * the project root. Registers shutdown cleanup.
   */
  public function workspaceInit(string $prefix): string {
    $base = $this->project_root . '/.artifacts/tmp';
    if (!is_dir($base) && !mkdir($base, 0o755, TRUE) && !is_dir($base)) {
      throw new RuntimeException("Failed to create artifacts tmp dir: $base");
    }

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
   * Run the installer non-interactively into `$workspace/star_wars`.
   *
   * @param string $uri
   *   `--uri` value for the installer (typically the project_root so that
   *   the in-development template is used as the source).
   *
   * @return string
   *   Path to the installed project directory ($workspace/star_wars).
   */
  public function runInstaller(string $workspace, string $uri): string {
    $installer = "$workspace/installer.php";
    if (!is_file($installer)) {
      throw new RuntimeException("Installer not found in workspace: $installer");
    }

    $this->info('Running installer non-interactively');
    $this->note("URI: $uri");

    $env = [
      'VORTEX_INSTALLER_PROMPT_BUILD_NOW' => '0',
      'VORTEX_INSTALLER_PROMPT_NAME' => 'Star Wars',
      'VORTEX_INSTALLER_PROMPT_ORG' => 'Rebellion',
    ];

    $this->run([
      'php', 'installer.php',
      '--no-interaction',
      '--destination=star_wars',
      "--uri=$uri",
    ], $workspace, $env);

    $project_dir = "$workspace/star_wars";
    if (!is_dir($project_dir)) {
      throw new RuntimeException("Installer did not produce project at $project_dir");
    }

    $this->pass("Installer completed; project at $project_dir");

    return $project_dir;
  }

  /**
   * Run `ahoy build` in the given project directory. Optionally registers a
   * shutdown hook so the Docker stack is torn down on exit (default ON;
   * pass FALSE when the caller wants the stack to persist between runs).
   */
  public function runAhoyBuild(string $project_dir, string $compose_project_name, bool $register_cleanup = TRUE): void {
    $this->info('Running ahoy build (silent, outside recording)');

    $env = [
      'AHOY_CONFIRM_RESPONSE' => 'y',
      'AHOY_CONFIRM_WAIT_SKIP' => '1',
      'COMPOSE_PROJECT_NAME' => $compose_project_name,
    ];

    if ($register_cleanup) {
      $this->registerDockerCleanup($project_dir, $compose_project_name);
    }
    $this->run(['ahoy', 'build'], $project_dir, $env);

    $this->pass('ahoy build completed');
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
  public function recordSession(string $cwd, string $cast_path, string $command, string $title, array $env = [], ?int $cols = NULL, ?int $rows = NULL): void {
    $w = $cols ?? self::TERMINAL_WIDTH;
    $h = $rows ?? self::TERMINAL_HEIGHT;
    $size = "{$w}x{$h}";

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
   *   - Replace the workspace path with /home/user/demo.
   *   - Replace the project root path with /home/user/vortex.
   *   - Strip any leftover /Users/<name>/ user-home references.
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

    $contents = str_replace($this->project_root, '/home/user/vortex', $contents);
    $contents = str_replace(json_encode($this->project_root) ?: '', json_encode('/home/user/vortex'), $contents);

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
   * Multiply every event timestamp by $factor (< 1 speeds up, > 1 slows down).
   * Used to make recorded command demos playable in less wall-clock time.
   */
  public function applyTimeScale(string $cast_path, float $factor): void {
    if (!is_file($cast_path)) {
      throw new RuntimeException("Cast file not found: $cast_path");
    }
    if ($factor <= 0) {
      throw new RuntimeException("applyTimeScale factor must be > 0, got $factor");
    }

    $this->info("Applying time scale {$factor}x to cast");

    $lines = file($cast_path, FILE_IGNORE_NEW_LINES);
    if ($lines === FALSE || count($lines) < 2) {
      throw new RuntimeException("Cast file is empty or malformed: $cast_path");
    }

    $output = [$lines[0]];
    for ($i = 1, $n = count($lines); $i < $n; $i++) {
      $line = trim($lines[$i]);
      if ($line === '') {
        continue;
      }
      $event = json_decode($line, TRUE);
      if (!is_array($event) || !isset($event[0]) || !is_numeric($event[0])) {
        $output[] = $lines[$i];
        continue;
      }
      $event[0] = round(((float) $event[0]) * $factor, 6);
      $output[] = json_encode($event, JSON_UNESCAPED_SLASHES);
    }

    if (file_put_contents($cast_path, implode("\n", $output) . "\n") === FALSE) {
      throw new RuntimeException("Failed to write time-scaled cast: $cast_path");
    }

    $this->pass("Cast time-scaled by {$factor}x");
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
   * Render a single frame to PNG (1280px wide).
   *
   * @param int|null $at_ms
   *   Cast timestamp (ms) to snapshot. Defaults to POSTER_TIMESTAMP_MS.
   */
  public function renderPng(string $cast_path, string $png_path, ?int $at_ms = NULL): void {
    $at = $at_ms ?? self::POSTER_TIMESTAMP_MS;

    $this->info("Rendering PNG poster (at {$at}ms): $png_path");

    $frame_svg = $png_path . '.svg';

    $this->run([
      'node',
      $this->renderer_script,
      $cast_path,
      $frame_svg,
      '--line-height', self::LINE_HEIGHT,
      '--at', (string) $at,
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
   * Reads cols/rows from the cast file so the GIF matches the recording size.
   */
  public function renderGif(string $cast_path, string $gif_path): void {
    if (!$this->commandExists('agg')) {
      $this->note('Skipping GIF render (`agg` not installed)');
      return;
    }

    $first_line = '';
    $handle = fopen($cast_path, 'r');
    if ($handle !== FALSE) {
      $first_line = (string) fgets($handle);
      fclose($handle);
    }
    $header = json_decode($first_line, TRUE);
    $cols = is_array($header) && isset($header['width']) ? (int) $header['width'] : self::TERMINAL_WIDTH;
    $rows = is_array($header) && isset($header['height']) ? (int) $header['height'] : self::TERMINAL_HEIGHT;

    $this->info("Rendering GIF: $gif_path");

    $this->run([
      'agg',
      '--cols', (string) $cols,
      '--rows', (string) $rows,
      $cast_path,
      $gif_path,
    ]);

    $this->pass("GIF rendered: $gif_path");
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

  protected function commandExists(string $cmd): bool {
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

  public function rmrf(string $path): void {
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
