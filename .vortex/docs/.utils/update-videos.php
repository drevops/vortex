#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/VideoRecorder.php';

/**
 * Update one or more documentation videos.
 *
 * Single workspace mode. The orchestrator always uses
 *   .artifacts/tmp/videos-workspace/
 * with the consumer project at .artifacts/tmp/videos-workspace/star_wars/
 * and Docker compose project name "vortex_videos".
 *
 * Default invocation wipes the workspace (via `ahoy reset` + `rm -rf`) and
 * bootstraps from scratch. The `--keep` flag skips the bootstrap and uses
 * the existing workspace, useful for re-recording a subset of videos
 * without paying the install + build cost.
 *
 * Output is hardcoded to .vortex/docs/static/img/<name>.{json,svg,png,gif}.
 *
 * Usage:
 *   php update-videos.php                          # wipe + bootstrap + record all six
 *   php update-videos.php lint provision           # wipe + bootstrap + record lint, provision
 *   php update-videos.php lint,test                # commas also accepted
 *   php update-videos.php --keep lint              # reuse workspace, record lint only
 */

const WORKSPACE_REL = '.artifacts/tmp/videos-workspace';

const COMPOSE_PROJECT = 'vortex_videos';

/**
 * Per-video configuration.
 *
 * - command:   the command executed inside the recording. NULL means the
 *              install expect script is used instead.
 * - speed:     playback speed multiplier. 1.0 = recorded speed, 2.0 = 2x faster.
 * - cols/rows: terminal dimensions passed to asciinema and used for renders.
 * - poster_ms: cast timestamp (ms) at which the PNG poster frame is taken.
 *              NULL means use the last frame of the cast.
 * - typer:     wrap the command with the simulated-typing intro from
 *              type-and-run.php. The install demo is FALSE because its expect
 *              script types the command line itself.
 */
