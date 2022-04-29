#!/usr/bin/env bats
#
# Tests for installer parameters for quiet and interactive installation.
#
# - proceed switch (used to stop installation)
# - default values
# - normalisation
# - discovery
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Install parameters: empty dir; proceed switch; quiet" {
  export DREVOPS_PROCEED_INSTALLATION=0
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; proceed switch; interactive" {
  export DREVOPS_PROCEED_INSTALLATION=0

  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # database_download_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; defaults; quiet" {
  export DREVOPS_PROCEED_INSTALLATION=0

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                            Name:  Star wars     "
  assert_output_contains "                    Machine name:  star_wars     "
  assert_output_contains "                    Organisation:  Star wars Org "
  assert_output_contains "       Organisation machine name:  star_wars_org "
  assert_output_contains "                   Module prefix:  star_wars     "
  assert_output_contains "                         Profile:  standard      "
  assert_output_contains "                      Theme name:  star_wars     "
  assert_output_contains "                             URL:  star-wars.com "
  assert_output_contains "        Database download source:  curl          "
  assert_output_contains "             Database store type:  file          "
  assert_output_contains "                      Deployment:  code          "
  assert_output_contains "                 FTP integration:  Disabled      "
  assert_output_contains "              Acquia integration:  Disabled      "
  assert_output_contains "              Lagoon integration:  Disabled      "
  assert_output_contains "     dependencies.io integration:  Enabled       "
  assert_output_contains "       Preserve docs in comments:  Yes           "
  assert_output_contains "       Preserve DrevOps comments:  No            "
}

@test "Install parameters: empty dir; defaults; interactive" {
  export DREVOPS_PROCEED_INSTALLATION=0

  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # database_download_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                            Name:  Star wars     "
  assert_output_contains "                    Machine name:  star_wars     "
  assert_output_contains "                    Organisation:  Star wars Org "
  assert_output_contains "       Organisation machine name:  star_wars_org "
  assert_output_contains "                   Module prefix:  star_wars     "
  assert_output_contains "                         Profile:  standard      "
  assert_output_contains "                      Theme name:  star_wars     "
  assert_output_contains "                             URL:  star-wars.com "
  assert_output_contains "        Database download source:  curl          "
  assert_output_contains "             Database store type:  file          "
  assert_output_contains "                      Deployment:  code          "
  assert_output_contains "                 FTP integration:  Disabled      "
  assert_output_contains "              Acquia integration:  Disabled      "
  assert_output_contains "              Lagoon integration:  Disabled      "
  assert_output_contains "     dependencies.io integration:  Enabled       "
  assert_output_contains "       Preserve docs in comments:  Yes           "
  assert_output_contains "       Preserve DrevOps comments:  No            "
}

