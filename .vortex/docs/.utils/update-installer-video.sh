#!/usr/bin/env bash

##
# Update installer video recording.
#
# This script records an ASCII Cinema cast of the Vortex installer
# and updates the installer.json file used in the documentation.
#
# Usage:
#   ./update-installer-video.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

#-------------------------------------------------------------------------------

# Directory for temporary workspace.
WORKSPACE_DIR="${WORKSPACE_DIR:-$HOME/www/demo}"

# Wait time before pressing enter in seconds (decimals allowed).
PROMPT_DELAY=1

# Terminal dimensions for recording.
TERMINAL_HEIGHT=36
TERMINAL_WIDTH=$((80 * TERMINAL_HEIGHT / 24))

#-------------------------------------------------------------------------------

# Script directory and project paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCS_DIR="${SCRIPT_DIR}/.."
PROJECT_ROOT="${DOCS_DIR}/../.."
CAST_FILE="${DOCS_DIR}/static/img/installer.json"
SVG_FILE="${DOCS_DIR}/static/img/installer.svg"
PNG_FILE="${DOCS_DIR}/static/img/installer.png"
GIF_FILE="${DOCS_DIR}/static/img/installer.gif"

# Local installer paths
INSTALLER_DOCS="${DOCS_DIR}/static/install"
INSTALLER_BUILD="${PROJECT_ROOT}/.vortex/installer/build/installer.phar"
INSTALLER_SOURCE_DIR="${PROJECT_ROOT}/.vortex/installer"

# Logging functions
note() { printf "       %s\n" "${1}"; }
info() { echo -e "\033[0;34m[INFO]\033[0m $1" >&2; }
pass() { echo -e "\033[0;32m[ OK ]\033[0m $1" >&2; }
fail() { echo -e "\033[0;31m[FAIL]\033[0m $1" >&2; }

#-------------------------------------------------------------------------------

