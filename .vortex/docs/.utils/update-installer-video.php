#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/VideoRecorder.php';

/**
 * Record the installer video shown in the documentation.
 *
 * Output artifacts: .vortex/docs/static/img/installer.{json,svg,png,gif}
 */

const PROMPT_DELAY = 1;

function build_installer_expect_script(int|float $prompt_delay): string {
  $body = <<<'EXPECT'
#!/usr/bin/env expect

set timeout 60
log_user 1

# Configuration from shell script
set prompt_delay {{PROMPT_DELAY}}

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
    # Save and bump timeout down to the "quiet" window
    set old $::timeout
    set ::timeout $secs
    expect {
        -re {.+} { exp_continue }  ;# keep draining while data arrives
        timeout { }                ;# no data for 'secs' seconds
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
spawn php installer.php --destination=star_wars

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
    # Already at first prompt, send project name
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
      # Installation completed, break out of loop
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
# Default is "No" via VORTEX_INSTALLER_PROMPT_BUILD_NOW=0 env var
expect {
  "Run the site build now?" {
    after 2000
    # Just press Enter to accept the default (No)
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

  return str_replace('{{PROMPT_DELAY}}', (string) $prompt_delay, $body);
}

function main(): void {
  $project_root = realpath(__DIR__ . '/../../..');
  $docs_static_dir = realpath(__DIR__ . '/../static/img');
  $renderer = __DIR__ . '/svg-term-render.js';

  if ($project_root === FALSE || $docs_static_dir === FALSE) {
    fwrite(STDERR, "Failed to resolve project paths\n");
    exit(1);
  }

  $recorder = new VideoRecorder($project_root, $docs_static_dir, $renderer);

  $recorder->info('Vortex installer video updater (PHP)');

  $recorder->checkDependencies(['expect']);

  $workspace = $recorder->workspaceInit('installer');

  $recorder->buildInstallerPhar("$workspace/installer.php");

  $expect_script = "$workspace/installer.exp";
  if (file_put_contents($expect_script, build_installer_expect_script(PROMPT_DELAY)) === FALSE) {
    throw new RuntimeException("Failed to write expect script: $expect_script");
  }
  chmod($expect_script, 0o755);

  $cast = "$workspace/new.json";
  $recorder->recordSession(
    cwd: $workspace,
    cast_path: $cast,
    command: $expect_script,
    title: 'Vortex Installer Demo',
  );

  $recorder->postprocessCast($cast, $workspace);

  $recorder->renderSvg($cast, "$workspace/new.svg");
  $recorder->renderPng($cast, "$workspace/new.png");
  $recorder->renderGif($cast, "$workspace/new.gif");

  $recorder->installArtifacts($workspace, 'installer');

  $recorder->pass('Installer video updated');
}

main();
