#!/usr/bin/env bash
# shellcheck disable=SC2063
#
# Initialise Drupal-Dev project.
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
  CUR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

  local site_name
  local site_short
  local site_theme
  local site_url
  local org
  local org_short
  local preserve_acquia_integration
  local preserve_lagoon_integration
  local remove_meta

  if [ "${1}" != "" ] ; then
    site_name=$(to_human_name "${1}")
    site_short=$(to_machine_name "${1}")
    site_theme=${site_short}
    site_url=${site_short//_/-}
    org=${site_short}
    org_short=$(to_machine_name "$org")
    preserve_acquia_integration=Y
    preserve_lagoon_integration=Y
    remove_meta=Y
  else
    site_name=$(to_human_name "$(basename "${CUR_DIR}")")
    site_name=$(ask "What is your site name? [${site_name}]" "${site_name}")
    site_short=$(to_machine_name "${site_name}")
    site_short=$(ask "What is your site machine name? [${site_short}]" "${site_short}")
    site_short=$(to_machine_name "${site_short}")
    site_theme=$(ask "What is your theme machine name? [${site_short}]" "${site_short}")
    site_theme=$(to_machine_name "${site_theme}")
    site_url=${site_short//_/-}
    site_url=$(ask "What is your site URL? [${site_url}.com]" "${site_url}")
    org=$(ask "What is your organization name? [${site_short}_org] " "${site_short}")
    org_short=$(to_machine_name "$org")
    preserve_acquia_integration=Y
    preserve_acquia_integration=$(ask "Do you want to keep Acquia Cloud integration? [${preserve_acquia_integration}] " $preserve_acquia_integration)
    preserve_lagoon_integration=Y
    preserve_lagoon_integration=$(ask "Do you want to keep Lagoon integration? [${preserve_lagoon_integration}] " $preserve_lagoon_integration)
    remove_meta=Y
    remove_meta=$(ask "Do you want to remove all Drupal-Dev META information? (Y,n) [$remove_meta] " $remove_meta)
  fi

  echo
  local proceed=Y
  proceed=$(ask "Proceed with initialising your project ${site_name}? (Y,n) [$proceed] " $proceed)

  if [ "$proceed" != "Y" ] ; then
    echo
    echo "Aborting project initialisation. No files were changed." && return;
  fi

  echo
  bash -c "echo -n Initialising project ${site_name}"

  rm README.md > /dev/null
  cp .drupal-dev/README.template.md README.md
  cp .drupal-dev/DEPLOYMENT.template.md DEPLOYMENT.md
  cp .drupal-dev/FAQs.template.md FAQs.md

  replace_string_content "mysitetheme" "${site_theme}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_content "myorg" "${org_short}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_content "mysiteurl" "${site_url}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_content "mysite" "${site_short}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_content "MYSITE" "${site_name}" "${CUR_DIR}" && bash -c "echo -n ."

  replace_string_filename "mysitetheme" "${site_theme}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_filename "myorg" "${org_short}" "${CUR_DIR}" && bash -c "echo -n ."
  replace_string_filename "mysite" "${site_short}" "${CUR_DIR}" && bash -c "echo -n ."

  if [ "$preserve_acquia_integration" != "Y" ] ; then
    rm -Rf hooks > /dev/null
    rm scripts/download-backup-acquia.sh > /dev/null
    rm DEPLOYMENT.md > /dev/null
    remove_tags_with_content "META:ACQUIA" "${CUR_DIR}" && bash -c "echo -n ."
    remove_tags_with_content "META:DEPLOYMENT" "${CUR_DIR}" && bash -c "echo -n ."
  fi

  if [ "$preserve_lagoon_integration" != "Y" ] ; then
    rm drush/aliases.drushrc.php > /dev/null
    rm .lagoon.yml > /dev/null
    remove_tags_with_content "META:LAGOON" "${CUR_DIR}" && bash -c "echo -n ."
  fi

  if [ "$remove_meta" == "Y" ] ; then
    remove_tags_with_content "META" "${CUR_DIR}" && bash -c "echo -n ."
    remove_tags "META" "${CUR_DIR}"
  fi

  enable_commented_code "${CUR_DIR}"

  rm -Rf "${CUR_DIR}"/.drupal-dev > /dev/null

  bash -c "echo complete"
}

ask() {
  local text="${1}"
  local default="${2:-}"
  read -r -p "${text} " response

  if [ "${response}" != "" ] ; then
    echo "${response}"
  else
   echo "${default}"
  fi
}

replace_string_content() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"
  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${needle}" "${dir}" | xargs sed -i '' "s@$needle@$replacement@g"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${needle}" "${dir}" | xargs sed -i "s@$needle@$replacement@g"
  fi
}

replace_string_filename() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"

  find "${dir}" -depth -name "*${needle}*" -execdir bash -c 'mv -i "${1}" "${1//'"$needle"'/'"$replacement"'}"' bash {} \;
}

remove_tags_with_content() {
  local tag="${1}"
  local dir="${2}"

  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "$tag\]" "${dir}" | xargs sed -i '' -e "/\[$tag\]/,/\[\/$tag\]/d"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "$tag\]" "${dir}" | xargs sed -i -e "/\[$tag\]/,/\[\/$tag\]/d"
  fi
}

remove_tags() {
  local tag="${1}"
  local dir="${2}"

  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "\[$tag" "${dir}" | xargs sed -i '' -e "/\[$tag/d"
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "\[\/$tag" "${dir}" | xargs sed -i '' -e "/\[\/$tag/d"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "\[$tag" "${dir}" | xargs sed -i -e "/\[$tag/d"
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "\[\/$tag" "${dir}" | xargs sed -i -e "/\[\/$tag/d"
  fi
}

enable_commented_code() {
  local dir="${1}"

  if [ "$(uname)" == "Darwin" ]; then
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "##### " "${dir}" | xargs sed -i '' -e "s/##### //g"
  else
    grep -r --exclude '*.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "##### " "${dir}" | xargs sed -i -e "s/##### //g"
  fi
}

to_lower() {
  echo "${1}" | tr '[:upper:]' '[:lower:]'
}

to_upper() {
  echo "${1}" | tr '[:lower:]' '[:upper:]'
}

to_machine_name () {
  local text="${1}"
  text=${text//  / }
  text=${text// /_}
  text=${text//-/_}
  text="$(to_lower "${text}")"
  echo "${text}"
}

to_human_name () {
  local text="${1}"
  text=${text//  / }
  text=${text//_/ }
  text=${text//-/ }
  echo "${text}"
}

main "${1}"
