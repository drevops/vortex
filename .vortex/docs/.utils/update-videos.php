#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/CastNormalizer.php';
require_once __DIR__ . '/VideoRecorder.php';

use DrevOps\Vortex\Docs\CastNormalizer;

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
 * Recording the same session twice produces the same bytes: every artefact is
 * a function of what the session output, never of how the machine was doing
 * that day. `--verify` re-runs the deterministic half of the pipeline over the
 * committed artefacts and reports anything that no longer matches.
 *
 * Usage:
 *   php update-videos.php                          # wipe + bootstrap + record all
 *   php update-videos.php lint provision           # wipe + bootstrap + record lint, provision
 *   php update-videos.php lint,test                # commas also accepted
 *   php update-videos.php --keep lint              # reuse workspace, record lint only
 *   php update-videos.php --verify                 # re-render the committed artefacts and compare
 */

const PROMPT_DELAY = 1;

const TYPE_DELAY = 0.02;

const VERIFY_REL = '.artifacts/tmp/videos-verify';

const WORKSPACE_REL = '.artifacts/tmp/videos-workspace';

const COMPOSE_PROJECT = 'vortex_videos';

/**
 * Per-video configuration.
 *
 * - command:     the command executed inside the recording. NULL means the
 *                installer expect script is used instead.
 * - frames:      how the recording is cut into frames. 'writes' follows the
 *                writes a command made; 'repaint' follows the repaints a
 *                full-screen prompt made.
 * - frame_delay: seconds a frame of command output plays for. The typed
 *                command line plays at CastNormalizer::TYPE_DELAY regardless,
 *                so every demo types at the same rate.
 * - cols/rows:   terminal dimensions passed to asciinema and used for renders.
 * - poster:      text the PNG poster frame draws. NULL means use the last
 *                frame of the cast.
 * - typer:       wrap the command with the simulated-typing intro from
 *                type-and-run.php. Installer is FALSE because the expect
 *                script handles its own prompt-driven flow.
 */
