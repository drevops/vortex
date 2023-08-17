#!/usr/bin/env bash
##
# Reset project to a freshly cloned repository state.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started reset."

note "Changing permissions and remove all other untracked files."
git ls-files --others -i --exclude-from=.gitignore -z | xargs -0 -I {} -- bash -c '( chmod 777 "{}" > /dev/null || true ) && ( rm -rf "{}" > /dev/null || true )'

note "Resetting repository files."
git reset --hard

note "Removing all untracked, files."
git clean -f -d

ntoe "Removing empty directories."
find . -type d -not -path "./.git/*" -empty -delete

pass "Finished reset."
