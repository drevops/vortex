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

const PROMPT_DELAY = 1;

const WORKSPACE_REL = '.artifacts/tmp/videos-workspace';

const COMPOSE_PROJECT = 'vortex_videos';

/**
 * Per-video configuration.
 *
 * - command:   the command executed inside the recording. NULL means the
 *              installer expect script is used instead.
 * - speed:     playback speed multiplier. 1.0 = recorded speed, 2.0 = 2x faster.
 * - cols/rows: terminal dimensions passed to asciinema and used for renders.
 * - poster_ms: cast timestamp (ms) at which the PNG poster frame is taken.
 * - typer:     wrap the command with the simulated-typing intro from
 *              type-and-run.php. Installer is FALSE because the expect
 *              script handles its own prompt-driven flow.
 */
const VIDEOS = [
  'installer' => [
    'command' => NULL,
    'speed' => 1.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => FALSE,
  ],
  'build' => [
    'command' => 'ahoy build',
    'speed' => 1.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'provision' => [
    'command' => 'ahoy provision',
    'speed' => 1.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'lint' => [
    'command' => 'ahoy lint',
    'speed' => 2.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'test' => [
    'command' => 'ahoy test',
    'speed' => 2.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => 2000,
    'typer' => TRUE,
  ],
  'test-bdd' => [
    'command' => 'ahoy test-bdd',
    'speed' => 1.0,
    'cols' => 140,
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
  fwrite(STDERR, "+ rm) and bootstraps from scratch (install installer.phar, run installer,\n");
  fwrite(STDERR, "ahoy build).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "--keep reuses the existing workspace and skips the bootstrap. Requires the\n");
  fwrite(STDERR, "Docker stack to be running (the script probes and exits cleanly otherwise).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Video names may be space or comma separated (lint test = lint,test).\n");
}

function build_installer_expect_script(int|float $prompt_delay, string $uri): string {
  $body = <<<'EXPECT'
#!/usr/bin/env expect

set timeout 60
log_user 1

set prompt_delay {{PROMPT_DELAY}}
set installer_uri {{URI}}

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

proc type_text {text} {
    wait_for_quiet 0.1
    set send_human {.1 .3 1 .05 2 .1 .2 0 .4 0 .6 0 .8 0 1}
    send -h $text
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
    ['{{PROMPT_DELAY}}', '{{URI}}'],
    [(string) $prompt_delay, $uri],
    $body,
  );
}

function install_video_artifacts(VideoRecorder $recorder, string $workspace, string $name, string $docs_static_dir): void {
  foreach (['json', 'svg', 'png', 'gif'] as $ext) {
    $src = "$workspace/$name.$ext";
    if (!is_file($src)) {
      continue;
    }
    $dst = $docs_static_dir . "/$name.$ext";
    if (!copy($src, $dst)) {
      throw new RuntimeException("Failed to copy $src -> $dst");
    }
    $recorder->note("Installed: $dst");
  }
}

function render_and_install(VideoRecorder $recorder, string $workspace, string $name, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];
  $cast = "$workspace/$name.json";

  $recorder->postprocessCast($cast, $workspace);

  if ((float) $cfg['speed'] !== 1.0) {
    $recorder->applyTimeScale($cast, 1.0 / (float) $cfg['speed']);
  }

  $recorder->renderSvg($cast, "$workspace/$name.svg");
  $recorder->renderPng($cast, "$workspace/$name.png", (int) $cfg['poster_ms']);
  $recorder->renderGif($cast, "$workspace/$name.gif");

  install_video_artifacts($recorder, $workspace, $name, $docs_static_dir);
}

function record_installer(VideoRecorder $recorder, string $workspace, string $project_root, string $docs_static_dir): void {
  $cfg = VIDEOS['installer'];

  $recorder->info("===== Recording 'installer' =====");

  $expect_script = "$workspace/installer.exp";
  if (file_put_contents($expect_script, build_installer_expect_script(PROMPT_DELAY, $project_root)) === FALSE) {
    throw new RuntimeException("Failed to write expect script: $expect_script");
  }
  chmod($expect_script, 0o755);

  $recorder->recordSession(
    cwd: $workspace,
    cast_path: "$workspace/installer.json",
    command: $expect_script,
    title: 'Vortex Installer Demo',
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  render_and_install($recorder, $workspace, 'installer', $docs_static_dir);
}

function record_command_video(VideoRecorder $recorder, string $name, string $project_dir, string $workspace, string $type_and_run, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];

  $recorder->info("===== Recording '$name' =====");

  $cmd = (string) $cfg['command'];
  $recorded_cmd = $cfg['typer'] === TRUE ? "php $type_and_run $cmd" : $cmd;

  $env = [
    'AHOY_CONFIRM_RESPONSE' => '1',
    'AHOY_CONFIRM_WAIT_SKIP' => '1',
    'COMPOSE_PROJECT_NAME' => COMPOSE_PROJECT,
  ];

  $recorder->recordSession(
    cwd: $project_dir,
    cast_path: "$workspace/$name.json",
    command: $recorded_cmd,
    title: "Vortex $cmd Demo",
    env: $env,
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  render_and_install($recorder, $workspace, $name, $docs_static_dir);
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
