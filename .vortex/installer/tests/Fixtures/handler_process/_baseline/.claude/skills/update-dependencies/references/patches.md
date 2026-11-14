# Fix a patch that no longer applies

Composer stops an install or an update with `No available patcher was able to apply patch <url> to <vendor/package>` when a patch in `extra.patches` doesn't apply to the new version of the package. The message names the patch and the package. Decide first whether the patch is still needed.

## Check whether the fix landed upstream

Read the release notes of the new version and the issue that the patch comes from. When the new version contains the fix, remove the patch. When it doesn't, look on the issue for a patch made for the new version before you re-roll it yourself.

Prefer a patch file saved in `patches/` over a URL whose content can change, such as a merge request diff. Point the entry in `extra.patches` at the local path, such as `patches/<package>-<description>.patch`.

## Remove a patch

1. Delete the entry from `extra.patches` in `composer.json`. Delete its file from `patches/` when no other entry uses it.
2. `ahoy composer patches-relock`
3. Delete the package's directory, because `patches-repatch` reinstalls only the packages that still have patches. The directory follows `installer-paths` in `composer.json`, such as `web/modules/contrib/<name>` for a module or `vendor/<vendor>/<package>` for a library: `ahoy cli rm -rf web/modules/contrib/<name>`
4. `ahoy composer patches-repatch`
5. `ahoy composer update --lock`
6. Confirm that `patches.lock.json` lists the package only when it still has other patches.

## Re-roll a patch

1. Remove the failing entry from `extra.patches` and keep its file. Run `ahoy composer patches-relock`.
2. Run the update again, so that the new version installs without the patch: `ahoy composer update <vendor/package> --with-dependencies`
3. Find the package's source repository and the installed reference: `ahoy composer show <vendor/package>` prints them on its `source` line.
4. Clone the source under `.artifacts/tmp/`: `git clone <source url> .artifacts/tmp/reroll/<name>`
5. Check out the installed reference: `git -C .artifacts/tmp/reroll/<name> checkout <reference>`
6. Apply the old patch with a 3-way merge: `git -C .artifacts/tmp/reroll/<name> apply --3way <absolute path to the old patch>`
7. Resolve each conflict in the clone, keeping the intent of the original patch. When the new version changed the patched code too much to tell, stop and ask the developer.
8. Write the new patch to `patches/`, with an absolute path because `-C` changes the directory: `git -C .artifacts/tmp/reroll/<name> diff --output=<absolute path to the project>/patches/<package>-<description>.patch`
9. Point the entry in `extra.patches` at the new file, then apply it: `ahoy composer patches-relock`, `ahoy composer patches-repatch` and `ahoy composer update --lock`, 1 command per call.
10. Confirm that the `patches-repatch` output has a `- Patching <vendor/package>` line and no error.
11. Delete the clone: `rm -rf .artifacts/tmp/reroll/<name>`

Composer Patches applies a patch at a depth that it takes from the patch's own `depth`, then from `extra.composer-patches.package-depths`, then from its built-in default for the package, then from `default-patch-depth`. Most patches apply at depth 1. Patches for `drupal/core` use paths that start with `core/` and apply at depth 2, the built-in default for that package. When you re-roll a core patch from a checkout of the `drupal/core` package, which has no `core/` directory, add `-p2` to the `git apply` command, and keep the shape of the new patch by adding `--src-prefix=a/core/ --dst-prefix=b/core/` to the `git diff` command. The 2.x plugin doesn't read the `patchLevel` key in `composer.json`.

## Patches for the tooling package

`scripts/vortex-tooling.sh` installs `drevops/vortex-tooling` before the project's full `composer install` runs. It copies the package's entries from `extra.patches` and the `patches/` directory into a temporary project, so a patch for that package applies there too.
