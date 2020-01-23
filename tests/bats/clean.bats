#!/usr/bin/env bats
#
# Test for clean functionality.
#

load _helper
load _helper_drevops

@test "Clean" {
  run_install

  assert_files_present
  assert_git_repo

  mktouch "docroot/index.php"
  mktouch "docroot/sites/all/modules/contrib/somemodule/somemodule.info"
  mktouch "docroot/sites/all/themes/contrib/sometheme/sometheme.info"
  mktouch "docroot/profiles/zzzsomeprofile/zzzsomeprofile.info"
  mktouch "docroot/sites/default/somesettingsfile.php"
  mktouch "docroot/sites/default/settings.generated.php"
  mktouch "docroot/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch "screenshots/s1.jpg"
  mktouch "screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "docroot/sites/default/settings.local.php"

  echo "version: \"2.3\"" > "docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "uncommitted_file.txt"

  ahoy clean

  assert_files_present
  assert_git_repo

  assert_dir_not_exists "docroot/includes"
  assert_dir_not_exists "docroot/sites/all/modules/contrib"
  assert_dir_not_exists "docroot/sites/all/themes/contrib"
  assert_dir_not_exists "docroot/profiles/zzzsomeprofile"

  assert_file_exists "docroot/sites/default/somesettingsfile.php"
  assert_file_not_exists "docroot/sites/default/settings.generated.php"
  assert_file_exists "docroot/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "docroot/sites/all/themes/custom/star_wars/node_modules"

  assert_dir_not_exists "docroot/sites/all/themes/custom/star_wars/build"
  assert_file_not_exists "docroot/sites/all/themes/custom/star_wars/scss/_components.scss"

  assert_file_exists "screenshots/s1.jpg"
  assert_file_exists "screenshots/s2.jpg"

  assert_file_exists ".data/db.sql"
  assert_file_exists ".data/db_2.sql"

  assert_file_exists "docroot/sites/default/settings.local.php"

  assert_file_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_exists "uncommitted_file.txt"
}

@test "Reset; no commit" {
  run_install

  assert_files_present
  assert_git_repo

  mktouch "first.txt"
  git_add "first.txt"
  git_commit "first commit"

  mktouch "docroot/index.php"
  mktouch "docroot/sites/all/modules/contrib/somemodule/somemodule.info"
  mktouch "docroot/sites/all/themes/contrib/sometheme/sometheme.info"
  mktouch "docroot/profiles/zzzsomeprofile/zzzsomeprofile.info"
  mktouch "docroot/sites/default/somesettingsfile.php"
  mktouch "docroot/sites/default/settings.generated.php"
  mktouch "docroot/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"

  mktouch "screenshots/s1.jpg"
  mktouch "screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "docroot/sites/default/settings.local.php"

  echo "version: \"2.3\"" > "docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "uncommitted_file.txt"

  mktouch "composer.lock"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/package-lock.json"

  ahoy reset

  assert_git_repo
  assert_files_not_present_common "star_wars"

  assert_dir_not_exists "docroot/includes"
  assert_dir_not_exists "docroot/sites/all/modules/contrib"
  assert_dir_not_exists "docroot/sites/all/themes/contrib"
  assert_dir_not_exists "docroot/profiles/zzzsomeprofile"

  assert_file_not_exists "docroot/sites/default/somesettingsfile.php"
  assert_file_not_exists "docroot/sites/default/settings.generated.php"
  assert_file_not_exists "docroot/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules"

  assert_dir_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/build"
  assert_file_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  assert_dir_not_exists "screenshots"

  assert_file_not_exists ".data/db.sql"
  assert_file_not_exists ".data/db_2.sql"

  assert_file_not_exists "docroot/sites/default/settings.local.php"

  assert_file_not_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_not_exists "uncommitted_file.txt"

  assert_file_not_exists "composer.lock"
  assert_file_not_exists "docroot/sites/all/themes/custom/star_wars/package-lock.json"
}

@test "Reset; committed files" {
  run_install

  assert_files_present
  assert_git_repo

  mktouch "docroot/index.php"
  mktouch "docroot/sites/all/modules/contrib/somemodule/somemodule.info"
  mktouch "docroot/sites/all/themes/contrib/sometheme/sometheme.info"
  mktouch "docroot/profiles/zzzsomeprofile/zzzsomeprofile.info"
  mktouch "docroot/sites/default/somesettingsfile.php"
  mktouch "docroot/sites/default/settings.generated.php"
  mktouch "docroot/sites/default/files/somepublicfile.php"

  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  # Make sure that sub-repos removed.
  mktouch "vendor/othervendor/otherpackage/.git/HEAD"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage with spaces.js"

  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/build/css/zzzsomecustomtheme.min.css"
  mktouch "docroot/sites/all/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  mktouch "screenshots/s1.jpg"
  mktouch "screenshots/s2.jpg"

  mktouch ".data/db.sql"
  mktouch ".data/db_2.sql"

  mktouch "docroot/sites/default/settings.local.php"

  echo "version: \"2.3\"" > "docker-compose.override.yml"

  mktouch ".idea/some_ide_file"
  mktouch ".vscode/some_ide_file"
  mktouch "nbproject/some_ide_file"

  mktouch "composer.lock"
  mktouch "docroot/sites/all/themes/custom/star_wars/package-lock.json"

  git_add_all_commit "Added DrevOps files"

  mktouch "uncommitted_file.txt"

  # Commit other file file.
  mktouch "committed_file.txt"
  git add "committed_file.txt"
  git commit -m "Added custom file" > /dev/null

  ahoy reset

  assert_files_present_common "star_wars" "StarWars"
  assert_git_repo

  assert_dir_not_exists "docroot/includes"
  assert_dir_not_exists "docroot/sites/all/modules/contrib"
  assert_dir_not_exists "docroot/sites/all/themes/contrib"
  assert_dir_not_exists "docroot/profiles/zzzsomeprofile"

  assert_file_not_exists "docroot/sites/default/somesettingsfile.php"
  assert_file_not_exists "docroot/sites/default/settings.generated.php"
  assert_file_not_exists "docroot/sites/default/files/somepublicfile.php"

  assert_dir_not_exists "vendor"
  assert_dir_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/node_modules"

  assert_dir_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/build"
  #assert_file_not_exists "docroot/sites/all/themes/custom/zzzsomecustomtheme/scss/_components.scss"

  assert_dir_not_exists "screenshots"

  assert_file_not_exists ".data/db.sql"
  assert_file_not_exists ".data/db_2.sql"

  assert_file_not_exists "docroot/sites/default/settings.local.php"

  assert_file_not_exists "docker-compose.override.yml"

  assert_file_exists ".idea/some_ide_file"
  assert_file_exists ".vscode/some_ide_file"
  assert_file_exists "nbproject/some_ide_file"

  assert_file_not_exists "uncommitted_file.txt"

  assert_file_exists "scripts/drevops/download-db-acquia.sh"
  assert_file_exists "committed_file.txt"

  # The files would be committed to the consumer repo.
  assert_file_exists "composer.lock"
  assert_file_exists "docroot/sites/all/themes/custom/star_wars/package-lock.json"
}
