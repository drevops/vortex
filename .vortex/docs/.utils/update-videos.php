#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/VideoRecorder.php';

/**
 * Update one or more documentation videos.
 *
 * Bootstraps a project ONCE (install + optional silent build) and then
 * records each requested ahoy command into its own asciicast / SVG / PNG
 * (and GIF when `agg` is available). Output artifacts land under
 * `.vortex/docs/static/img/<name>.{json,svg,png,gif}`.
 *
 * Usage:
 *   php update-videos.php                                # all five videos
 *   php update-videos.php lint provision                 # subset
 *
 * Env:
 *   VORTEX_VIDEO_PROJECT_DIR    Reuse an already-built consumer project at
 *                               this path. Bootstrap is skipped entirely.
 *                               The project must have its Docker stack up
 *                               (`ahoy up`). Recording `build` is rejected
 *                               in this mode.
 *   VORTEX_VIDEO_KEEP_WORKSPACE Keep the temp workspace and the Docker stack
 *                               after the run completes (for debugging).
 *
 * Bootstrap-vs-record-build interaction:
 *   - If `build` is in the requested set AND no existing project is supplied,
 *     bootstrap stops after the installer; recording `ahoy build` performs
 *     the build for the first time inside the cast.
 *   - If `build` is NOT in the requested set AND no existing project is
 *     supplied, bootstrap also runs `ahoy build` silently so the recorded
 *     commands (lint/provision/test/test-bdd) have a built project to work
 *     against.
 */

const VIDEO_COMMANDS = [
  'build' => 'ahoy build',
  'provision' => 'ahoy provision',
  'lint' => 'ahoy lint',
  'test' => 'ahoy test',
  'test-bdd' => 'ahoy test-bdd',
];

function usage(): void {
  fwrite(STDERR, "Usage: php update-videos.php [video-name ...]\n");
  fwrite(STDERR, '  Video names: ' . implode(', ', array_keys(VIDEO_COMMANDS)) . "\n");
  fwrite(STDERR, "  Default: all videos\n");
}

function main(array $argv): int {
  $project_root = realpath(__DIR__ . '/../../..');
  $docs_static_dir = realpath(__DIR__ . '/../static/img');
  $renderer = __DIR__ . '/svg-term-render.js';

  if ($project_root === FALSE || $docs_static_dir === FALSE) {
    fwrite(STDERR, "Failed to resolve project paths\n");
    return 1;
  }

  $args = array_slice($argv, 1);
  if (in_array('-h', $args, TRUE) || in_array('--help', $args, TRUE)) {
    usage();
    return 0;
  }

  $recorder = new VideoRecorder($project_root, $docs_static_dir, $renderer);
  $recorder->info('Vortex video orchestrator (PHP)');

  $requested = $args !== [] ? $args : array_keys(VIDEO_COMMANDS);
  foreach ($requested as $name) {
    if (!isset(VIDEO_COMMANDS[$name])) {
      $recorder->fail("Unknown video: $name");
      $recorder->note('Allowed: ' . implode(', ', array_keys(VIDEO_COMMANDS)));
      return 1;
    }
  }

  $existing_project = getenv('VORTEX_VIDEO_PROJECT_DIR') ?: NULL;

  $extra_deps = $existing_project !== NULL ? ['ahoy'] : ['ahoy', 'docker'];
  $recorder->checkDependencies($extra_deps);

  $workspace = $recorder->workspaceInit('videos');
  $compose_project = NULL;
  $project_dir = NULL;

  if ($existing_project !== NULL) {
    $project_dir = realpath($existing_project) ?: $existing_project;
    if (!is_dir($project_dir)) {
      $recorder->fail("VORTEX_VIDEO_PROJECT_DIR is not a directory: $project_dir");
      return 1;
    }
    if (in_array('build', $requested, TRUE)) {
      $recorder->fail('Cannot record `build` when VORTEX_VIDEO_PROJECT_DIR is set');
      $recorder->note('Run `build` separately without the env var to start from a fresh install.');
      return 1;
    }
    $recorder->info("Reusing existing built project: $project_dir");
    $recorder->note('Bootstrap skipped. The project\'s Docker stack must already be up.');
  }
  else {
    $compose_project = 'vortex_videos_' . bin2hex(random_bytes(3));
    $recorder->buildInstallerPhar("$workspace/installer.php");

    $will_record_build = in_array('build', $requested, TRUE);
    $project_dir = $recorder->bootstrapProject(
      $workspace,
      with_build: !$will_record_build,
      compose_project_name: $compose_project,
      uri: $project_root,
    );

    if ($will_record_build) {
      $recorder->registerDockerCleanup($project_dir, $compose_project);
    }
  }

  $produced = [];
  foreach ($requested as $name) {
    $command = VIDEO_COMMANDS[$name];

    $env = [];
    if ($compose_project !== NULL) {
      $env['COMPOSE_PROJECT_NAME'] = $compose_project;
    }
    if ($name === 'build') {
      $env['AHOY_CONFIRM_RESPONSE'] = 'y';
      $env['AHOY_CONFIRM_WAIT_SKIP'] = '1';
    }

    $recorder->info("===== Recording '$name' =====");

    $cast = "$workspace/$name.json";
    $svg = "$workspace/$name.svg";
    $png = "$workspace/$name.png";
    $gif = "$workspace/$name.gif";

    $recorder->recordSession(
      cwd: $project_dir,
      cast_path: $cast,
      command: $command,
      title: "Vortex $command Demo",
      env: $env,
    );

    $recorder->postprocessCast($cast, $workspace);

    $recorder->renderSvg($cast, $svg);
    $recorder->renderPng($cast, $png);
    $recorder->renderGif($cast, $gif);

    foreach (['json', 'svg', 'png', 'gif'] as $ext) {
      $src = "$workspace/$name.$ext";
      if (!is_file($src)) {
        continue;
      }
      $dst = $docs_static_dir . "/$name.$ext";
      if (!copy($src, $dst)) {
        $recorder->fail("Failed to install $src -> $dst");
        return 1;
      }
      $recorder->note("Installed: $dst");
    }

    $produced[] = $name;
  }

  $recorder->pass('Videos updated: ' . implode(', ', $produced));

  return 0;
}

exit(main($argv));
