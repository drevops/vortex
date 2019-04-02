#!/usr/bin/env bash
##
# Download Drupal-Dev files from the centralised location.
#
# If you see this file it means that someone has already
# installed Drupal-Dev into this project.
#
# Run this file to download the latest version of Drupal-Dev.
#
# === WHAT IS DRUPAL-DEV ===
# Drupal-Dev is a development environment for Drupal sites with tools included.
# https://drupal-dev.io
# https://github.com/integratedexperts/drupal-dev
#
# === WHAT IS THIS FILE AND WHY DO I NEED IT ===
# Using Drupal-Dev requires initial installation into your project. Once
# installed, it the can be "attached" in every environment were development
# stack is required. This means that your project will have only small number
# of Drupal-Dev files committed - the rest of the files will be downloaded each
# time Drupal-Dev needs to be "attached".
#
# This file is a script to download Drupal-Dev at the latest stable version and
# "attach" it to the current environment.
# Files already committed within current repository will not be overridden.
#
# Usage:
#
# For silent installation (configuration discovered from the environment):
# ./drupal-dev.sh
#
# For interactive installation (wizard suggests defaults from configuration
# discovered from the environment):
# ./drupal-dev.sh --interactive
#
# === HOW TO OVERRIDE LOCALLY EXCLUDED FILES ===
# To override any files coming from Drupal-Dev to persist in the current
# repository, modify `.git/info/exclude` file and commit them.
#
# === HOW TO UPDATE DRUPAL-DEV ===
# ahoy update
#
# === HOW TO PIN TO SPECIFIC DRUPAL-DEV COMMIT ===
# For development of Drupal-Dev or debugging of the development stack, it may be
# required to point to the specific Drupal-Dev's commit rather then use the latest
# stable version.
#
# Uncomment and set the Drupal-Dev's commit value. Commit this change to apply
# this to all environments.
# export DRUPALDEV_COMMIT=COMMIT_SHA

bash <(curl -L https://raw.githubusercontent.com/integratedexperts/drupal-dev/8.x/install.sh?"$(date +%s)") "$@"
