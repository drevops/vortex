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
 * - command:   the command executed inside the recording.
 * - speed:     playback speed multiplier. 1.0 = recorded speed, 2.0 = 2x faster.
 * - cols/rows: terminal dimensions passed to asciinema and used for renders.
 * - poster_ms: cast timestamp (ms) at which the PNG poster frame is taken.
 *              NULL means use the last frame of the cast.
 * - typer:     wrap the command with the simulated-typing intro from
 *              type-and-run.php.
 */
const VIDEOS = [
  // The poster aims at the panel overview, which is on screen for three
  // seconds once the banner is dismissed. Its own driver types the command,
  // so the shared typer stays off.
  'install' => [
    'command' => 'php vortex.phar',
    'speed' => 1.0,
    'cols' => 100,
    'rows' => 36,
    'poster_ms' => 7000,
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
    'poster_ms' => NULL,
    'typer' => TRUE,
  ],
  'lint' => [
    'command' => 'ahoy lint',
    'speed' => 2.0,
    'cols' => 140,
    'rows' => 42,
    'poster_ms' => NULL,
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
  fwrite(STDERR, "+ rm) and bootstraps from scratch (build vortex.phar, install a project,\n");
  fwrite(STDERR, "ahoy build).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "--keep reuses the existing workspace and skips the bootstrap. Requires the\n");
  fwrite(STDERR, "Docker stack to be running (the script probes and exits cleanly otherwise).\n");
  fwrite(STDERR, "\n");
  fwrite(STDERR, "Video names may be space or comma separated (lint test = lint,test).\n");
}

function render_video(VideoRecorder $recorder, string $name, string $workspace, string $docs_static_dir): void {
  $cfg = VIDEOS[$name];
  $cast = $docs_static_dir . "/$name.json";

  // For command videos using type-and-run.php the first event is the typed
  // prompt, which is the part worth keeping.
  $recorder->postprocessCast($cast, $workspace, strip_first_event: FALSE);

  if ((float) $cfg['speed'] !== 1.0) {
    $recorder->applyTimeScale($cast, 1.0 / (float) $cfg['speed']);
  }

  $recorder->renderSvg($cast, $docs_static_dir . "/$name.svg");
  $recorder->renderPng($cast, $docs_static_dir . "/$name.png", $cfg['poster_ms'] === NULL ? NULL : (int) $cfg['poster_ms']);
}

function record_install_video(VideoRecorder $recorder, string $workspace, string $uri, string $driver, string $docs_static_dir): void {
  $cfg = VIDEOS['install'];

  $recorder->info("===== Recording 'install' =====");

  $recorder->recordSession(
    cwd: $workspace,
    cast_path: $docs_static_dir . '/install.json',
    command: sprintf('expect %s vortex.phar %s star_wars %d %d', escapeshellarg($driver), escapeshellarg($uri), $cfg['cols'], $cfg['rows']),
    title: 'Vortex install Demo',
    cols: (int) $cfg['cols'],
    rows: (int) $cfg['rows'],
  );

  render_video($recorder, 'install', $workspace, $docs_static_dir);
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
  $install_driver = __DIR__ . '/install-demo.exp';

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
    if (in_array('install', $requested, TRUE)) {
      $recorder->fail("Cannot record 'install' with --keep (it would wipe the kept project)");
      $recorder->note("Run without --keep to re-record 'install'.");
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

    // The recorded run is a real install, so it doubles as the bootstrap.
    if (in_array('install', $requested, TRUE)) {
      record_install_video($recorder, $workspace, $project_root, $install_driver, $docs_static_dir);
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
