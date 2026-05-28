#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/VideoRecorder.php';

/**
 * Update one or more documentation videos.
 *
 * Bootstraps a project ONCE in a temp directory and records each requested
 * video against it. The order is fixed:
 *   installer -> build -> provision -> lint -> test -> test-bdd
 *
 * Phase 1 (install) always happens. When `installer` is in the requested set
 * the install is recorded (interactive flow via expect); otherwise the
 * installer runs silently in --no-interaction mode.
 *
 * Phase 2 (build) only happens if anything beyond installer is requested.
 * When `build` is in the requested set the build is recorded; otherwise
 * `ahoy build` runs silently so the remaining recorded commands have a
 * built project to work against.
 *
 * Phase 3 records each remaining requested command (provision, lint, test,
 * test-bdd) in the order listed.
 *
 * Output artifacts: .vortex/docs/static/img/<name>.{json,svg,png,gif}
 *
 * Usage:
 *   php update-videos.php                        # all six videos
 *   php update-videos.php installer              # only installer (no Docker)
 *   php update-videos.php lint provision         # silent install + silent build,
 *                                                # then record lint and provision
 *   php update-videos.php build lint             # silent install, recorded build,
 *                                                # then record lint
 */

const PROMPT_DELAY = 1;

const ALL_VIDEOS = ['installer', 'build', 'provision', 'lint', 'test', 'test-bdd'];

const COMMAND_VIDEOS = [
  'build' => 'ahoy build',
  'provision' => 'ahoy provision',
  'lint' => 'ahoy lint',
  'test' => 'ahoy test',
  'test-bdd' => 'ahoy test-bdd',
];

function usage(): void {
  fwrite(STDERR, "Usage: php update-videos.php [video-name ...]\n");
  fwrite(STDERR, '  Video names: ' . implode(', ', ALL_VIDEOS) . "\n");
  fwrite(STDERR, "  Default: all videos\n");
}

function build_installer_expect_script(int|float $prompt_delay, string $uri): string {
  $body = <<<'EXPECT'
#!/usr/bin/env expect

set timeout 60
log_user 1

# Configuration from PHP wrapper
set prompt_delay {{PROMPT_DELAY}}
set installer_uri {{URI}}

# Function to safely send input if process is still running.
proc safe_send {s} {
    if {[exp_pid] > 0} {
        send -- $s
    } else {
        puts "child process already ended; skipping send <$s>"
    }
}

# Function to wait for a quiet period (no output) for specified seconds.
proc wait_for_quiet {{secs 1}} {
    set old $::timeout
    set ::timeout $secs
    expect {
        -re {.+} { exp_continue }
        timeout { }
    }
    set ::timeout $old
}

# Function to clear existing text with backspaces.
proc clear_field {} {
    for {set i 0} {$i < 50} {incr i} {
        safe_send "\b"
    }
    after 150
}

# Function to simulate typing.
proc type_text {text} {
    wait_for_quiet 0.1
    set send_human {.1 .3 1 .05 2 .1 .2 0 .4 0 .6 0 .8 0 1}
    send -h $text
}

# Function to wait and press enter
proc wait_and_enter {} {
    global prompt_delay
    wait_for_quiet 0.25
    sleep $prompt_delay
    safe_send "\r"
}

#######################
# Start the installer #
#######################
set env(VORTEX_INSTALLER_PROMPT_BUILD_NOW) 0
spawn php installer.php --destination=star_wars --uri=$installer_uri

# Wait for the welcome screen and let it proceed
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

# Handle all remaining prompts by pressing enter until installation completes
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

# Handle the final "Run the site build now?" prompt separately
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
  $cast = "$workspace/$name.json";
  $recorder->postprocessCast($cast, $workspace);
  $recorder->renderSvg($cast, "$workspace/$name.svg");
  $recorder->renderPng($cast, "$workspace/$name.png");
  $recorder->renderGif($cast, "$workspace/$name.gif");
  install_video_artifacts($recorder, $workspace, $name, $docs_static_dir);
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

  $requested = $args !== [] ? $args : ALL_VIDEOS;
  foreach ($requested as $name) {
    if (!in_array($name, ALL_VIDEOS, TRUE)) {
      fwrite(STDERR, "Unknown video: $name\n");
      fwrite(STDERR, 'Allowed: ' . implode(', ', ALL_VIDEOS) . "\n");
      return 1;
    }
  }

  $recorder = new VideoRecorder($project_root, $docs_static_dir, $renderer);
  $recorder->info('Vortex video orchestrator (PHP)');
  $recorder->note('Requested: ' . implode(', ', $requested));

  $needs_built_project = array_intersect($requested, ['build', 'provision', 'lint', 'test', 'test-bdd']) !== [];

  $extra_deps = ['expect'];
  if ($needs_built_project) {
    $extra_deps[] = 'ahoy';
    $extra_deps[] = 'docker';
  }
  $recorder->checkDependencies($extra_deps);

  $workspace = $recorder->workspaceInit('videos');
  $compose_project = 'vortex_videos_' . bin2hex(random_bytes(3));

  $recorder->buildInstallerPhar("$workspace/installer.php");

  // Phase 1: install (recorded or silent)
  if (in_array('installer', $requested, TRUE)) {
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
    );
    render_and_install($recorder, $workspace, 'installer', $docs_static_dir);

    $project_dir = "$workspace/star_wars";
    if (!is_dir($project_dir)) {
      $recorder->fail("Recorded installer did not create project at $project_dir");
      return 1;
    }
  }
  else {
    $project_dir = $recorder->runInstaller($workspace, $project_root);
  }

  $record_env = [
    'AHOY_CONFIRM_RESPONSE' => 'y',
    'AHOY_CONFIRM_WAIT_SKIP' => '1',
  ];
  if ($compose_project !== NULL) {
    $record_env['COMPOSE_PROJECT_NAME'] = $compose_project;
  }

  // Phase 2: build (recorded, silent, or skipped if nothing needs it)
  if ($needs_built_project) {
    if (in_array('build', $requested, TRUE)) {
      $recorder->info("===== Recording 'build' =====");
      $recorder->registerDockerCleanup($project_dir, $compose_project);
      $recorder->recordSession(
        cwd: $project_dir,
        cast_path: "$workspace/build.json",
        command: 'ahoy build',
        title: 'Vortex ahoy build Demo',
        env: $record_env,
        rows: VideoRecorder::TERMINAL_HEIGHT_TALL,
      );
      render_and_install($recorder, $workspace, 'build', $docs_static_dir);
    }
    else {
      $recorder->runAhoyBuild($project_dir, $compose_project);
    }
  }

  // Phase 3: record remaining commands in fixed order
  foreach (['provision', 'lint', 'test', 'test-bdd'] as $name) {
    if (!in_array($name, $requested, TRUE)) {
      continue;
    }
    $recorder->info("===== Recording '$name' =====");
    $command = COMMAND_VIDEOS[$name];
    $recorder->recordSession(
      cwd: $project_dir,
      cast_path: "$workspace/$name.json",
      command: $command,
      title: "Vortex $command Demo",
      env: $record_env,
      rows: VideoRecorder::TERMINAL_HEIGHT_TALL,
    );
    render_and_install($recorder, $workspace, $name, $docs_static_dir);
  }

  $recorder->pass('Videos updated: ' . implode(', ', $requested));

  return 0;
}

exit(main($argv));
