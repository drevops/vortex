#!/usr/bin/env bash
##
# Drupal-Dev installer.
#
# Usage:
# curl -L https://raw.githubusercontent.com/integratedexperts/drupal-dev/8.x/install | bash
# or
# curl -L https://raw.githubusercontent.com/integratedexperts/drupal-dev/8.x/install | bash -s -- /path/to/destination/directory

{ # Ensures the entire script is downloaded.

# shellcheck disable=SC2235
([ "${1}" == "--interactive" ] || [ "${1}" == "-i" ]) && DRUPALDEV_IS_INTERACTIVE=1 && shift

CUR_DIR=$(pwd)
# Destination directory, that can be overridden with the first argument to this script.
DST_DIR="${DST_DIR:-${CUR_DIR}}"
DST_DIR=${1:-${DST_DIR}}

# Load variables from .env and .env.local files, if they exist.
# Note that .env.local is read only if .env exists.
# This would be the case for an 'update' operation.
# shellcheck disable=SC2046
[ -f "${DST_DIR}/.env" ] && [ -s "${DST_DIR}/.env" ] && export $(grep -v '^#' "${DST_DIR}/.env" | xargs) && if [ -f "${DST_DIR}/.env.local" ] && [ -s "${DST_DIR}/.env.local" ]; then export $(grep -v '^#' "${DST_DIR}/.env.local" | xargs); fi

# Project name.
PROJECT="${PROJECT:-}"
# Drupal version to download files for.
DRUPAL_VERSION="${DRUPAL_VERSION:-8}"
# Flag to run this install in interactive mode with user input.
DRUPALDEV_IS_INTERACTIVE="${DRUPALDEV_IS_INTERACTIVE:-0}"
# Flag to init git repository.
DRUPALDEV_INIT_REPO="${DRUPALDEV_INIT_REPO:-1}"
# Flag to allow override existing committed files.
DRUPALDEV_ALLOW_OVERRIDE="${DRUPALDEV_ALLOW_OVERRIDE:-0}"
# Path to local Drupal-Dev repository. If not provided - remote will be used.
DRUPALDEV_LOCAL_REPO="${DRUPALDEV_LOCAL_REPO:-}"
# Organisation name to download the files from.
DRUPALDEV_GH_ORG="${DRUPALDEV_GH_ORG:-integratedexperts}"
# Project name to download the files from.
DRUPALDEV_GH_PROJECT="${DRUPALDEV_GH_PROJECT:-drupal-dev}"
# Optional commit to download. If not provided, latest release will be downloaded.
DRUPALDEV_COMMIT="${DRUPALDEV_COMMIT:-}"
# Flag to display debug information.
DRUPALDEV_DEBUG="${DRUPALDEV_DEBUG:-0}"
# Flag to proceed.
DRUPALDEV_PROCEED="${DRUPALDEV_PROCEED:-1}"
# Temporary directory to download and expand files to.
DRUPALDEV_TMP_DIR="${DRUPALDEV_TMP_DIR:-$(mktemp -d)}"
# Internal flag to remove demo configuration.
DRUPALDEV_REMOVE_DEMO=${DRUPALDEV_REMOVE_DEMO:-1}
# Internal version of Drupal-Dev. Discovered during installation.
DRUPALDEV_VERSION="${DRUPALDEV_VERSION:-${DRUPAL_VERSION}.x}"

install(){
  check_requirements

  if [ "${DRUPALDEV_IS_INTERACTIVE}" -eq 1 ]; then
    print_header_interactive "${DRUPALDEV_ALLOW_OVERRIDE}" "${DRUPALDEV_COMMIT}"
  else
    print_header_silent "${DRUPALDEV_ALLOW_OVERRIDE}" "${DRUPALDEV_COMMIT}"
  fi

  gather_answers "${DRUPALDEV_IS_INTERACTIVE}"

  local proceed=Y
  proceed=$(ask "Proceed with installing Drupal-Dev into your project '$(get_value "name")'? (Y,n)" "${proceed}" "${DRUPALDEV_IS_INTERACTIVE}")
  { [ "$(to_upper "${proceed}")" != "Y" ] || [ "${DRUPALDEV_PROCEED}" -ne 1 ]; } && print_abort && return;

  download

  prepare_destination "${DST_DIR}" "${DRUPALDEV_INIT_REPO}"

  process_stub "${DRUPALDEV_TMP_DIR}"

  copy_files "${DRUPALDEV_TMP_DIR}" "${DST_DIR}" "${DRUPALDEV_ALLOW_OVERRIDE}"

  # Reload variables from .env and .env.local files.
  # shellcheck disable=SC2046
  [ -f "${DST_DIR}/.env" ] && [ -s "${DST_DIR}/.env" ] && export $(grep -v '^#' "${DST_DIR}/.env" | xargs) && if [ -f "${DST_DIR}/.env.local" ] && [ -s "${DST_DIR}/.env.local" ]; then export $(grep -v '^#' "${DST_DIR}/.env.local" | xargs); fi

  process_demo

  print_footer
}

check_requirements(){
  command_exists "grep"
  command_exists "sed"
  command_exists "head"
  command_exists "curl"
  command_exists "basename"
  command_exists "dirname"
  command_exists "git"
  command_exists "tar"
  command_exists "cut"
  command_exists "cat"
  command_exists "composer"
}

download(){
  if [ "${DRUPALDEV_LOCAL_REPO}" != "" ]; then
    echo "==> Downloading Drupal-Dev from local repository ${DRUPALDEV_LOCAL_REPO}"
    download_local "${DRUPALDEV_LOCAL_REPO}" "${DRUPALDEV_TMP_DIR}" "${DRUPALDEV_COMMIT}"
  else
    echo "==> Downloading Drupal-Dev from remote repository https://github.com/${DRUPALDEV_GH_ORG}/${DRUPALDEV_GH_PROJECT}"
    download_remote "${DRUPALDEV_TMP_DIR}" "${DRUPALDEV_GH_ORG}" "${DRUPALDEV_GH_PROJECT}" "${DRUPAL_VERSION}.x" "${DRUPALDEV_COMMIT}"
  fi
}

prepare_destination(){
  local dir="${1}"
  local init_git_repo="${2}"

  [ ! -d "${dir}" ] && echo "==> Creating ${dir} directory" && mkdir -p "${dir}"

  if [ "${init_git_repo}" -eq 1 ]; then
    git_init "${dir}"
  fi
}

#
# Gather answers from the user input or from the environment.
#
gather_answers(){
  local is_interactive=${1}

  set_answer "name"                     "What is your site name?"                                                     "${is_interactive}"
  set_answer "machine_name"             "What is your site machine name?"                                             "${is_interactive}"
  set_answer "org"                      "What is your organization name"                                              "${is_interactive}"
  set_answer "org_machine_name"         "What is your organization machine name?"                                     "${is_interactive}"
  set_answer "module_prefix"            "What is your project-specific module prefix?"                                "${is_interactive}"
  set_answer "profile"                  "What is your custom profile machine name (leave empty to not use profile)?"  "${is_interactive}"
  set_answer "theme"                    "What is your theme machine name?"                                            "${is_interactive}"
  set_answer "url"                      "What is your site public URL?"                                               "${is_interactive}"
  set_answer "fresh_install"            "Do you want to use fresh Drupal installation for every build?"               "${is_interactive}"
  set_answer "preserve_deployment"      "Do you want to keep deployment configuration?"                               "${is_interactive}"
  set_answer "preserve_acquia"          "Do you want to keep Acquia Cloud integration?"                               "${is_interactive}"
  set_answer "preserve_lagoon"          "Do you want to keep Amazee.io Lagoon integration?"                           "${is_interactive}"
  set_answer "preserve_ftp"             "Do you want to keep FTP integration?"                                        "${is_interactive}"
  set_answer "preserve_dependenciesio"  "Do you want to keep dependencies.io integration?"                            "${is_interactive}"
  set_answer "preserve_doc_comments"    "Do you want to keep detailed documentation in comments?"                     "${is_interactive}"
  set_answer "remove_drupaldev_info"    "Do you want to remove all Drupal-Dev information?"                           "${is_interactive}"

  print_summary "${is_interactive}"

  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && print_resolved_variables
}

set_answer(){
  local name="${1}"
  local question="${2}"
  local is_interactive=${3}
  local default

  # Explicitly set options always take precedence.
  option_exists "${name}" && return

  default="$(get_default_value "${name}")"
  default="$(discover_value "${name}" "${default}")"

  answer=$(ask "${question}" "${default}" "${is_interactive}")
  answer=$(normalise_answer "${name}" "${answer}")

  set_option "${name}" "${answer}"
}

#
# Download from local.
#
download_local(){
  local src="${1}"
  local dst="${2}"
  local commit="${3:-HEAD}"
  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && echo "DEBUG: Downloading from the local repo"

  echo "==> Downloading Drupal-Dev at ref ${commit} from local repo ${src}"
  git --git-dir="${src}/.git" --work-tree="${src}" archive --format=tar "${commit}" \
    | tar xf - -C "${dst}"
}

#
# Download from remote.
#
download_remote(){
  local dst="${1}"
  local org="${2}"
  local project="${3}"
  local release_prefix="${4}"
  local commit="${5:-}"
  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && echo "DEBUG: Downloading from the remote repo"

  if [ "${commit}" != "" ]; then
    echo "==> Downloading Drupal-Dev at commit ${commit}"
    curl -# -L "https://github.com/${org}/${project}/archive/${commit}.tar.gz" \
      | tar xzf - -C "${dst}" --strip 1
  else
    # Find the latest version for specified drupal version.
    # Print found version.
    # Download archive of this version.
    release=$(
      curl -s -L "https://api.github.com/repos/${org}/${project}/releases" \
        | grep "\"tag_name\": \"${release_prefix}" \
        | head -n1 \
        | sed -E 's/.*"([^"]+)".*/\1/'
      )
    [ "${release}" == "" ] && error "Unable to find the latest release of Drupal-Dev"

    echo "==> Downloading the latest version ${release} of Drupal-Dev"
    curl -# -L "https://github.com/${DRUPALDEV_GH_ORG}/${DRUPALDEV_GH_PROJECT}/archive/${release}.tar.gz" \
      | tar xzf - -C "${DRUPALDEV_TMP_DIR}" --strip 1
    DRUPALDEV_VERSION="${release}"
  fi
}

#
# Replace all tokens and show summary.
#
process_stub(){
  local dir="${1}"

  if [ "$(get_value "profile")" == "" ] || [ "$(get_value "profile")" == "n" ]; then
    rm -Rf "${dir}"/docroot/profiles/custom/your_site_profile > /dev/null
    replace_string_content "docroot/profiles/custom/your_site_profile," "" "${dir}"
    remove_special_comments_with_content "PROFILE" "${dir}" && bash -c "echo -n ."
  elif is_core_profile "$(get_value "profile")"; then
    # For core profiles - remove custom profile, but preserve the information
    # about used core profile.
    rm -Rf "${dir}"/docroot/profiles/custom/your_site_profile > /dev/null
    replace_string_content "docroot/profiles/custom/your_site_profile," "" "${dir}"
    replace_string_content  "your_site_profile"  "$(get_value "profile")" "${dir}" && bash -c "echo -n ."
  else
    replace_string_content  "your_site_profile"  "$(get_value "profile")" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "fresh_install")" != "Y" ] ; then
    remove_special_comments_with_content "FRESH_INSTALL" "${dir}" && bash -c "echo -n ."
  else
    remove_special_comments_with_content "!FRESH_INSTALL" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_deployment")" != "Y" ] ; then
    rm "${dir}"/.gitignore.deployment > /dev/null
    rm "${dir}"/DEPLOYMENT.md > /dev/null
    rm "${dir}"/.circleci/deploy.sh > /dev/null
    remove_special_comments_with_content "DEPLOYMENT" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_acquia")" != "Y" ] ; then
    rm -Rf "${dir}"/hooks > /dev/null
    rm "${dir}"/scripts/download-backup-acquia.sh > /dev/null
    remove_special_comments_with_content "ACQUIA" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_lagoon")" != "Y" ] ; then
    rm "${dir}"/drush/aliases.drushrc.php > /dev/null
    rm "${dir}"/.lagoon.yml > /dev/null
    remove_special_comments_with_content "LAGOON" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_ftp")" != "Y" ] ; then
    remove_special_comments_with_content "FTP" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_dependenciesio")" != "Y" ] ; then
    rm "${dir}"/dependencies.yml > /dev/null
    remove_special_comments_with_content "DEPENDENCIESIO" "${dir}" && bash -c "echo -n ."
  fi

  # @note: String replacement may break symlinks to the file where replacement
  # occurs.
  replace_string_content  "your_site_theme"   "$(get_value "theme")"            "${dir}" && bash -c "echo -n ."
  replace_string_content  "your_org"          "$(get_value "org_machine_name")" "${dir}" && bash -c "echo -n ."
  replace_string_content  "YOURORG"           "$(get_value "org")"              "${dir}" && bash -c "echo -n ."
  replace_string_content  "your-site-url"     "$(get_value "url")"              "${dir}" && bash -c "echo -n ."
  replace_string_content  "your_site"         "$(get_value "machine_name")"     "${dir}" && bash -c "echo -n ."
  machine_name="$(get_value "machine_name")"
  machine_name_hyphenated="${machine_name/_/-}"
  replace_string_content  "your-site"         "${machine_name_hyphenated}"      "${dir}" && bash -c "echo -n ."
  replace_string_content  "YOURSITE"          "$(get_value "name")"             "${dir}" && bash -c "echo -n ."

  machine_name_camel_cased="$(to_camelcase "${machine_name}")"
  replace_string_content  "YourSite"          "${machine_name_camel_cased}"     "${dir}" && bash -c "echo -n ."
  replace_string_filename "YourSite"          "${machine_name_camel_cased}"     "${dir}" && bash -c "echo -n ."

  replace_string_content  "DRUPALDEV_VERSION_URLENCODED"  "${DRUPALDEV_VERSION/-/--}" "${dir}" && bash -c "echo -n ."
  replace_string_content  "DRUPALDEV_VERSION" "${DRUPALDEV_VERSION}"            "${dir}" && bash -c "echo -n ."

  replace_string_filename "your_site_theme"   "$(get_value "theme")"            "${dir}" && bash -c "echo -n ."
  replace_string_filename "your_org"          "$(get_value "org_machine_name")" "${dir}" && bash -c "echo -n ."
  replace_string_filename "your_site"         "$(get_value "machine_name")"     "${dir}" && bash -c "echo -n ."

  if [ "$(get_value "preserve_doc_comments")" == "Y" ] ; then
    # Replace special "#:" comments with normal "#" comments.
    replace_string_content "#:" "#" "${dir}"
  else
    remove_special_comments "${dir}" "#:"
  fi

  if [ "$(get_value "remove_drupaldev_info")" == "Y" ] ; then
    # Handle code required for Drupal-Dev maintenance.
    remove_special_comments_with_content "DRUPAL-DEV" "${dir}" && bash -c "echo -n ."
    # Handle code required for the demo of Drupal-Dev.
    [ "${DRUPALDEV_REMOVE_DEMO}" -eq 1 ] && remove_special_comments_with_content "DEMO" "${dir}" && bash -c "echo -n ."
    # Remove other unhandled comments.
    remove_special_comments "${dir}" "#;<"
    remove_special_comments "${dir}" "#;>"
    # Remove all other comments.
    remove_special_comments "${dir}"
  fi

  enable_commented_code "${dir}"
}