# Note that there is no quiet test for this scenario.
@test "Install parameters: empty dir; overrides and normalisation; interactive" {
  export DREVOPS_PROCEED_INSTALLATION=0

  answers=(
    "star Wars" # name
    "star wars MaCHine" # machine_name
    "The Empire" # org
    "the new empire" # morh_machine_name
    "s W" # module_prefix
    "S w Profile" # profile
    "light saber" # theme
    "resistance forever.com" # URL
    "nah" # fresh_install
    "something" # download_db_type
    "other thing" # database_download_source
    "dunno" # database_store_type
    "nnnooo" # deploy_type
    "nope" # preserve_ftp
    "dunno" # preserve_acquia
    "nah" # preserve_lagoon
    "never" # preserve_dependenciesio
    "nnnooo" # preserve_doc_comments
    "nooo" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed."

  assert_output_contains "                            Name:  Star wars              "
  assert_output_contains "                    Machine name:  star_wars_machine      "
  assert_output_contains "                    Organisation:  The Empire             "
  assert_output_contains "       Organisation machine name:  the_new_empire         "
  assert_output_contains "                   Module prefix:  s_w                    "
  assert_output_contains "                         Profile:  s_w_profile            "
  assert_output_contains "                      Theme name:  light_saber            "
  assert_output_contains "                             URL:  resistance-forever.com "
  assert_output_contains "        Database download source:  curl                   "
  assert_output_contains "             Database store type:  file                   "
  assert_output_contains "                      Deployment:  Disabled               "
  assert_output_contains "                 FTP integration:  Disabled               "
  assert_output_contains "              Acquia integration:  Disabled               "
  assert_output_contains "              Lagoon integration:  Disabled               "
  assert_output_contains "     dependencies.io integration:  Disabled               "
  assert_output_contains "       Preserve docs in comments:  No                     "
  assert_output_contains "       Preserve DrevOps comments:  No                     "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; quiet" {
  export DREVOPS_PROCEED_INSTALLATION=0

  fixture_preinstalled

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                            Name:  Resistance new site          "
  assert_output_contains "                    Machine name:  resistance_site              "
  assert_output_contains "                    Organisation:  The Resistance               "
  assert_output_contains "       Organisation machine name:  the_next_resistance          "
  assert_output_contains "                   Module prefix:  another_resist               "
  assert_output_contains "                         Profile:  standard                     "
  assert_output_contains "                      Theme name:  resisting                    "
  assert_output_contains "                             URL:  www.resistance-star-wars.com "
  assert_output_contains "        Database download source:  curl                         "
  assert_output_contains "             Database store type:  file                         "
  assert_output_contains "                      Deployment:  code                         "
  assert_output_contains "                 FTP integration:  Disabled                     "
  assert_output_contains "              Acquia integration:  Enabled                      "
  assert_output_contains "              Lagoon integration:  Enabled                      "
  assert_output_contains "     dependencies.io integration:  Enabled                      "
  assert_output_contains "       Preserve docs in comments:  Yes                          "
  assert_output_contains "       Preserve DrevOps comments:  Yes                          "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; accepting suggested values" {
  export DREVOPS_PROCEED_INSTALLATION=0

  fixture_preinstalled

  answers=(
    "nothing" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # database_download_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "                            Name:  Resistance new site          "
  assert_output_contains "                    Machine name:  resistance_site              "
  assert_output_contains "                    Organisation:  The Resistance               "
  assert_output_contains "       Organisation machine name:  the_next_resistance          "
  assert_output_contains "                   Module prefix:  another_resist               "
  assert_output_contains "                         Profile:  standard                     "
  assert_output_contains "                      Theme name:  resisting                    "
  assert_output_contains "                             URL:  www.resistance-star-wars.com "
  assert_output_contains "        Database download source:  curl                         "
  assert_output_contains "             Database store type:  file                         "
  assert_output_contains "                      Deployment:  code                         "
  assert_output_contains "                 FTP integration:  Disabled                     "
  assert_output_contains "              Acquia integration:  Enabled                      "
  assert_output_contains "              Lagoon integration:  Enabled                      "
  assert_output_contains "     dependencies.io integration:  Enabled                      "
  assert_output_contains "       Preserve docs in comments:  Yes                          "
  assert_output_contains "       Preserve DrevOps comments:  Yes                          "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; user input overrides discovery which overrides defaults" {
  export DREVOPS_PROCEED_INSTALLATION=0

  fixture_preinstalled

  # Order of values overrides for interactive install into existing dir (bottom wins):
  # - default value
  # - discovered value
  # - entered value (but if nothing entered, will fallback to discovered value).
  answers=(
    "star Wars" # name
    "star wars MaCHine" # machine_name
    "The Empire" # org
    "the new empire" # morh_machine_name
    "s W" # module_prefix
    "S w Profile" # profile
    "light saber" # theme
    "resistance forever.com" # URL
    "nah" # fresh_install
    "image" # database_download_source
    "image" # database_store_type
    "no" # deploy_type
    "Y" # preserve_ftp
    "nothing" # preserve_acquia - testing NOTHING value - should be 'Enabled' as exists in fixture.
    "nah" # preserve_lagoon
    "nothing" # preserve_dependenciesio - testing NOTHING value - should be 'Enabled as exists in fixture.
    "n" # preserve_doc_comments
    "n" # preserve_drevops_info
  )

  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_contains "It looks like DrevOps is already installed into this project."
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "                            Name:  Star wars              "
  assert_output_contains "                    Machine name:  star_wars_machine      "
  assert_output_contains "                    Organisation:  The Empire             "
  assert_output_contains "       Organisation machine name:  the_new_empire         "
  assert_output_contains "                   Module prefix:  s_w                    "
  assert_output_contains "                         Profile:  s_w_profile            "
  assert_output_contains "                      Theme name:  light_saber            "
  assert_output_contains "                             URL:  resistance-forever.com "
  assert_output_contains "        Database download source:  docker_registry        "
  assert_output_contains "             Database store type:  docker_image           "
  assert_output_contains "                      Deployment:  Disabled               "
  assert_output_contains "                 FTP integration:  Enabled                "
  assert_output_contains "              Acquia integration:  Enabled                "
  assert_output_contains "              Lagoon integration:  Disabled               "
  assert_output_contains "     dependencies.io integration:  Enabled                "
  assert_output_contains "       Preserve docs in comments:  No                     "
  assert_output_contains "       Preserve DrevOps comments:  No                     "
}

#
# Helper to create fixture files to fake pre-installed state.
#
# Note that this helper provides only one state of the fixture site.
#
fixture_preinstalled(){
  # Create readme file to pretend that DrevOps was installed.
  fixture_readme

  # Sets 'name' to 'Resistance new site'.
  # Sets 'machine_name' to 'resistance_site'.
  # Sets 'org' to 'The Resistance'.
  # Sets 'org_machine_name' to 'the_next_resistance'.
  fixture_composerjson "Resistance new site" "resistance_site" "The Resistance" "the_next_resistance"

  # Sets 'module_prefix' to 'another_resist'.
  mkdir -p docroot/modules/custom/another_resist_core
  mkdir -p docroot/modules/custom/some_resist_notcore
  mkdir -p docroot/modules/custom/yetanother_resist

  # Sets 'theme' to 'resisting'.
  mktouch docroot/sites/all/themes/custom/resisting/resisting.info.yml
  mktouch docroot/sites/all/themes/custom/yetanothertheme/yetanothertheme.info.yml

  # Sets 'url' to 'www.resistance-star-wars.com'.
  mkdir -p docroot/sites/default
  echo "  \$config['stage_file_proxy.settings']['origin'] = 'http://www.resistance-star-wars.com/';" > docroot/sites/default/settings.php

  # Sets 'preserve_acquia' to 'Yes'.
  mkdir -p hooks
  # Sets 'preserve_lagoon' to 'Yes'.
  touch .lagoon.yml
  # Sets 'preserve_dependencies' to 'Yes'.
  touch dependencies.yml

  # Sets 'fresh_install' to 'No'.
  echo "download-db:" >> .ahoy.yml

  # Sets 'preserve_doc_comments' to 'Yes'.
  echo "# Ahoy configuration file." >> .ahoy.yml

  # Sets 'preserve_drevops_info' to 'Yes'.
  echo "# Comments starting with '#:' provide explicit documentation and will be" >> .ahoy.yml
}
