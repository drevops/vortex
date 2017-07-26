#!/usr/bin/env bash
#
# Initialise drupal-dev project.
#
# Replaces placeholders with actual content.
#

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

to_lower() {
  echo $(echo "$1" | tr '[:upper:]' '[:lower:]')
}

to_upper() {
  echo $(echo "$1" | tr '[:lower:]' '[:upper:]')
}

shorten () {
  local text=$1
  text=${text//  / }
  text=${text// /_}
  text=$(to_lower $text)
  echo $text
}

questions() {
  CURDIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

  local site_name=$(ask "What is your site name?")
  [ "$site_name" == "" ] && echo "Site name is required" && exit 1
  local site_short=$(shorten "$site_name")
  site_short=$(ask "What is your site machine name? [$site_short]" $site_short)
  local site_theme=$(ask "What is your theme machine name? [$site_short]" $site_short)
  local site_url=${site_short//_/-}
  site_url=$(ask "What is your site URL? [$site_url]" $site_url)
  local org=$(ask "What is your organization name? [$site_short] " $site_short)
  local org_short=$(shorten "$org")

  echo
  echo -n "Replacing placeholders in files"
  replace_string_content "mysitetheme" "$site_theme" "$CURDIR" && echo -n "."
  replace_string_content "myorg" "$org_short" "$CURDIR" && echo -n "."
  replace_string_content "mysite" "$site_short" "$CURDIR" && echo -n "."
  replace_string_content "MYSITEURL" "$site_url" "$CURDIR" && echo -n "."
  replace_string_content "MYSITE" "$site_name" "$CURDIR" && echo -n "."

  replace_string_filename "mysitetheme" "$site_theme" "$CURDIR" && echo -n "."
  replace_string_filename "mysite" "$org_short" "$CURDIR" && echo -n "."

  echo "complete"
}

questions