const VIDEOS = [
  'cli-install' => [
    'command' => NULL,
    'speed' => 1.0,
    'cols' => 80,
    'rows' => 42,
    // Late enough for the panel overview to have settled, which is the frame
    // that says what this tool is before anyone presses play.
    'poster_ms' => 7000,
    'typer' => FALSE,
  ],
  'build' => [
    'command' => 'ahoy build',
    'speed' => 1.0,
    'cols' => 80,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'provision' => [
    'command' => 'ahoy provision',
    'speed' => 1.0,
    'cols' => 80,
    'rows' => 42,
    'poster_ms' => NULL,
    'typer' => TRUE,
  ],
  'lint' => [
    'command' => 'ahoy lint',
    'speed' => 2.0,
    'cols' => 80,
    'rows' => 42,
    'poster_ms' => NULL,
    'typer' => TRUE,
  ],
  'test' => [
    'command' => 'ahoy test',
    'speed' => 2.0,
    'cols' => 80,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'test-bdd' => [
    'command' => 'ahoy test-bdd',
    'speed' => 1.0,
    'cols' => 80,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
];

function usage(): void {
  fwrite(STDERR, "Usage: php update-videos.php [--keep] [video-name ...]\n");
  fwrite(STDERR, '  Video names: ' . implode(', ', array_keys(VIDEOS)) . "\n");
  fwrite(STDERR, "  Default: all videos\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Default mode wipes '.artifacts/tmp/videos-workspace/' (via 'ahoy reset'\n");
  fwrite(STDERR, "+ rm) and bootstraps from scratch (fetch vortex.phar, run the install,\n");
  fwrite(STDERR, "ahoy build).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "--keep reuses the existing workspace and skips the bootstrap. Requires the\n");
  fwrite(STDERR, "Docker stack to be running (the script probes and exits cleanly otherwise).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Video names may be space or comma separated (lint test = lint,test).\n");
}

function build_install_expect_script(string $uri, int $cols, int $rows): string {
  $body = <<<'EXPECT'
#!/usr/bin/env expect

# Drive an interactive Vortex CLI install for the documentation demo video.
#
# The script types the command a reader would type, then answers the panel TUI
# with paced keystrokes so the recording reads like someone using it. The panel
# is addressed by position rather than by text, because a panel redraws in
# place and an expect pattern would match a line that is no longer on screen.
#
# A background reader drains the child's output for the whole session: without
# it the pty buffer fills, the child blocks mid-write and never returns to its
# input loop.

set timeout 180
log_user 1

set cli_uri "{{URI}}"

set env(TERM) xterm-256color
set env(COLUMNS) {{COLS}}
set env(LINES) {{ROWS}}
set env(VORTEX_CLI_INSTALL_PROMPT_BUILD_NOW) 0

set send_slow {1 .06}

proc press {seq {secs 1}} {
  send -- $seq
  sleep $secs
}

proc repeat {seq times {secs 1}} {
  for {set i 0} {$i < $times} {incr i} {
    press $seq $secs
  }
}

set ENTER "\r"
set ESC "\033"
set UP "\033\[A"
set DOWN "\033\[B"
set BACKSPACE "\177"

# The typed line is what a reader would run; the spawned command adds the
# destination and template source the recording harness needs.
send_user "$ "
sleep 1
foreach char [split "php vortex.phar" ""] {
  send_user -- $char
  sleep 0.06
}
sleep 0.5
send_user "\n"

spawn -noecho php vortex.phar install --destination=star_wars --uri=$cli_uri

expect {
  "Press any key" { }
  timeout {
    puts "Timeout waiting for the welcome screen"
    exit 1
  }
  eof {
    puts "Install ended before the welcome screen"
    exit 1
  }
}

expect_background -re ".+"
sleep 2.5

# Dismiss the banner and let the panel overview settle.
press $ENTER 3

# General information: replace the detected site name and watch the machine
# name, organization and domain re-derive from it.
press $ENTER 2
press $ENTER 1
repeat $BACKSPACE 9 0.06
sleep 0.4
send -s -- "Star Wars"
sleep 0.8
press $ENTER 2.5
press $ESC 1.5

# Hosting: switching the provider reveals the fields that only it needs.
repeat $DOWN 4 0.4
press $ENTER 1.5
press $ENTER 1.5
press $UP 0.7
press $UP 0.7
press $ENTER 2.5
press $ESC 1.5

# Leaving a panel returns to the top of the overview, so the walk to the
# closing actions always starts from the first entry.
repeat $DOWN 12 0.3
press $ENTER 2

# The install writes its own progress from here, so the drain goes back to the
# foreground: a background reader would swallow the closing question along with
# it and leave the recording waiting out the timeout on an answer nobody gave.
catch { expect_background }

expect {
  "Run the site build now?" {
    sleep 1.5
    # Declined, so the demo ends on the summary rather than on a build that
    # takes minutes and shows nothing about the questions.
    send -- "n"
  }
  timeout { }
  eof { }
}

catch { expect eof }
sleep 2
EXPECT;

  return str_replace(
    ['{{URI}}', '{{COLS}}', '{{ROWS}}'],
    [tcl_quote($uri), (string) $cols, (string) $rows],
    $body,
  );
}

/**
 * Make a value safe to sit inside a Tcl double-quoted string.
 *
 * Tcl substitutes inside double quotes, so a path holding a bracket, a dollar
 * or a backslash would either fail to parse or run as a command.
 */
function tcl_quote(string $value): string {
  return str_replace(['\\', '"', '$', '[', ']'], ['\\\\', '\\"', '\\$', '\\[', '\\]'], $value);
}

function render_video(VideoRecorder $recorder, string $name, string $workspace, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];
  $cast = $docs_static_dir . "/$name.json";

  $recorder->postprocessCast($cast, $workspace);

  if ((float) $cfg['speed'] !== 1.0) {
    $recorder->applyTimeScale($cast, 1.0 / (float) $cfg['speed']);
  }

  $recorder->renderSvg($cast, $docs_static_dir . "/$name.svg");
  $recorder->renderPng($cast, $docs_static_dir . "/$name.png", $cfg['poster_ms'] === NULL ? NULL : (int) $cfg['poster_ms']);
}

function record_install(VideoRecorder $recorder, string $workspace, string $project_root, string $docs_static_dir): void {
  $cfg = VIDEOS['cli-install'];

  $recorder->info("===== Recording 'cli-install' =====");

  $expect_script = "$workspace/cli-install.exp";
  if (file_put_contents($expect_script, build_install_expect_script($project_root, (int) $cfg['cols'], (int) $cfg['rows'])) === FALSE) {
    throw new RuntimeException("Failed to write expect script: $expect_script");
  }
  if (!chmod($expect_script, 0o755)) {
    throw new RuntimeException("Failed to chmod expect script: $expect_script");
  }

  $recorder->recordSession(
    cwd: $workspace,
    cast_path: $docs_static_dir . '/cli-install.json',
    command: $expect_script,
    title: 'Vortex CLI install demo',
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  // The keystrokes are aimed by position, and the script cannot tell a panel it
  // misread from one it read correctly, so the project it was supposed to
  // create is what says the recording is worth keeping.
  if (!is_dir("$workspace/star_wars")) {
    throw new RuntimeException("Recorded install did not create a project at $workspace/star_wars");
  }

  render_video($recorder, 'cli-install', $workspace, $docs_static_dir);
}

function record_command_video(VideoRecorder $recorder, string $name, string $project_dir, string $workspace, string $type_and_run, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];

  $recorder->info("===== Recording '$name' =====");

  $cmd = (string) $cfg['command'];
  $recorded_cmd = $cfg['typer'] === TRUE
    ? 'php ' . escapeshellarg($type_and_run) . ' ' . escapeshellarg($cmd)
    : $cmd;

  $env = [
    'AHOY_CONFIRM_RESPONSE' => '1',
    'AHOY_CONFIRM_WAIT_SKIP' => '1',
    'COMPOSE_PROJECT_NAME' => COMPOSE_PROJECT,
  ];

  $recorder->recordSession(
    cwd: $project_dir,
    cast_path: $docs_static_dir . "/$name.json",
    command: $recorded_cmd,
    title: "Vortex $cmd Demo",
    env: $env,
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  render_video($recorder, $name, $workspace, $docs_static_dir);
}

function main(array $argv): int {
  $project_root = realpath(__DIR__ . '/../../..');
  $docs_static_dir = realpath(__DIR__ . '/../static/img');
  $renderer = __DIR__ . '/svg-term-render.js';
  $type_and_run = __DIR__ . '/type-and-run.php';

  if ($project_root === FALSE || $docs_static_dir === FALSE) {
    fwrite(STDERR, "Failed to resolve project paths\n");
    return 1;
  }

  $args = array_slice($argv, 1);
  if (in_array('-h', $args, TRUE) || in_array('--help', $args, TRUE)) {
    usage();
    return 0;
  }

  $keep = in_array('--keep', $args, TRUE);
  $args = array_values(array_filter($args, fn($a): bool => $a !== '--keep'));

  $expanded = [];
  foreach ($args as $arg) {
    foreach (preg_split('/[,\s]+/', (string) $arg) as $name) {
      if ($name !== '') {
        $expanded[] = $name;
      }
    }
  }
  $requested = $expanded !== [] ? $expanded : array_keys(VIDEOS);

  foreach ($requested as $name) {
    if (!isset(VIDEOS[$name])) {
      fwrite(STDERR, "Unknown video: $name\n");
      fwrite(STDERR, 'Allowed: ' . implode(', ', array_keys(VIDEOS)) . "\n");
      return 1;
    }
  }

  $recorder = new VideoRecorder($project_root, $docs_static_dir, $renderer);
  $recorder->info('Vortex video orchestrator (PHP)');
  $recorder->note('Requested: ' . implode(', ', $requested));
  $recorder->note('Mode: ' . ($keep ? 'reuse workspace (--keep)' : 'wipe + bootstrap'));

  $needs_built_project = array_intersect($requested, ['build', 'provision', 'lint', 'test', 'test-bdd']) !== [];

  $extra_deps = ['expect'];
  if ($needs_built_project) {
    $extra_deps[] = 'ahoy';
    $extra_deps[] = 'docker';
  }
  $recorder->checkDependencies($extra_deps);

  $workspace = $project_root . '/' . WORKSPACE_REL;
  $project_dir = "$workspace/star_wars";
  $compose_project = COMPOSE_PROJECT;

  if ($keep) {
    if (!is_dir($project_dir)) {
      $recorder->fail("--keep requires an existing workspace at $project_dir");
      $recorder->note('Run without --keep first to bootstrap.');
      return 1;
    }
    if ($needs_built_project && !$recorder->isDockerStackRunning($compose_project)) {
      $recorder->fail("Docker stack '$compose_project' is not running");
      $recorder->note('Rerun without --keep to bootstrap fresh.');
      return 1;
    }
    if (in_array('cli-install', $requested, TRUE)) {
      $recorder->fail("Cannot record 'cli-install' with --keep (it would wipe the kept project)");
      $recorder->note('Run without --keep to re-record cli-install.');
      return 1;
    }
    $recorder->info("Reusing workspace: $workspace");
  }
  else {
    if (is_dir($workspace)) {
      $recorder->teardownPersistentWorkspace($workspace, $compose_project);
    }
    if (!mkdir($workspace, 0o755, TRUE) && !is_dir($workspace)) {
      $recorder->fail("Failed to create workspace: $workspace");
      return 1;
    }
    $recorder->info("Created fresh workspace: $workspace");

    $recorder->buildCliPhar("$workspace/vortex.phar");

    if (in_array('cli-install', $requested, TRUE)) {
      record_install($recorder, $workspace, $project_root, $docs_static_dir);
    }
    else {
      $recorder->runInstall($workspace, $project_root);
    }

    if (!is_dir($project_dir)) {
      $recorder->fail("Installation did not create project at $project_dir");
      return 1;
    }

    if ($needs_built_project && !in_array('build', $requested, TRUE)) {
      $recorder->runAhoyBuild($project_dir, $compose_project);
    }
  }

  $order = ['build', 'provision', 'lint', 'test', 'test-bdd'];
  foreach ($order as $name) {
    if (!in_array($name, $requested, TRUE)) {
      continue;
    }
    record_command_video($recorder, $name, $project_dir, $workspace, $type_and_run, $docs_static_dir);
  }

  $recorder->pass('Videos updated: ' . implode(', ', $requested));
  if (!$keep) {
    $recorder->note("Workspace and Docker stack preserved at $workspace");
    $recorder->note("Re-run with --keep to re-record without rebuilding, or without flags to wipe and bootstrap fresh.");
  }

  return 0;
}

exit(main($argv));
