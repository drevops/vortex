#!/usr/bin/env bash
##
# Simulate a user typing a command at a prompt, then execute it.
#
# Used by `update-videos.php` so that each recorded command video starts
# with the visible prompt + command before the actual output streams in.
#
# Usage:
#   type-and-run.sh ahoy lint
##

set -e

cmd="$*"

printf '$ '
sleep 0.5

for ((i=0; i<${#cmd}; i++)); do
  printf '%s' "${cmd:i:1}"
  sleep 0.05
done

sleep 0.4
printf '\n'

exec bash -c "$cmd"