copy_files(){
  local src="${1}"
  local dst="${2}"
  local allow_override="${3:-}"

  pushd "${dst}" > /dev/null || exit 1

  targets=()
  # Collect files.
  while IFS=  read -r -d $'\0'; do
      targets+=("$REPLY")
  done < <(find "${src}" -type f -print0)
  # Collect symlinks separately, to later ensure that they point to existing
  # files.
  while IFS=  read -r -d $'\0'; do
      targets+=("$REPLY")
  done < <(find "${src}" -type l -print0)

  for file in "${targets[@]}"; do
    parent="$(dirname "${file}")"
    relative_file=${file#"${src}/"}
    relative_parent="$(dirname "${relative_file}")"

    # Parent DIR for file ${relative_file} is a symlink - skipping.
    if [ -L "${parent}" ]; then
      continue
    fi

    echo "==> Processing file ${relative_file}"

    if [ "$(file_is_internal "${relative_file}")" -eq 1 ]; then
      echo "    Skipping internal Drupal-Dev file ${relative_file}"
      continue
    fi

    # Only process untracked files - allows to have project-specific overrides
    # being committed and not overridden OR tracked files are allowed to
    # be overridden.
    file_is_tracked="$(git_file_is_tracked "${relative_file}")"
    if [ "${file_is_tracked}" -ne 0 ] || [ "${allow_override}" -ne 0 ]; then
      # Copy file if it exists in Drupal-Dev (some files may not exist as they
      # are only local exclude records).
      if [ -e "${file}" ]; then
        mkdir -p "${relative_parent}"
        if [ -d "${file}" ]; then
          # Symlink files can be directories, so handle them differently.
          cp -fR "${file}" "${relative_parent}/"
          echo "    Copied dir ${relative_file}"
        elif [ -L "${file}" ]; then
          cp -a "${file}" "${relative_parent}/"
          echo "    Copied symlink ${file} to ${relative_file}"
        else
          cp -f "${file}" "${relative_file}"
          echo "    Copied file ${relative_file}"
        fi
      fi
    else
      echo "    Skipped file ${relative_file}"
    fi
  done

  popd > /dev/null || exit 1
}

is_core_profile(){
  local profile="${1}"

  local core_profiles=(
    "standard"
    "minimal"
    "testing"
    "demo_umami"
  )

  in_array "${profile}" "${core_profiles[@]}"
}

#
# Check that Drupal-Dev is installed for this project.
#
is_installed(){
  [ ! -f "README.md" ] && return 1
  grep -q "badge/Drupal--Dev-" "README.md"
}

#
# Process demo configuration.
#
process_demo(){
  # Download demo database if the variable exists.
  if [ "${DEMO_DB+x}" ] && [ ! -f .data/db.sql ] ; then
    echo "==> No database file found in .data/db.sql. Downloading DEMO database from ${DEMO_DB}"
    mkdir -p .data && curl -L "${DEMO_DB}" -o .data/db.sql
  fi
}

################################################################################
#                                DEFAULTS                                      #
################################################################################

# Get default value
get_default_value(){
  local name="${1}"
  local default="${2}"

  get_value_from_callback "get_default_value" "${name}" "${default}"
}

get_default_value__name(){
  echo "${PROJECT:-$(basename "${DST_DIR}")}"
}

get_default_value__machine_name(){
  to_machine_name "$(get_value "name")"
}

get_default_value__org(){
  echo "$(get_value "name") Org"
}

get_default_value__org_machine_name(){
  to_machine_name "$(get_value "org")"
}

get_default_value__module_prefix(){
  get_value "machine_name"
}

get_default_value__profile(){
  echo "n"
}

get_default_value__theme(){
  to_lower "$(get_value "name")"
}

get_default_value__url(){
  local machine_name
  machine_name="$(get_value "machine_name")"
  machine_name="${machine_name/_/-}"
  machine_name="${machine_name/ /-}"
  echo "${machine_name}.com"
}

get_default_value__fresh_install(){
  echo "n"
}

get_default_value__preserve_deployment(){
  echo "Y"
}

get_default_value__preserve_acquia(){
  echo "Y"
}

get_default_value__preserve_lagoon(){
  echo "Y"
}

get_default_value__preserve_ftp(){
  echo "n"
}

get_default_value__preserve_dependenciesio(){
  echo "Y"
}

get_default_value__preserve_doc_comments(){
  echo "Y"
}

get_default_value__remove_drupaldev_info(){
  echo "Y"
}

################################################################################
#                                NORMALISERS                                   #
################################################################################

normalise_answer(){
  local name="${1}"
  local default="${2}"

  get_value_from_callback "normalise_answer" "${name}" "${default}" "${default}"
}

normalise_answer__name(){
  capitalize "$(to_human_name "${1}")"
}

normalise_answer__machine_name(){
  to_machine_name "${1}"
}

normalise_answer__org_machine_name(){
  to_machine_name "${1}"
}

normalise_answer__module_prefix(){
  to_machine_name "${1}"
}

normalise_answer__profile(){
  local profile
  profile=$(to_machine_name "${1}")
  { [ "${profile}" == "" ] || [ "${profile}" == "n" ]; } && profile="standard"
  echo "${profile}"
}

normalise_answer__theme(){
  to_machine_name "${1}"
}

normalise_answer__url(){
  local url="${1}"
  url="${url/_/-}"
  url="${url/ /-}"
  echo "${url}"
}

normalise_answer__fresh_install(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_deployment(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_acquia(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_lagoon(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_ftp(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_dependenciesio(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__preserve_doc_comments(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

normalise_answer__remove_drupaldev_info(){
  [ "${1}" != "Y" ] && echo "n" || echo "Y"
}

################################################################################
#                              DISCOVERIES                                     #
################################################################################

# Discover value from the environment.
discover_value(){
  local name="${1}"
  local default="${2}"

  if ! is_installed; then
    echo "${default}"
    return
  fi

  get_value_from_callback "discover_value" "${name}" "${default}"
}

discover_value__name(){
  if  [ -f "composer.json" ]; then
    composer config description | grep "Drupal [78] implementation" | cut -c 28- | sed -n 's/\(.*\) for .*/\1/p'
  fi
}

discover_value__org(){
  [ -f "composer.json" ] && composer config description | grep "Drupal [78] implementation" | cut -c 28- | sed -n 's/.* for \(.*\)/\1/p'
}

discover_value__machine_name(){
  [ -f "composer.json" ] && composer config name | sed 's/.*\///'
}

discover_value__org_machine_name(){
  [ -f "composer.json" ] && composer config name | sed 's/\/.*//'
}

discover_value__module_prefix(){
  if ls -d docroot/modules/custom/*_core > /dev/null; then
    # shellcheck disable=SC2012
    ls -d docroot/modules/custom/*_core | head -n 1 | cut -c 24- | sed -n 's/_core//p'
    return
  elif ls -d docroot/sites/all/modules/custom/*_core > /dev/null; then
    # shellcheck disable=SC2012
    ls -d docroot/sites/all/modules/custom/*_core | head -n 1 | cut -c 34- | sed -n 's/_core//p'
    return
  fi
}

discover_value__profile(){
  if [ -d "docroot/profiles/custom" ] && ls -d docroot/profiles/custom/* > /dev/null; then
    # shellcheck disable=SC2012
    ls -d docroot/profiles/custom/* | head -n 1 | cut -c 25-
    return
  fi
}

discover_value__theme(){
  if ls -d docroot/themes/custom/* > /dev/null; then
    # shellcheck disable=SC2012
    ls -d docroot/themes/custom/* | head -n 1 | cut -c 23-
    return
  elif ls -d docroot/sites/all/themes/custom/* > /dev/null; then
    # shellcheck disable=SC2012
    ls -d docroot/sites/all/themes/custom/* | head -n 1 | cut -c 33-
    return
  fi
}

discover_value__url(){
  if [ -f "docroot/sites/default/settings.php" ]; then
    # Extract from string $config['stage_file_proxy.settings']['origin'] = 'http://your-site-url/';
    # shellcheck disable=SC2002
    cat docroot/sites/default/settings.php \
      | grep "config\['stage_file_proxy.settings'\]\['origin'\]" \
      | sed 's/ //g' \
      | cut -c 48- \
      | sed "s/'//g" \
      | sed 's/http\://g' \
      | sed "s/\///g" \
      | sed 's/;//g'
  fi
}

discover_value__fresh_install(){
  [ ! -f ".ahoy.yml" ] && echo "N" && return
  { [ -f ".ahoy.yml" ] && file_contains ".ahoy.yml" "download-db:"; } && echo "N" || echo "Y"
}

discover_value__preserve_deployment(){
  [ -f ".gitignore.deployment" ] && echo "Y" || echo "N"
}

discover_value__preserve_acquia(){
  { [ -d "hooks" ] || [ -f "scripts/download-backup-acquia.sh" ]; } && echo "Y" || echo "N"
}

discover_value__preserve_lagoon(){
  [ -f ".lagoon.yml" ] && echo "Y" || echo "N"
}

discover_value__preserve_ftp(){
  { [ -f ".ahoy.yml" ] && file_contains ".ahoy.yml" "FTP_HOST"; } && echo "Y" || echo "N"
}

discover_value__preserve_dependenciesio(){
  [ -f "dependencies.yml" ] && echo "Y" || echo "N"
}

discover_value__preserve_doc_comments(){
  { [ -f ".ahoy.yml" ] && file_contains ".ahoy.yml" "Ahoy configuration file."; } && echo "Y" || echo "N"
}

discover_value__remove_drupaldev_info(){
  dir_contains_string "${DST_DIR}" "#;< DRUPAL-DEV" && echo "N" || echo "Y"
}

################################################################################
#                              DISPLAYS                                        #
################################################################################

print_header_interactive(){
  local is_override="${1:-0}"
  local commit="${2:-}"

  echo
  echo "**********************************************************************"
  echo "          WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER                *"
  echo "**********************************************************************"
  echo "*                                                                    *"
  if [ "${commit}" == "" ]; then
    echo "* This will install the latest version of Drupal-Dev into your       *"
    echo "* project.                                                           *"
  else
    echo "* This will install Drupal-Dev into your project at commit           *"
    echo "* ${commit}                           *"
  fi
  echo "*                                                                    *"
  if is_installed; then
    echo "* It looks like Drupal-Dev is already installed into this project.   *"
    echo "*                                                                    *"
  fi
  echo "* Please answer the questions below to install configuration         *"
  echo "* relevant to your site.                                             *"
  echo "*                                                                    *"
  if [ "${is_override}" -eq 1 ]; then
    echo "* ATTENTION! RUNNING IN UPDATE MODE                                  *"
    echo "* Existing committed files will be modified. You will need to        *"
    echo "* resolve changes manually.                                          *"
  else
    echo "* Existing files will not be modified until confirmed at the last    *"
    echo "* question.                                                          *"
  fi
  echo "*                                                                    *"
  echo "* Press Ctrl+C at any time to exit this installer.                   *"
  echo "*                                                                    *"
  echo "**********************************************************************"
  echo
}

print_header_silent(){
  local is_override="${1:-0}"
  local commit="${2:-}"

  echo
  echo "**********************************************************************"
  echo "*            WELCOME TO DRUPAL-DEV SILENT INSTALLER                  *"
  echo "**********************************************************************"
  echo "*                                                                    *"
  if [ "${commit}" == "" ]; then
    echo "* This will install the latest version of Drupal-Dev into your       *"
    echo "* project.                                                           *"
  else
    echo "* This will install Drupal-Dev into your project at commit           *"
    echo "* ${commit}                           *"
  fi
  echo "*                                                                    *"
  if is_installed; then
    echo "* It looks like Drupal-Dev is already installed into this project.   *"
    echo "*                                                                    *"
  fi
  echo "* Drupal-Dev installer will try to discover the settings from the    *"
  echo "* environment and will install configuration relevant to your site.  *"
  echo "*                                                                    *"
  if [ "${is_override}" -eq 1 ]; then
    echo "* ATTENTION! RUNNING IN UPDATE MODE                                  *"
    echo "* Existing committed files will be modified. You will need to        *"
    echo "* resolve changes manually.                                          *"
  else
    echo "* Existing committed files will not be modified.                     *"
  fi
  echo "*                                                                    *"
  echo "**********************************************************************"
}

print_summary(){
  local is_interactive="${1:-0}"

  if [ "${is_interactive}" -eq 1 ]; then
    echo
    echo "**********************************************************************"
    echo "*                       INSTALLATION SUMMARY                         *"
    echo "**********************************************************************"
  fi
  echo "  Name:                          $(get_value "name")"
  echo "  Machine name:                  $(get_value "machine_name")"
  echo "  Organisation:                  $(get_value "org")"
  echo "  Organisation machine name:     $(get_value "org_machine_name")"
  echo "  Module prefix:                 $(get_value "module_prefix")"
  echo "  Profile:                       $(get_value "profile")"
  echo "  Theme name:                    $(get_value "theme")"
  echo "  URL:                           $(get_value "url")"
  echo "  Fresh install for every build: $(format_yes_no "$(get_value "fresh_install")")"
  echo "  Deployment:                    $(format_enabled "$(get_value "preserve_deployment")")"
  echo "  Acquia integration:            $(format_enabled "$(get_value "preserve_acquia")")"
  echo "  Lagoon integration:            $(format_enabled "$(get_value "preserve_lagoon")")"
  echo "  FTP integration:               $(format_enabled "$(get_value "preserve_ftp")")"
  echo "  dependencies.io integration:   $(format_enabled "$(get_value "preserve_dependenciesio")")"
  echo "  Preserve docs in comments:     $(format_yes_no "$(get_value "preserve_doc_comments")")"
  echo "  Remove Drupal-Dev comments:    $(format_yes_no "$(get_value "remove_drupaldev_info")")"
  echo "**********************************************************************"
  echo
}

print_footer(){
  echo
  echo "**********************************************************************"
  echo "*                                                                    *"
  echo "* Finished installing Drupal-Dev.                                    *"
  echo "*                                                                    *"
  echo "* Please review changes and commit required files.                   *"
  echo "*                                                                    *"
  echo "* Do not forget to run 'composer update --lock' before committing    *"
  echo "* changes.                                                           *"
  echo "*                                                                    *"
  echo "**********************************************************************"
}

print_abort(){
  echo
  echo "**********************************************************************"
  echo "*                                                                    *"
  echo "* Aborting project installation. No files were changed               *"
  echo "*                                                                    *"
  echo "**********************************************************************"
}

# Helper to print all resolved variables.
print_resolved_variables(){
  echo
  echo "==================== RESOLVED VARIABLES ===================="
  vars=$(compgen -A variable | grep DRUPALDEV_)
  vars=("CUR_DIR" "DST_DIR" "PROJECT" "DRUPAL_VERSION" "${vars[@]}")
  # shellcheck disable=SC2068
  for var in ${vars[@]};
  do
    echo "${var}"="$(eval "echo \$$var")"
  done
  echo "============================================================"
}

################################################################################
#                              UTILITIES                                       #
################################################################################

#
# Helper to get the value from callback.
#
get_value_from_callback(){
  local callback_prefix="${1}"
  shift
  local name="${1}"
  shift
  local default="${1}"
  shift
  local callback_args=("$@")
  local callback="${callback_prefix}"__"${name}"
  local value

  if is_function "${callback}"; then
    value=$("${callback}" "${callback_args[@]}")

    if [ "${value}" != "" ]; then
      echo "${value}"
      return
    fi
  fi

  echo "${default}"
}

#
# in_array "needle" "${haystack[@]}"
#
in_array(){
  local needle="${1}"
  shift
  for item in "${@}"; do
    [ "${item}" == "${needle}" ] && return 0
  done
  return 1
}

# Check if specified file is tracked by git.
git_file_is_tracked(){
  if [ -d ./.git/ ]; then
    git ls-files --error-unmatch "${1}" &>/dev/null
    echo $?
  else
    echo 1
  fi
}

# Check if specified file is ignored by git.
git_file_is_ignored(){
  if [ -d ./.git/ ]; then
    git check-ignore "${1}"
    echo $?
  else
    echo 1
  fi
}

# Check if specified file is internal Drupal-Dev file.
file_is_internal(){
  local file="${1}"
  local files=(
    install.sh
    LICENSE
    .circleci/drupal_dev-test.sh
    .circleci/drupal_dev-test-deployment.sh
    tests/bats
  )

  for i in "${files[@]}"; do
    if [ "${file#${i}}" != "${file}" ]; then
      echo 1
      return
    fi
  done

  echo 0
}

# Check if specified file is in the list of required files.
file_is_required(){
  local file="${1}"
  local files=(
    README.md
    .circleci/config.yml
    docroot/sites/default/settings.php
    docroot/sites/default/services.yml
  )

  for i in "${files[@]}"; do
    if [ "${i}" == "${file}" ]; then
      echo 0
      return
    fi
  done

  echo 1
}

# Add specified file to local git exclude (not .gitgnore).
git_add_to_local_exclude(){
  if [ -d ./.git/ ]; then
    mkdir -p ./.git/info >/dev/null
    [ ! -f "./.git/info/exclude" ] && touch ./.git/info/exclude >/dev/null
    if ! grep -Fxq "${1}" ./.git/info/exclude; then
      echo "# /${1} file below is excluded by Drupal-Dev" >> ./.git/info/exclude
      echo "/${1}" >> ./.git/info/exclude
      echo "    Added file ${1} to local git exclude"
    fi
  fi
}

# Remove specified file from local git exclude (not .gitgnore).
git_remove_from_local_exclude(){
  local path="${1}"
  path="/${path}"
  if [ -f "./.git/info/exclude" ]; then
    if grep -Fq "${path}" "./.git/info/exclude"; then
      path="${path//\//\\/}"
      remove_ignore_comments "./.git/info" "# ${path} file below is excluded by Drupal-Dev"
      echo "    Removed file ${1} from local git exclude"
    fi
  fi
}

# Ask user a question, if is interactive, and return a value.
# If not interactive - return efault value.
# - text: Text to sho in the question.
# - default: Default value.
# - is_interactive: Flag to show user input as interactive. Defaults to true.
#
ask() {
  local text="${1}"
  local default="${2}"
  local is_interactive="${3:-1}"

  [ "${is_interactive}" -ne 1 ] && echo "${default}" && return

  text="> ${text} [${default}]"

  read -r -p "${text} " response

  if [ "${response}" != "" ] ; then
    echo "${response}"
  else
   echo "${default}"
  fi
}

set_option(){
  local name="${1}"
  local value="${2}"
  name="$(to_upper "${name}")"

  export DRUPALDEV_OPT_"${name}"="${value}"
}

get_option(){
  local name="${1}"
  name="$(to_upper "${name}")"
  eval "var=\$DRUPALDEV_OPT_${name}"
  echo "${var}"
}

option_exists(){
  local name="${1}"
  name="$(to_upper "${name}")"
  eval "var=\$DRUPALDEV_OPT_${name}"

  [ -n "${var}" ]
}

replace_string_content() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "${needle}" "${dir}" \
    | xargs sed "${sed_opts[@]}" "s@$needle@$replacement@g"
}

replace_string_filename() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"

  find "${dir}" -depth -name "*${needle}*" -execdir bash -c 'mv -i "${1}" "${1//'"$needle"'/'"$replacement"'}"' bash {} \;
}

remove_special_comments() {
  local dir="${1}"
  local token="${2:-#;}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "${token}" "${dir}" \
    | LC_ALL=C.UTF-8  xargs sed "${sed_opts[@]}" -e "/${token}/d"
}

# Remove ignore comments.
# The difference with remove_special_comments() is that this function removes
# $token line and one more line that follows it.
remove_ignore_comments() {
  local dir="${1}"
  local token="${2:-#;}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "${token}" "${dir}" \
    | LC_ALL=C.UTF-8  xargs sed "${sed_opts[@]}" -e "/${token}/{N;d;}"
}

remove_special_comments_with_content() {
  local token="${1}"
  local dir="${2}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "#;> $token" "${dir}" \
    | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/#;< $token/,/#;> $token/d"
}

enable_commented_code() {
  local dir="${1}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "##### " "${dir}" \
    | xargs sed "${sed_opts[@]}" -e "s/##### //g"
}

# Get value for a variable by name.
# - name: Name of the environment variable (uppercase suffix for DRUPALDEV_OPT_).
# - default: Default value to return. If not set, defaults to calculated project
#   name $DRUPALDEV_OPT_NAME.
get_value(){
  local name="${1}"
  local default="${2:-${DRUPALDEV_OPT_NAME}}"

  suffix=$(to_upper "${name}")
  existing_value=$(eval "echo \${DRUPALDEV_OPT_${suffix}}")

  if [ "${existing_value}" != "" ]; then
    echo "${existing_value}"
    return
  fi

  echo "${default}"
}

is_function(){
  type -t "${1}" >/dev/null
}

file_contains(){
  local file="${1}"
  local string="${2}"
  [ ! -f "${file}" ] && return 1

  contents="$(cat "${file}")"
  string_contains "${string}" "${contents}"
}

string_contains(){
  local needle="${1}"
  local haystack="${2}"

  if echo "$haystack" | $(type -p ggrep grep | head -1) -F -- "$needle" > /dev/null; then
    return 0
  else
    return 1
  fi
}

dir_contains_string(){
  local dir="${1}"
  local string="${2}"

  [ -d "${dir}" ] || return 1

  grep -qrI \
    --exclude-dir='.git' \
    --exclude-dir='.idea' \
    --exclude-dir='vendor' \
    --exclude-dir='node_modules' \
    --exclude-dir=".data" \
    -l "${string}" "${dir}"
}

git_init(){
  local dir="${1}"
  [ -d "${dir}/.git" ] && return
  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && echo "DEBUG: Initialising new git repository"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init > /dev/null
}

command_exists(){
  command -v "${1}" > /dev/null || { echo "Command ${1} does not exist in current environment" && exit 1; }
}

to_lower() {
  echo "${1}" | tr '[:upper:]' '[:lower:]'
}

to_upper() {
  echo "${1}" | tr '[:lower:]' '[:upper:]'
}

capitalize() {
  echo "$(tr '[:lower:]' '[:upper:]' <<< "${1:0:1}")${1:1}"
}

to_camelcase(){
  local string="${1}"

  IFS=" " read -r -a chunks <<< "${string//_/ }"

  for ((i=0; i<${#chunks[@]}; i++)); do
    chunks[$i]=$(capitalize "${chunks[$i]}")
  done

  echo "${chunks[@]}" | tr -d ' '
}

format_enabled(){
  [ "${1}" == "Y" ] && echo "Enabled" || echo "Disabled"
}

format_yes_no(){
  [ "${1}" == "Y" ] && echo "Yes" || echo "No"
}

to_machine_name () {
  local text="${1}"
  text="${text//  / }"
  text="${text// /_}"
  text="${text//-/_}"
  text="$(to_lower "${text}")"
  echo "${text}"
}

to_human_name () {
  local text="${1}"
  text="${text//  / }"
  text="${text//_/ }"
  text="${text//-/ }"
  echo "${text}"
}

install "$@"

} # Ensures the entire script is downloaded.