# Check if required tools are available.
check_dependencies() {
  local required_commands=("asciinema" "expect" "php" "composer" "npx")
  local missing_deps=()

  for cmd in "${required_commands[@]}"; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
      missing_deps+=("$cmd")
    fi
  done

  if [ ${#missing_deps[@]} -gt 0 ]; then
    fail "Missing required dependencies: ${missing_deps[*]}"
    fail "Please install them before running this script."
    echo >&2
    echo "Installation commands:" >&2
    echo "  brew install asciinema expect composer  # macOS" >&2
    echo "  apt-get install asciinema expect-dev composer  # Ubuntu/Debian" >&2
    exit 1
  fi
}

# Prepare the installer by building it if necessary.
prepare_installer() {
  info "Preparing installer..."

  # Build installer if source directory exists.
  if [ -d "$INSTALLER_SOURCE_DIR" ]; then
    info "Building installer from source"
    note "Source directory: $INSTALLER_SOURCE_DIR"

    # Change to installer directory and build.
    local current_dir
    current_dir=$(pwd)
    cd "$INSTALLER_SOURCE_DIR"

    info "Running composer install..."
    if ! composer install; then
      fail "Failed to run composer install"
      note "Directory: $INSTALLER_SOURCE_DIR"
      cd "$current_dir"
      return 1
    fi

    info "Running composer build..."
    if ! composer build; then
      fail "Failed to run composer build"
      note "Directory: $INSTALLER_SOURCE_DIR"
      cd "$current_dir"
      return 1
    fi

    cd "$current_dir"

    # Check if build was successful.
    if [ -f "$INSTALLER_BUILD" ]; then
      info "Successfully built installer"
      note "Built file: $INSTALLER_BUILD"
      note "Target: $INSTALLER_DOCS"
      cp "$INSTALLER_BUILD" "$INSTALLER_DOCS"
      return 0
    else
      fail "Build completed but installer not found"
      note "Expected location: $INSTALLER_BUILD"
      return 1
    fi
  fi

  fail "Could not find installer source or build directory"
  note "Searched locations:"
  note "  - Static: $INSTALLER_DOCS"
  note "  - Built: $INSTALLER_BUILD"
  note "  - Source: $INSTALLER_SOURCE_DIR"
  return 1
}

# Create expect script for automated interaction.
create_expect_script() {
  local expect_script="$1"

  cat >"$expect_script" <<EOF
#!/usr/bin/env expect

set timeout 60
log_user 1

# Configuration from shell script
set prompt_delay $PROMPT_DELAY

# Function to safely send input if process is still running.
proc safe_send {s} {
    if {[exp_pid] > 0} {
        send -- \$s
    } else {
        puts "child process already ended; skipping send <\$s>"
    }
}

# Function to wait for a quiet period (no output) for specified seconds.
proc wait_for_quiet {{secs 1}} {
    # Save and bump timeout down to the "quiet" window
    set old \$::timeout
    set ::timeout \$secs
    expect {
        -re {.+} { exp_continue }  ;# keep draining while data arrives
        timeout { }                ;# no data for 'secs' seconds
    }
    set ::timeout \$old
}

# Function to clear existing text with backspaces.
proc clear_field {} {
    for {set i 0} {\$i < 50} {incr i} {
        safe_send "\b"
    }
    after 150
}

# Function to simulate typing.
proc type_text {text} {
    wait_for_quiet 0.1
    set send_human {.1 .3 1 .05 2 .1 .2 0 .4 0 .6 0 .8 0 1}
    send -h \$text
}

# Function to wait and press enter
proc wait_and_enter {} {
    global prompt_delay
    wait_for_quiet 0.25
    sleep \$prompt_delay
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
EOF

  chmod +x "$expect_script"
}

# Record the installer session.
record_installer() {
  local workspace_dir="$1"
  local output_cast="$2"

  info "Setting up workspace directory"
  note "Directory: $workspace_dir"
  cd "$workspace_dir"

  if ! prepare_installer; then
    fail "Failed to prepare installer"
    return 1
  fi

  info "Copying installer to workspace directory"
  note "Source: $INSTALLER_DOCS"
  note "Target: $workspace_dir/installer.php"
  cp "$INSTALLER_DOCS" "$workspace_dir/installer.php"

  info "Creating automation script"
  local expect_script="$workspace_dir/installer_automation.exp"
  note "Script: $expect_script"
  create_expect_script "$expect_script"

  info "Starting ASCII Cinema recording"
  note "Terminal size: ${TERMINAL_WIDTH}x${TERMINAL_HEIGHT}"
  note "Output file: $output_cast"

  asciinema rec \
    --cols="$TERMINAL_WIDTH" \
    --rows="$TERMINAL_HEIGHT" \
    --title="Vortex Installer Demo" \
    --command="$expect_script" \
    --overwrite \
    "$output_cast"

  pass "Recording completed"
  note "Cast file: $output_cast"

  info "Post-processing cast file"
  note "Removing spawn command line from recording"
  local temp_processed="${output_cast}.processed"
  sed '2d' "$output_cast" >"$temp_processed"
  mv "$temp_processed" "$output_cast"
  note "Replace personal info in cast file"
  sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')
  sed "${sed_opts[@]}" -E 's|/Users/[^/]+/|/home/user/|g' "$output_cast"

  info "Converting cast to SVG"
  local output_svg="${output_cast%.*}.svg"
  note "Cast file: $output_cast"
  note "SVG file: $output_svg"

  if [ ! -f "$output_cast" ]; then
    fail "Cast file not found for SVG conversion"
    return 1
  fi

  if ! node "${SCRIPT_DIR}/svg-term-render.js" "$output_cast" "$output_svg" --line-height 1.1; then
    fail "Failed to convert cast to SVG"
    return 1
  fi
  pass "SVG conversion completed"

  info "Converting cast to PNG at poster timestamp"
  local output_png="${output_cast%.*}.png"
  note "PNG file: $output_png"
  note "Extracting frame SVG"

  if ! node "${SCRIPT_DIR}/svg-term-render.js" "$output_cast" "$output_png.svg" --line-height 1.1 --at 1000; then
    fail "Failed to extract frame at timestamp"
    return 1
  fi

  note "Converting frame SVG to PNG"
  if ! npx sharp-cli -i "$output_png.svg" -o "$output_png" -f png resize 1280; then
    fail "Failed to convert frame SVG to PNG"
    return 1
  fi
  rm -f "$output_png.svg"
  pass "PNG conversion completed"

  info "Converting cast to GIF"
  local output_gif="${output_cast%.*}.gif"
  note "GIF file: $output_gif"

  if command -v agg >/dev/null 2>&1; then
    if ! agg --cols "$TERMINAL_WIDTH" --rows "$TERMINAL_HEIGHT" "$output_cast" "$output_gif"; then
      fail "Failed to convert cast to GIF"
      return 1
    fi
    pass "GIF conversion completed"
  else
    note "Skipping: agg is not available"
  fi
}

main() {
  while [[ $# -gt 0 ]]; do
    case $1 in
      -h | --help)
        echo "Usage: $0"
        echo
        echo "Update installer video recording for Vortex documentation."
        echo
        echo "Options:"
        echo "  -h, --help      Show this help message"
        exit 0
        ;;
      *)
        fail "Unknown option: $1"
        echo "Use --help for usage information."
        exit 1
        ;;
    esac
  done

  info "Vortex Installer Video Updater"
  note "Automated installer recording and conversion tool"
  echo

  check_dependencies

  info "Creating temporary workspace"
  WORKSPACE_DIR="${WORKSPACE_DIR:-"$(mktemp -d -t vortex-installer-recording-XXXXXX)"}"
  [ ! -d "$WORKSPACE_DIR" ] && mkdir -p "$WORKSPACE_DIR"

  note "Workspace: $WORKSPACE_DIR"
  local temp_cast="$WORKSPACE_DIR/installer_new.json"
  local temp_svg="$WORKSPACE_DIR/installer_new.svg"
  local temp_png="$WORKSPACE_DIR/installer_new.png"
  local temp_gif="$WORKSPACE_DIR/installer_new.gif"

  cleanup() {
    info "Cleaning up workspace directory"
    note "Removing: ${WORKSPACE_DIR}"
    rm -rf "$WORKSPACE_DIR"
  }
  trap cleanup EXIT

  record_installer "${WORKSPACE_DIR}" "${temp_cast}"

  info "Updating installer files"
  note "Installing processed files to final locations"

  info "Verifying generated files"
  local files_missing=()

  if [ ! -f "$temp_cast" ]; then
    files_missing+=("JSON cast")
  fi

  if [ ! -f "$temp_svg" ]; then
    files_missing+=("SVG image")
  fi

  if [ ! -f "$temp_png" ]; then
    files_missing+=("PNG image")
  fi

  if [ ${#files_missing[@]} -gt 0 ]; then
    fail "Some generated files are missing: ${files_missing[*]}"
    note "Expected files:"
    note "  - JSON cast: $temp_cast"
    note "  - SVG image: $temp_svg"
    note "  - PNG image: $temp_png"
    fail "Cannot proceed with file installation"
    exit 1
  fi

  info "Copying processed files"
  note "JSON cast: $temp_cast → $CAST_FILE"
  cp "$temp_cast" "$CAST_FILE"
  note "SVG image: $temp_svg → $SVG_FILE"
  cp "$temp_svg" "$SVG_FILE"
  note "PNG image: $temp_png → $PNG_FILE"
  cp "$temp_png" "$PNG_FILE"
  if [ -f "$temp_gif" ]; then
    note "GIF image: $temp_gif → $GIF_FILE"
    cp "$temp_gif" "$GIF_FILE"
  fi

  pass "Successfully updated installer files"
  note "Updated files:"
  note "  - Cast: $CAST_FILE"
  note "  - SVG: $SVG_FILE"
  note "  - PNG: $PNG_FILE"
  if [ -f "$GIF_FILE" ]; then
    note "  - GIF: $GIF_FILE"
  fi
  note "Next steps:"
  note "  1. Review the updated files"
  note "  2. Test the documentation locally"
  note "  3. Commit all files to your repository"
}

main "$@"
