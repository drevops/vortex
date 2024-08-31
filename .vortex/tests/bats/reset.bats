#!/usr/bin/env bats
#
# Test for clean functionality.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Reset" {
  run_installer_quiet

  assert_files_present
  assert_git_repo

  mktouch "web/core/install"
  mktouch "web/modules/contrib/somemodule/somemodule.info.yml"
  mktouch "web/themes/contrib/sometheme/sometheme.info.yml"
  mktouch "web/profiles/contrib/someprofile/someprofile.info.yml"
  mktouch "web/sites/default/somesettingsfile.php"
  mktouch "web/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"

  mktouch "web/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"
  mktouch "web/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch ".logs/screenshots/s1.jpg"
  mktouch ".logs/screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "web/sites/default/settings.local.php"
  mktouch "web/sites/default/services.local.yml"

  echo 'version: "2.3"' >"docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "uncommitted_file.txt"

  ahoy reset

  assert_files_present
  assert_git_repo

  assert_dir_not_exists "web/core"
  assert_dir_not_exists "web/modules/contrib"
  assert_dir_not_exists "web/themes/contrib"
  assert_dir_not_exists "web/profiles/contrib"

  assert_file_exists "web/sites/default/somesettingsfile.php"
  assert_file_exists "web/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "web/themes/custom/star_wars/node_modules"

  assert_dir_not_exists "web/themes/custom/star_wars/build"
  assert_file_not_exists "web/themes/custom/star_wars/scss/_components.scss"

  assert_file_exists ".logs/screenshots/s1.jpg"
  assert_file_exists ".logs/screenshots/s2.jpg"

  assert_file_exists ".data/db.sql"
  assert_file_exists ".data/db_2.sql"

  assert_file_exists "web/sites/default/settings.local.php"
  assert_file_exists "web/sites/default/services.local.yml"

  assert_file_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_exists "uncommitted_file.txt"
}

@test "Reset; hard; no commit" {
  run_installer_quiet

  assert_files_present
  assert_git_repo

  mktouch "first.txt"
  git_add "first.txt"
  git_commit "first commit"

  mktouch "web/core/install"
  mktouch "web/modules/contrib/somemodule/somemodule.info.yml"
  mktouch "web/themes/contrib/sometheme/sometheme.info.yml"
  mktouch "web/profiles/contrib/someprofile/someprofile.info.yml"
  mktouch "web/sites/default/somesettingsfile.php"
  mktouch "web/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch "web/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"

  mktouch ".logs/screenshots/s1.jpg"
  mktouch ".logs/screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "web/sites/default/settings.local.php"
  mktouch "web/sites/default/services.local.yml"

  echo 'version: "2.3"' >"docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "uncommitted_file.txt"

  mktouch "composer.lock"
  mktouch "web/themes/custom/zzzsomecustomtheme/package-lock.json"

  ahoy reset hard

  assert_git_repo
  assert_files_not_present_common

  assert_dir_not_exists "web/core"
  assert_dir_not_exists "web/modules/contrib"
  assert_dir_not_exists "web/themes/contrib"
  assert_dir_not_exists "web/profiles/contrib"

  assert_file_not_exists "web/sites/default/somesettingsfile.php"
  assert_file_not_exists "web/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "web/themes/custom/zzzsomecustomtheme/node_modules"

  assert_dir_not_exists "web/themes/custom/zzzsomecustomtheme/build"
  assert_file_not_exists "web/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  assert_dir_not_exists ".logs/screenshots"

  assert_file_not_exists ".data/db.sql"
  assert_file_not_exists ".data/db_2.sql"

  assert_file_not_exists "web/sites/default/settings.local.php"
  assert_file_not_exists "web/sites/default/services.local.yml"

  assert_file_not_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_not_exists "uncommitted_file.txt"

  assert_file_not_exists "composer.lock"
  assert_file_not_exists "web/themes/custom/star_wars/package-lock.json"
}

@test "Reset; hard; committed files" {
  run_installer_quiet

  assert_files_present
  assert_git_repo

  mktouch "web/core/install"
  mktouch "web/modules/contrib/somemodule/somemodule.info.yml"
  mktouch "web/themes/contrib/sometheme/sometheme.info.yml"
  mktouch "web/profiles/contrib/someprofile/someprofile.info.yml"
  mktouch "web/sites/default/somesettingsfile.php"
  mktouch "web/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"

  mktouch "web/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "web/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"
  mktouch "web/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch ".logs/screenshots/s1.jpg"
  mktouch ".logs/screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "web/sites/default/settings.local.php"
  mktouch "web/sites/default/services.local.yml"

  echo 'version: "2.3"' >"docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "composer.lock"
  mktouch "web/themes/custom/star_wars/package-lock.json"

  git_add_all_commit "Added Vortex files"

  mktouch "uncommitted_file.txt"

  # Commit other file file.
  mktouch "committed_file.txt"
  git add "committed_file.txt"
  git commit -m "Added custom file" >/dev/null

  ahoy reset hard

  assert_files_present_common
  assert_git_repo

  assert_dir_not_exists "web/core"
  assert_dir_not_exists "web/modules/contrib"
  assert_dir_not_exists "web/themes/contrib"
  assert_dir_not_exists "web/profiles/contrib"

  assert_file_not_exists "web/sites/default/somesettingsfile.php"
  assert_file_not_exists "web/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "web/themes/custom/zzzsomecustomtheme/node_modules"

  assert_dir_not_exists "web/themes/custom/zzzsomecustomtheme/build"
  #assert_file_not_exists "web/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  assert_dir_not_exists ".logs/screenshots"

  assert_file_not_exists ".data/db.sql"
  assert_file_not_exists ".data/db_2.sql"

  assert_file_not_exists "web/sites/default/settings.local.php"
  assert_file_not_exists "web/sites/default/services.local.yml"

  assert_file_not_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_not_exists "uncommitted_file.txt"

  assert_file_exists "scripts/vortex/download-db-acquia.sh"
  assert_file_exists "committed_file.txt"

  # The files would be committed to the consumer repo.
  assert_file_exists "composer.lock"
  assert_file_exists "web/themes/custom/star_wars/package-lock.json"
}