const VIDEOS = [
  'installer' => [
    'command' => NULL,
    'frames' => CastNormalizer::MODE_REPAINT,
    'frame_delay' => 0.1,
    'cols' => 80,
    'rows' => 42,
    'poster' => 'Repository and reference validated',
    'typer' => FALSE,
  ],
  'build' => [
    'command' => 'ahoy build',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.05,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'provision' => [
    'command' => 'ahoy provision',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.15,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'lint' => [
    'command' => 'ahoy lint',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.4,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'test' => [
    'command' => 'ahoy test',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.15,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'test-bdd' => [
    'command' => 'ahoy test-bdd',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.1,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'info' => [
    'command' => 'ahoy info',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.5,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'doctor' => [
    'command' => 'ahoy doctor',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.5,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
  'doctor-info' => [
    'command' => 'ahoy doctor info',
    'frames' => CastNormalizer::MODE_WRITES,
    'frame_delay' => 0.3,
    'cols' => 80,
    'rows' => 42,
    'poster' => NULL,
    'typer' => TRUE,
  ],
];

function usage(): void {
  fwrite(STDERR, "Usage: php update-videos.php [--keep|--verify] [video-name ...]\n");
  fwrite(STDERR, '  Video names: ' . implode(', ', array_keys(VIDEOS)) . "\n");
  fwrite(STDERR, "  Default: all videos\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "--verify records nothing. It re-runs normalization and rendering over the\n");
  fwrite(STDERR, "committed artefacts and reports every one that no longer matches.\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Default mode wipes '.artifacts/tmp/videos-workspace/' (via 'ahoy reset'\n");
  fwrite(STDERR, "+ rm) and bootstraps from scratch (install installer.phar, run installer,\n");
  fwrite(STDERR, "ahoy build).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "--keep reuses the existing workspace and skips the bootstrap. Requires the\n");
  fwrite(STDERR, "Docker stack to be running (the script probes and exits cleanly otherwise).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Video names may be space or comma separated (lint test = lint,test).\n");
}

function build_installer_expect_script(int|float $prompt_delay, int|float $type_delay, string $uri): string {
  $body = <<<'EXPECT'
#!/usr/bin/env expect

set timeout 60
log_user 1

set prompt_delay {{PROMPT_DELAY}}
set type_delay {{TYPE_DELAY}}
set installer_uri "{{URI}}"

proc safe_send {s} {
    if {[exp_pid] > 0} {
        send -- $s
    } else {
        puts "child process already ended; skipping send <$s>"
    }
}

proc wait_for_quiet {{secs 1}} {
    set old $::timeout
    set ::timeout $secs
    expect {
        -re {.+} { exp_continue }
        timeout { }
    }
    set ::timeout $old
}

proc clear_field {} {
    for {set i 0} {$i < 50} {incr i} {
        safe_send "\b"
    }
    after 150
}

# Type at a fixed rate. 'send -h' varies its inter-character delays at random,
# which moves where the terminal splits its writes and makes two recordings of
# the same session differ.
proc type_text {text} {
    global type_delay
    wait_for_quiet 0.1
    foreach ch [split $text ""] {
        safe_send $ch
        sleep $type_delay
    }
}

proc wait_and_enter {} {
    global prompt_delay
    wait_for_quiet 0.25
    sleep $prompt_delay
    safe_send "\r"
}

set env(VORTEX_INSTALLER_PROMPT_BUILD_NOW) 0
spawn php installer.php --destination=star_wars --uri=$installer_uri

expect {
  "Press any key to continue" {
    after 3000
    safe_send "\r"
  }
  timeout {
    puts "Timeout waiting for welcome screen or first prompt"
    exit 1
  }
}

expect {
  "Site name" {
    clear_field
    type_text "Star Wars"
    wait_and_enter
  }
  timeout {
    puts "Timeout waiting for welcome screen or first prompt"
    exit 1
  }
}

expect {
  "Site machine name" {
    wait_and_enter
  }
}

expect {
  "Organization name" {
    clear_field
    type_text "Rebellion"
    wait_and_enter
  }
}

while {1} {
  expect {
    "Proceed with installing Vortex?" {
      after 2000
      safe_send "\r"
    }
    "Vortex will be installed into your project" {
      after 2000
      safe_send "\r"
    }
    "Finished installing Vortex" {
      break
    }
    "─┘" {
      wait_and_enter
    }
    timeout {
      puts "Timeout during installation"
      break
    }
    eof {
      puts "End of file reached"
      break
    }
  }
}

expect {
  "Run the site build now?" {
    after 2000
    safe_send "\r"
  }
  timeout {
    puts "Timeout waiting for build prompt"
  }
  eof {
    puts "End of file before build prompt"
  }
}

expect eof
EXPECT;

  return str_replace(
    ['{{PROMPT_DELAY}}', '{{TYPE_DELAY}}', '{{URI}}'],
    [(string) $prompt_delay, (string) $type_delay, $uri],
    $body,
  );
}

function build_normalizer(string $name, string $project_root, string $workspace): CastNormalizer {
  $cfg = VIDEOS[$name];

  return new CastNormalizer(
    mode: (string) $cfg['frames'],
    frame_delay: (float) $cfg['frame_delay'],
    typed: $cfg['typer'] === TRUE,
    paths: [
      $workspace => '/home/user/demo',
      $project_root => '/home/user/vortex',
    ],
  );
}

function render_video(VideoRecorder $recorder, string $name, string $workspace, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];
  $cast = $docs_static_dir . "/$name.json";

  $recorder->normalizeCast($cast, build_normalizer($name, $recorder->project_root, $workspace));
  $recorder->renderSvg($cast, $docs_static_dir . "/$name.svg");
  $recorder->renderPng($cast, $docs_static_dir . "/$name.png", $cfg['poster'] === NULL ? NULL : (string) $cfg['poster']);
}

function files_match(string $left, string $right): bool {
  return is_file($left) && is_file($right) && filesize($left) === filesize($right) && md5_file($left) === md5_file($right);
}

/**
 * Re-render the committed artefacts and report every one that has drifted.
 *
 * Nothing is recorded: recording is the one part of the pipeline that needs a
 * built project and a running Docker stack. Everything after it is a function
 * of the committed cast, so this checks that each cast is already in canonical
 * form and that its SVG and PNG are the ones the current renderers produce
 * from it.
 */
function verify_videos(VideoRecorder $recorder, array $requested, string $project_root, string $docs_static_dir): int {
  $recorder->info('Verifying committed artefacts');

  $verify_dir = $project_root . '/' . VERIFY_REL;
  $recorder->rmrf($verify_dir);
  if (!mkdir($verify_dir, 0o755, TRUE) && !is_dir($verify_dir)) {
    $recorder->fail("Failed to create verification directory: $verify_dir");
    return 1;
  }

  $workspace = $project_root . '/' . WORKSPACE_REL;
  $drifted = [];

  foreach ($requested as $name) {
    $cast = $docs_static_dir . "/$name.json";
    if (!is_file($cast)) {
      $recorder->note("$name: no committed cast, skipped");
      continue;
    }

    $recorder->info("===== Verifying '$name' =====");

    $cfg = VIDEOS[$name];
    $committed = (string) file_get_contents($cast);
    $normalized = build_normalizer($name, $project_root, $workspace)->normalize($committed);

    $differences = $normalized === $committed ? [] : ['cast'];

    $candidate = "$verify_dir/$name.json";
    if (file_put_contents($candidate, $normalized) === FALSE) {
      throw new RuntimeException("Failed to write candidate cast: $candidate");
    }

    $recorder->renderSvg($candidate, "$verify_dir/$name.svg");
    if (!files_match("$verify_dir/$name.svg", $docs_static_dir . "/$name.svg")) {
      $differences[] = 'svg';
    }

    $recorder->renderPng($candidate, "$verify_dir/$name.png", $cfg['poster'] === NULL ? NULL : (string) $cfg['poster']);
    if (!files_match("$verify_dir/$name.png", $docs_static_dir . "/$name.png")) {
      $differences[] = 'png';
    }

    if ($differences === []) {
      $recorder->pass("$name: artefacts match");
      continue;
    }

    $drifted[$name] = $differences;
    $recorder->fail("$name: " . implode(', ', $differences) . ' differ');
  }

  if ($drifted === []) {
    $recorder->pass('All verified artefacts match');
    return 0;
  }

  $recorder->fail('Artefacts differ for: ' . implode(', ', array_keys($drifted)));
  $recorder->note('Re-run `ahoy update-videos <name>` and commit the result.');

  return 1;
}

function record_installer(VideoRecorder $recorder, string $workspace, string $project_root, string $docs_static_dir): void {
  $cfg = VIDEOS['installer'];

  $recorder->info("===== Recording 'installer' =====");

  $expect_script = "$workspace/installer.exp";
  if (file_put_contents($expect_script, build_installer_expect_script(PROMPT_DELAY, TYPE_DELAY, $project_root)) === FALSE) {
    throw new RuntimeException("Failed to write expect script: $expect_script");
  }
  if (!chmod($expect_script, 0o755)) {
    throw new RuntimeException("Failed to chmod expect script: $expect_script");
  }

  $recorder->recordSession(
    cwd: $workspace,
    cast_path: $docs_static_dir . '/installer.json',
    command: $expect_script,
    title: 'Vortex Installer Demo',
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  render_video($recorder, 'installer', $workspace, $docs_static_dir);
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
  $verify = in_array('--verify', $args, TRUE);
  $args = array_values(array_filter($args, fn($a): bool => !in_array($a, ['--keep', '--verify'], TRUE)));

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

  if ($verify) {
    return verify_videos($recorder, $requested, $project_root, $docs_static_dir);
  }

  $recorder->note('Mode: ' . ($keep ? 'reuse workspace (--keep)' : 'wipe + bootstrap'));

  $needs_built_project = array_intersect($requested, ['build', 'provision', 'lint', 'test', 'test-bdd', 'info', 'doctor', 'doctor-info']) !== [];

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
    if (in_array('installer', $requested, TRUE)) {
      $recorder->fail("Cannot record 'installer' with --keep (it would wipe the kept project)");
      $recorder->note('Run without --keep to re-record installer.');
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

    $recorder->buildInstallerPhar("$workspace/installer.php");

    if (in_array('installer', $requested, TRUE)) {
      record_installer($recorder, $workspace, $project_root, $docs_static_dir);
    }
    else {
      $recorder->runInstaller($workspace, $project_root);
    }

    if (!is_dir($project_dir)) {
      $recorder->fail("Installation did not create project at $project_dir");
      return 1;
    }

    if ($needs_built_project && !in_array('build', $requested, TRUE)) {
      $recorder->runAhoyBuild($project_dir, $compose_project);
    }
  }

  $order = ['build', 'info', 'doctor', 'doctor-info', 'provision', 'lint', 'test', 'test-bdd'];
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
