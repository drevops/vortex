#!/usr/bin/env bash
##
# Reset project to a freshly cloned repo state.
#

set -e

# Change permissions and remove all other untracked files.
git ls-files --others -i --exclude-from=.gitignore | xargs -I {} -- bash -c "chmod 777 {} || true && rm -rf {} || true"

# Reset repository files.
git reset --hard

# Remove all untracked, files.
git clean -f -d

# Remove empty directories.
find . -type d -not -path "./.git/*" -empty -delete
