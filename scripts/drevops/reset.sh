#!/usr/bin/env bash
##
# Reset project to a freshly cloned repo state.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Change permissions and remove all other untracked files.
git ls-files --others -i --exclude-from=.gitignore -z | xargs -0 -I {} -- bash -c '( chmod 777 "{}" > /dev/null || true ) && ( rm -rf "{}" > /dev/null || true )'

# Reset repository files.
git reset --hard

# Remove all untracked, files.
git clean -f -d

# Remove empty directories.
find . -type d -not -path "./.git/*" -empty -delete
