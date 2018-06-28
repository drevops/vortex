#!/usr/bin/env bash
#
# Initialise drupal-dev project.
#
# Usage:
# init.sh           Interactively initialise project asking questions.
# init.sh somename  Initialise project using 'somename' as site name and
#                   defaults for all answers.
#
# Replaces the following placeholders with provided values:
# MYSITE:           Site name
# mysite:           Abbreviated version of `MYSITE`. Spaces replaced with
#                   underscores and lowercased.
# myorg:            Organisation name. Spaces replaced with underscores and
#                   lowercased. Defaults to `mysite`.
# mysiteurl:        Desired site URL. `local.` prefix is added to the provided
#                   value. Defaults to `mysite`.
# mysitetheme:      Theme name. Defaults to `mysite`.
#

main() {
  CURDIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

  if [ "$1" != "" ] ; then
    local site_name=$(to_human_name $1)
    local site_short=$(to_machine_name "$1")
    local site_theme=$site_short
    local site_url=${site_short//_/-}
    local org=$site_short
    local org_short=$(to_machine_name "$org")
    local acquia_hook_short=Y
    local remove_meta=Y
  else
    local site_name=$(to_human_name "$(basename $CURDIR)")
    site_name=$(ask "What is your site name? [$site_name]" "$site_name")
    local site_short=$(to_machine_name "$site_name")
    site_short=$(ask "What is your site machine name? [$site_short]" $site_short)
    site_short=$(to_machine_name "$site_short")
    local site_theme=$(ask "What is your theme machine name? [$site_short]" $site_short)
    site_theme=$(to_machine_name "$site_theme")
    local site_url=${site_short//_/-}
    site_url=$(ask "What is your site URL? [${site_url}.com]" $site_url)
    local org=$(ask "What is your organization name? [${site_short}_org] " $site_short)
    local org_short=$(to_machine_name "$org")
    local preserve_acquia_integration=Y
    preserve_acquia_integration=$(ask "Do you want to leave Acquia Cloud integration? [${preserve_acquia_integration}] " $preserve_acquia_integration)
    local remove_meta=Y
    remove_meta=$(ask "Do you want to remove all drupal-dev META information? (Y,n) [$remove_meta] " $remove_meta)
  fi

  echo
  local proceed=Y
  local proceed=$(ask "Proceed with initialising your project $site_name? (Y,n) [$proceed] " $proceed)

  if [ "$proceed" != "Y" ] ; then
    echo
    echo "Aborting project initialisation. No files were changed." && return;
  fi

  echo
  echo -n "Initialising project "

  rm README.md > /dev/null
  cp .dev/README.template.md README.md
  cp .dev/DEPLOYMENT.template.md DEPLOYMENT.md
  cp .dev/FAQs.template.md FAQs.md

  replace_string_content "mysitetheme" "$site_theme" "$CURDIR" && echo -n "."
  replace_string_content "myorg" "$org_short" "$CURDIR" && echo -n "."
  replace_string_content "mysiteurl" "$site_url" "$CURDIR" && echo -n "."
  replace_string_content "mysite" "$site_short" "$CURDIR" && echo -n "."
  replace_string_content "MYSITE" "$site_name" "$CURDIR" && echo -n "."

  replace_string_filename "mysitetheme" "$site_theme" "$CURDIR" && echo -n "."
  replace_string_filename "myorg" "$org_short" "$CURDIR" && echo -n "."
  replace_string_filename "mysite" "$site_short" "$CURDIR" && echo -n "."

  if [ "$preserve_acquia_integration" != "Y" ] ; then
    rm -Rf hooks > /dev/null
    rm scripts/acquia-download-backup.sh > /dev/null
    rm DEPLOYMENT.md > /dev/null
    remove_tags "META:ACQUIA" "$CURDIR" && echo -n "."
    remove_tags "META:DEPLOYMENT" "$CURDIR" && echo -n "."
  fi

  if [ "$remove_meta" == "Y" ] ; then
    remove_tags "META" "$CURDIR" && echo -n "."
  fi

  rm -Rf $CURDIR/.dev > /dev/null

  echo " complete"
}

ask() {
  local text=$1
  local default=${2:-}
  read -r -p "$text " response

  if [ "$response" != "" ] ; then
    echo $response
  else
   echo $default
  fi
}

replace_string_content() {
  local needle="$1"
  local replacement="$2"
  local dir="$3"
  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l $needle $dir | xargs sed -i '' "s@$needle@$replacement@g"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l $needle $dir | xargs sed -i "s@$needle@$replacement@g"
  fi
}

replace_string_filename() {
  local needle=$1
  local replacement=$2
  local dir=$3

  find $dir -depth -name "*$needle*" -execdir bash -c 'mv -i "$1" "${1//'$needle'/'$replacement'}"' bash {} \;
}

remove_tags() {
  local tag=$1
  local dir=$2

  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "$tag\]" $dir | xargs sed -i '' -e "/\[$tag\]/,/\[\/$tag\]/d"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "$tag\]" $dir | xargs sed -i -e "/\[$tag\]/,/\[\/$tag\]/d"
  fi
}

to_lower() {
  echo $(echo "$1" | tr '[:upper:]' '[:lower:]')
}

to_upper() {
  echo $(echo "$1" | tr '[:lower:]' '[:upper:]')
}

to_machine_name () {
  local text=$1
  text=${text//  / }
  text=${text// /_}
  text=${text//-/_}
  text=$(to_lower $text)
  echo $text
}

to_human_name () {
  local text=$1
  text=${text//  / }
  text=${text//_/ }
  text=${text//-/ }
  echo $text
}

main "$1"
