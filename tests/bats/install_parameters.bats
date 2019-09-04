#!/usr/bin/env bats
#
# Tests for installer parameters for silent and interactive installation.
#
# - proceed switch (used to stop installation)
# - default values
# - normalisation
# - discovery
#

load _helper
load _helper_drupaldev

@test "Install parameters: empty dir; proceed switch; silent" {
  export DRUPALDEV_PROCEED=0
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; proceed switch; interactive" {
  export DRUPALDEV_PROCEED=0

  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependenciesio
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; defaults; silent" {
  export DRUPALDEV_PROCEED=0

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          Star wars"
  assert_output_contains "Machine name:                  star_wars"
  assert_output_contains "Organisation:                  Star wars Org"
  assert_output_contains "Organisation machine name:     star_wars_org"
  assert_output_contains "Module prefix:                 star_wars"
  assert_output_contains "Profile:                       standard"
  assert_output_contains "Theme name:                    star_wars"
  assert_output_contains "URL:                           star-wars.com"

  assert_output_contains "Fresh install for every build: No"
  assert_output_contains "Deployment:                    Enabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Enabled"
  assert_output_contains "FTP integration:               Disabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

@test "Install parameters: empty dir; defaults; interactive" {
  export DRUPALDEV_PROCEED=0

  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependenciesio
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          Star wars"
  assert_output_contains "Machine name:                  star_wars"
  assert_output_contains "Organisation:                  Star wars Org"
  assert_output_contains "Organisation machine name:     star_wars_org"
  assert_output_contains "Module prefix:                 star_wars"
  assert_output_contains "Profile:                       standard"
  assert_output_contains "Theme name:                    star_wars"
  assert_output_contains "URL:                           star-wars.com"

  assert_output_contains "Fresh install for every build: No"
  assert_output_contains "Deployment:                    Enabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Enabled"
  assert_output_contains "FTP integration:               Disabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

# Note that there is no silent test for this scenario.
@test "Install parameters: empty dir; overrides and normalisation; interactive" {
  export DRUPALDEV_PROCEED=0

  answers=(
    "star Wars" # name
    "star wars MaCHine" # machine_name
    "The Empire" # org
    "the new empire" # morh_machine_name
    "s W" # module_prefix
    "S w Profile" # profile
    "light saber" # theme
    "resistance forever.com" # URL
    "Nope" # fresh_install
    "n" # preserve_deployment
    "dunno" # preserve_acquia
    "nah" # preserve_lagoon
    "Y" # preserve_ftp
    "never" # preserve_dependenciesio
    "nooo" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          Star Wars"
  assert_output_contains "Machine name:                  star_wars_machine"
  assert_output_contains "Organisation:                  The Empire"
  assert_output_contains "Organisation machine name:     the_new_empire"
  assert_output_contains "Module prefix:                 s_w"
  assert_output_contains "Profile:                       s_w_profile"
  assert_output_contains "Theme name:                    light_saber"
  assert_output_contains "URL:                           resistance-forever.com"
  assert_output_contains "Fresh install for every build: No"
  assert_output_contains "Deployment:                    Disabled"
  assert_output_contains "Acquia integration:            Disabled"
  assert_output_contains "Lagoon integration:            Disabled"
  assert_output_contains "FTP integration:               Enabled"
  assert_output_contains "dependencies.io integration:   Disabled"
  assert_output_contains "Remove Drupal-Dev comments:    No"
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; silent" {
  export DRUPALDEV_PROCEED=0

  fixture_preinstalled

  # Remove existing fixture to force override from discovery.
  rm 1.txt

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          Resistance new site"
  assert_output_contains "Machine name:                  resistance_site"
  assert_output_contains "Organisation:                  The Resistance"
  assert_output_contains "Organisation machine name:     the_next_resistance"
  assert_output_contains "Module prefix:                 another_resist"
  assert_output_contains "Profile:                       standard"
  assert_output_contains "Theme name:                    resisting"
  assert_output_contains "URL:                           www.resistance-star-wars.com"
  assert_output_contains "Fresh install for every build: No"
  assert_output_contains "Deployment:                    Disabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Enabled"
  assert_output_contains "FTP integration:               Disabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; accepting suggested values" {
  export DRUPALDEV_PROCEED=0

  fixture_preinstalled

  # Remove existing fixture to force override from discovery.
  rm 1.txt

  answers=(
    "nothing" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependenciesio
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "Name:                          Resistance new site"
  assert_output_contains "Machine name:                  resistance_site"
  assert_output_contains "Organisation:                  The Resistance"
  assert_output_contains "Organisation machine name:     the_next_resistance"
  assert_output_contains "Module prefix:                 another_resist"
  assert_output_contains "Profile:                       standard"
  assert_output_contains "Theme name:                    resisting"
  assert_output_contains "URL:                           www.resistance-star-wars.com"
  assert_output_contains "Fresh install for every build: No"
  assert_output_contains "Deployment:                    Disabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Enabled"
  assert_output_contains "FTP integration:               Disabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; user input overrides discovery which overrides defaults" {
  export DRUPALDEV_PROCEED=0

  fixture_preinstalled

  answers=(
    "star Wars" # name
    "star wars MaCHine" # machine_name
    "The Empire" # org
    "the new empire" # morh_machine_name
    "s W" # module_prefix
    "S w Profile" # profile
    "light saber" # theme
    "resistance forever.com" # URL
    "Y" # fresh_install
    "n" # preserve_deployment
    "nothing" # preserve_acquia - testing NOTHING value - should be 'Enabled'
    "nah" # preserve_lagoon
    "Y" # preserve_ftp
    "nothing" # preserve_dependenciesio - testing NOTHING value
    "Y" # remove_drupaldev_info
  )

  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "Name:                          Star Wars"
  assert_output_contains "Machine name:                  star_wars_machine"
  assert_output_contains "Organisation:                  The Empire"
  assert_output_contains "Organisation machine name:     the_new_empire"
  assert_output_contains "Module prefix:                 s_w"
  assert_output_contains "Profile:                       s_w_profile"
  assert_output_contains "Theme name:                    light_saber"
  assert_output_contains "URL:                           resistance-forever.com"
  assert_output_contains "Fresh install for every build: Yes"
  assert_output_contains "Deployment:                    Disabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Disabled"
  assert_output_contains "FTP integration:               Enabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

#
# Helper to create fixture files to fake pre-installed state.
#
# Note that this helper provides only one state of the fixture site.
#
fixture_preinstalled(){
  # Create readme file to pretend that Drupal-Dev was installed.
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
  mkdir -p docroot/themes/custom/resisting
  mkdir -p docroot/themes/custom/yetanothertheme

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
  echo "download-db:" > .ahoy.yml
  # Sets 'remove_drupaldev_info' to 'Yes'.
  echo "#;< DRUPAL-DEV" > 1.txt
}
