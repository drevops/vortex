#!/usr/bin/env bash
##
# DrevOps installer.
#
# Usage:
# curl -L https://raw.githubusercontent.com/drevops/drevops/8.x/install | bash
# or
# curl -L https://raw.githubusercontent.com/drevops/drevops/8.x/install | bash -s -- /path/to/destination/directory

# shellcheck disable=SC2015

{ # Ensures the entire script is downloaded.

set -e

# shellcheck disable=SC2235
([ "${1}" == "--interactive" ] || [ "${1}" == "-i" ]) && DREVOPS_IS_INTERACTIVE=1 && shift

CUR_DIR=$(pwd)
# Destination directory, that can be overridden with the first argument to this script.
DST_DIR="${DST_DIR:-${CUR_DIR}}"
DST_DIR=${1:-${DST_DIR}}

# Load variables from .env file.
# This reload is required to get latest variable values during 'update' operation.
# shellcheck disable=SC1090,SC1091
[ -f "${DST_DIR}/.env" ] && t=$(mktemp) && export -p > "$t" && set -a && . "${DST_DIR}/.env" && set +a && . "$t" && rm "$t" && unset t

# Project name.
PROJECT="${PROJECT:-}"
# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"
# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"
# Drupal version to download files for.
DRUPAL_VERSION="${DRUPAL_VERSION:-8}"
# Flag to run this install in interactive mode with user input.
DREVOPS_IS_INTERACTIVE="${DREVOPS_IS_INTERACTIVE:-0}"
# Flag to init git repository.
DREVOPS_INIT_REPO="${DREVOPS_INIT_REPO:-1}"
# Flag to allow override existing committed files.
DREVOPS_ALLOW_OVERRIDE="${DREVOPS_ALLOW_OVERRIDE:-0}"
# Path to local DrevOps repository. If not provided - remote will be used.
DREVOPS_LOCAL_REPO="${DREVOPS_LOCAL_REPO:-}"
# Organisation name to download the files from.
DREVOPS_GH_ORG="${DREVOPS_GH_ORG:-drevops}"
# Project name to download the files from.
DREVOPS_GH_PROJECT="${DREVOPS_GH_PROJECT:-drevops}"
# Optional commit to download. If not provided, latest release will be downloaded.
DREVOPS_COMMIT="${DREVOPS_COMMIT:-}"
# Flag to display scripts debug information.
DREVOPS_DEBUG="${DREVOPS_DEBUG:-0}"
# Flag to display install debug information.
DREVOPS_INSTALL_DEBUG="${DREVOPS_INSTALL_DEBUG:-0}"
# Flag to proceed.
DREVOPS_PROCEED="${DREVOPS_PROCEED:-1}"
# Temporary directory to download and expand files to.
DREVOPS_TMP_DIR="${DREVOPS_TMP_DIR:-$(mktemp -d)}"
# Internal version of DrevOps. Discovered during installation.
DREVOPS_VERSION="${DREVOPS_VERSION:-${DRUPAL_VERSION}.x}"
# Internal flag to enforce DEMO mode. If not set, the demo mode will be discovered automatically.
DREVOPS_DEMO=${DREVOPS_DEMO:-}

install(){
  check_requirements

  if [ "${DREVOPS_IS_INTERACTIVE}" -eq 1 ]; then
    print_header_interactive "${DREVOPS_ALLOW_OVERRIDE}" "${DREVOPS_COMMIT}"
  else
    print_header_silent "${DREVOPS_ALLOW_OVERRIDE}" "${DREVOPS_COMMIT}"
  fi

  gather_answers "${DREVOPS_IS_INTERACTIVE}"

  local proceed=Y
  proceed=$(ask "Proceed with installing DrevOps into your project '$(get_value "name")'? (Y,n)" "${proceed}" "${DREVOPS_IS_INTERACTIVE}")
  { [ "$(to_upper "${proceed}")" != "Y" ] || [ "${DREVOPS_PROCEED}" -ne 1 ]; } && print_abort && return;

  download

  prepare_destination "${DST_DIR}" "${DREVOPS_INIT_REPO}"

  process_stub "${DREVOPS_TMP_DIR}"

  copy_files "${DREVOPS_TMP_DIR}" "${DST_DIR}" "${DREVOPS_ALLOW_OVERRIDE}"

  # Reload variables from .env file.
  # shellcheck disable=SC1090,SC1091
  [ -f "${DST_DIR}/.env" ] && t=$(mktemp) && export -p > "$t" && set -a && . "${DST_DIR}/.env" && set +a && . "$t" && rm "$t" && unset t

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
  if [ "${DREVOPS_LOCAL_REPO}" != "" ]; then
    echo "==> Downloading DrevOps from local repository ${DREVOPS_LOCAL_REPO}"
    download_local "${DREVOPS_LOCAL_REPO}" "${DREVOPS_TMP_DIR}" "${DREVOPS_COMMIT}"
  else
    echo "==> Downloading DrevOps from remote repository https://github.com/${DREVOPS_GH_ORG}/${DREVOPS_GH_PROJECT}"
    download_remote "${DREVOPS_TMP_DIR}" "${DREVOPS_GH_ORG}" "${DREVOPS_GH_PROJECT}" "${DRUPAL_VERSION}.x" "${DREVOPS_COMMIT}"
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
  set_answer "remove_drevops_info"      "Do you want to remove all DrevOps information?"                              "${is_interactive}"

  print_summary "${is_interactive}"

  [ -n "${DREVOPS_INSTALL_DEBUG}" ] && print_resolved_variables
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
  [ -n "${DREVOPS_INSTALL_DEBUG}" ] && echo "DEBUG: Downloading from the local repo"

  echo -n "==> Downloading DrevOps at ref ${commit} from local repo ${src}"
  git --git-dir="${src}/.git" --work-tree="${src}" archive --format=tar "${commit}" \
    | tar xf - -C "${dst}"
  echo -n " ."
  echo " done"
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
  [ -n "${DREVOPS_INSTALL_DEBUG}" ] && echo "DEBUG: Downloading from the remote repo"

  if [ "${commit}" != "" ]; then
    echo -n "==> Downloading DrevOps at commit ${commit}"
    curl -sS -L "https://github.com/${org}/${project}/archive/${commit}.tar.gz" \
      | tar xzf - -C "${dst}" --strip 1
    echo -n " ."
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
    [ "${release}" == "" ] && error "Unable to find the latest release of DrevOps"

    echo -n "==> Downloading the latest version ${release} of DrevOps"
    curl -sS -L "https://github.com/${DREVOPS_GH_ORG}/${DREVOPS_GH_PROJECT}/archive/${release}.tar.gz" \
      | tar xzf - -C "${DREVOPS_TMP_DIR}" --strip 1
    echo -n " ."
    DREVOPS_VERSION="${release}"
  fi

  echo " done"
}

#
# Replace all tokens and show summary.
#
process_stub(){
  local dir="${1}"

  echo -n "==> Replacing tokens "

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
    remove_special_comments_with_content "DEPLOYMENT" "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_acquia")" != "Y" ] ; then
    rm -Rf "${dir}"/hooks > /dev/null
    rm "${dir}"/scripts/drevops/download-db-acquia.sh > /dev/null
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

  replace_string_content  "DREVOPS_VERSION_URLENCODED"  "${DREVOPS_VERSION/-/--}" "${dir}" && bash -c "echo -n ."
  replace_string_content  "DREVOPS_VERSION" "${DREVOPS_VERSION}"            "${dir}" && bash -c "echo -n ."

  replace_string_filename "your_site_theme"   "$(get_value "theme")"            "${dir}" && bash -c "echo -n ."
  replace_string_filename "your_org"          "$(get_value "org_machine_name")" "${dir}" && bash -c "echo -n ."
  replace_string_filename "your_site"         "$(get_value "machine_name")"     "${dir}" && bash -c "echo -n ."

  if [ "$(get_value "preserve_doc_comments")" == "Y" ] ; then
    # Replace special "#: " comments with normal "#" comments.
    replace_string_content "#:" "#" "${dir}"
  else
    remove_special_comments "${dir}" "#:"
  fi

  # Reload variables.
  # shellcheck disable=SC2046
  [ -f "${dir}/.env" ] && [ -s "${dir}/.env" ] && export $(grep -v '^#' "${dir}/.env" | xargs)

  # Discover demo mode. Has to happen after all other tokens replaced.
  DREVOPS_DEMO=$(is_demo)

  # Remove code required for the demo of DrevOps.
  if [ "${DREVOPS_DEMO}" != "1" ]; then
    remove_special_comments_with_content "DEMO" "${dir}"
    bash -c "echo -n ."
  fi

  if [ "$(get_value "remove_drevops_info")" == "Y" ] ; then
    # Remove code required for DrevOps maintenance.
    remove_special_comments_with_content "DREVOPS" "${dir}" && bash -c "echo -n ."

    # Remove other unhandled comments.
    remove_special_comments "${dir}" "#;<"
    remove_special_comments "${dir}" "#;>"

    # Remove all other comments.
    remove_special_comments "${dir}"
  fi

  # Remove DrevOps internal files.
  rm -Rf "${dir}"/docs > /dev/null

  enable_commented_code "${dir}"

  echo " done"
}

is_demo(){
  # Perform auto-discovery only if the mode was not explicitly defined.
  if [ "$DREVOPS_DEMO" == "" ]; then
    # Only if using canonical-db workflow, DB url is one of the demo URLs
    # and there is no database dump file.
    if [ "$(get_value "fresh_install")" == "n" ] && [ -z "${CURL_DB_URL##*.dist.sql.md*}" ]  && [ ! -f "${DB_DIR}"/"${DB_FILE}" ] ; then
      DREVOPS_DEMO=1
    else
      DREVOPS_DEMO=0
    fi
  fi

  echo $DREVOPS_DEMO
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

  echo -n "==> Copying files "
  for file in "${targets[@]}"; do
    parent="$(dirname "${file}")"
    relative_file=${file#"${src}/"}
    relative_parent="$(dirname "${relative_file}")"

    # Parent DIR for file ${relative_file} is a symlink - skipping.
    if [ -L "${parent}" ]; then
      continue
    fi

    [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "==> Processing file ${relative_file}" || echo -n "."

    if [ "$(file_is_internal "${relative_file}")" -eq 1 ]; then
      [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "    Skipping internal DrevOps file ${relative_file}" || echo -n "."
      continue
    fi

    # Only process untracked files - allows to have project-specific overrides
    # being committed and not overridden OR tracked files are allowed to
    # be overridden.
    file_is_tracked="$(git_file_is_tracked "${relative_file}")"
    if [ "${file_is_tracked}" -ne 0 ] || [ "${allow_override}" -ne 0 ]; then
      # Copy file if it exists in DrevOps (some files may not exist as they
      # are only local exclude records).
      if [ -e "${file}" ]; then
        mkdir -p "${relative_parent}"
        if [ -d "${file}" ]; then
          # Symlink files can be directories, so handle them differently.
          cp -fR "${file}" "${relative_parent}/" 2>/dev/null
          [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "    Copied dir ${relative_file}" || echo -n "."
        elif [ -L "${file}" ]; then
          cp -a "${file}" "${relative_parent}/" 2>/dev/null
          [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "    Copied symlink ${file} to ${relative_file}" || echo -n "."
        else
          cp -f "${file}" "${relative_file}" 2>/dev/null
          [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "    Copied file ${relative_file}" || echo -n "."
        fi
      fi
    else
      [ "${DREVOPS_INSTALL_DEBUG}" -eq 1 ] && echo "    Skipped file ${relative_file}" || echo -n "."
    fi
  done

  echo " done"

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
# Check that DrevOps is installed for this project.
#
is_installed(){
  [ ! -f "README.md" ] && return 1
  grep -q "badge/DrevOps-" "README.md"
}

#
# Process demo configuration.
#
process_demo(){
  { [ "${DREVOPS_SKIP_DEMO+x}" ] || [ "${DREVOPS_DEMO}" == "0" ]; } && return 0

  # Download demo database if this is not a fresh install, the DB file does
  # not exist and the demo DB variable exists.
  echo "==> No database dump file found in .data directory. Downloading DEMO database from ${CURL_DB_URL}"
  mkdir -p "${DST_DIR}"/"${DB_DIR}" && curl -L "${CURL_DB_URL}" -o "${DST_DIR}"/"${DB_DIR}"/"${DB_FILE}"
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

get_default_value__remove_drevops_info(){
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

normalise_answer__remove_drevops_info(){
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
  if  [ -f "${DST_DIR}/composer.json" ]; then
    composer --working-dir="${DST_DIR}" config description | grep "Drupal [78] implementation" | cut -c 28- | sed -n 's/\(.*\) for .*/\1/p' || true
  fi
}

discover_value__org(){
  [ -f "${DST_DIR}/composer.json" ] && composer --working-dir="${DST_DIR}" config description | grep "Drupal [78] implementation" | cut -c 28- | sed -n 's/.* for \(.*\)/\1/p' || true
}

discover_value__machine_name(){
  [ -f "${DST_DIR}/composer.json" ] && composer --working-dir="${DST_DIR}" config name | sed 's/.*\///' || true
}

discover_value__org_machine_name(){
  [ -f "${DST_DIR}/composer.json" ] && composer --working-dir="${DST_DIR}" config name | sed 's/\/.*//' || true
}

discover_value__module_prefix(){
  if ls -d "${DST_DIR}"/docroot/modules/custom/*_core > /dev/null 2>&1; then
    for dir in "${DST_DIR}"/docroot/modules/custom/*_core; do
      basename "${dir}"
      return
    done
  elif ls -d "${DST_DIR}"/docroot/sites/all/modules/custom/*_core > /dev/null 2>&1; then
    for dir in "${DST_DIR}"/docroot/sites/all/modules/custom/*_core; do
      basename "${dir}"
      return
    done
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
  if ls -d "${DST_DIR}"/docroot/themes/custom/* > /dev/null 2>&1; then
    for dir in "${DST_DIR}"/docroot/themes/custom/*; do
      basename "${dir}"
      return
    done
  elif ls -d "${DST_DIR}"/docroot/sites/all/themes/custom/* > /dev/null 2>&1; then
    for dir in "${DST_DIR}"/docroot/sites/all/themes/custom/*; do
      basename "${dir}"
      return
    done
  fi
}

discover_value__url(){
  if [ -f "${DST_DIR}/docroot/sites/default/settings.php" ]; then
    # Extract from string $config['stage_file_proxy.settings']['origin'] = 'http://your-site-url/';
    # shellcheck disable=SC2002
    cat "${DST_DIR}"/docroot/sites/default/settings.php \
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
  [ ! -f "${DST_DIR}/.ahoy.yml" ] && echo "N" && return
  { [ -f "${DST_DIR}/.ahoy.yml" ] && file_contains "${DST_DIR}/.ahoy.yml" "download-db:"; } && echo "N" || echo "Y"
}

discover_value__preserve_deployment(){
  [ -f "${DST_DIR}/.gitignore.deployment" ] && echo "Y" || echo "N"
}

discover_value__preserve_acquia(){
  { [ -d "${DST_DIR}/hooks" ] || [ -f "${DST_DIR}/scripts/drevops/download-db-acquia.sh" ]; } && echo "Y" || echo "N"
}

discover_value__preserve_lagoon(){
  [ -f "${DST_DIR}/.lagoon.yml" ] && echo "Y" || echo "N"
}

discover_value__preserve_ftp(){
  { [ -f "${DST_DIR}/.env" ] && file_contains "${DST_DIR}/.env" "DOWNLOAD_DB_TYPE=ftp"; } && echo "Y" || echo "N"
}

discover_value__preserve_dependenciesio(){
  [ -f "${DST_DIR}/dependencies.yml" ] && echo "Y" || echo "N"
}

discover_value__preserve_doc_comments(){
  { [ -f "${DST_DIR}/.ahoy.yml" ] && file_contains "${DST_DIR}/.ahoy.yml" "Ahoy configuration file."; } && echo "Y" || echo "N"
}

discover_value__remove_drevops_info(){
  dir_contains_string "${DST_DIR}" "#;< DREVOPS" && echo "N" || echo "Y"
}

################################################################################
#                              DISPLAYS                                        #
################################################################################

print_header_interactive(){
  local is_override="${1:-0}"
  local commit="${2:-}"

  echo
  echo "**********************************************************************"
  echo "          WELCOME TO DREVOPS INTERACTIVE INSTALLER                   *"
  echo "**********************************************************************"
  echo "*                                                                    *"
  if [ "${commit}" == "" ]; then
    echo "* This will install the latest version of DrevOps into your          *"
    echo "* project.                                                           *"
  else
    echo "* This will install DrevOps into your project at commit              *"
    echo "* ${commit}                              *"
  fi
  echo "*                                                                    *"
  if is_installed; then
    echo "* It looks like DrevOps is already installed into this project.      *"
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
  echo "*            WELCOME TO DREVOPS SILENT INSTALLER                     *"
  echo "**********************************************************************"
  echo "*                                                                    *"
  if [ "${commit}" == "" ]; then
    echo "* This will install the latest version of DrevOps into your          *"
    echo "* project.                                                           *"
  else
    echo "* This will install DrevOps into your project at commit              *"
    echo "* ${commit}                           *"
  fi
  echo "*                                                                    *"
  if is_installed; then
    echo "* It looks like DrevOps is already installed into this project.      *"
    echo "*                                                                    *"
  fi
  echo "* DrevOps installer will try to discover the settings from the       *"
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
  echo "  Remove DrevOps comments:       $(format_yes_no "$(get_value "remove_drevops_info")")"
  echo "**********************************************************************"
  echo
}

print_footer(){
  echo
  echo "**********************************************************************"
  echo "*                                                                    *"
  echo "* Finished installing DrevOps.                                       *"
  echo "*                                                                    *"
  echo "* Review changes and commit required files.                          *"
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
  vars=$(compgen -A variable | grep DREVOPS_)
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

# Check if specified file is internal DrevOps file.
file_is_internal(){
  local file="${1}"
  local files=(
    install.sh
    LICENSE
    .circleci/drevops-test.sh
    .circleci/drevops-test-deployment.sh
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
      echo "# /${1} file below is excluded by DrevOps" >> ./.git/info/exclude
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
      remove_ignore_comments "./.git/info" "# ${path} file below is excluded by DrevOps"
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

  export DREVOPS_OPT_"${name}"="${value}"
}

get_option(){
  local name="${1}"
  name="$(to_upper "${name}")"
  eval "var=\$DREVOPS_OPT_${name}"
  echo "${var}"
}

option_exists(){
  local name="${1}"
  name="$(to_upper "${name}")"
  eval "var=\$DREVOPS_OPT_${name}"

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
    --exclude-dir="scripts/drevops" \
    -l "${needle}" "${dir}" \
    | xargs sed "${sed_opts[@]}" "s@$needle@$replacement@g" || true
}

replace_string_filename() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"

  # Find all dirs, remove existing new dirs and move them to new names.
  # This will make sure that any files relying on the parent _renamed_ dirs
  # are also copied.
  # shellcheck disable=SC2044
  for file in $(find "${dir}" -depth -type d -name "*${needle}*"); do
    newname="${file//$needle/$replacement}"
    if [ -d "${newname}" ]; then
      rm -rf "${newname}"
    fi
    mv "${file}" "${newname}"
  done;

  # Rename files.
  # shellcheck disable=SC2044
  for file in $(find "${dir}" -depth -type f -name "*${needle}*"); do
    newname="${file//$needle/$replacement}"
    mkdir -p "$(dirname "${newname}")"
    mv "${file}" "${newname}"
  done;
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
    --exclude-dir="scripts/drevops" \
    -l "${token}" "${dir}" \
    | LC_ALL=C.UTF-8  xargs sed "${sed_opts[@]}" -e "/${token}/d" || true
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
    --exclude-dir="scripts/drevops" \
    -l "${token}" "${dir}" \
    | LC_ALL=C.UTF-8  xargs sed "${sed_opts[@]}" -e "/${token}/{N;d;}" || true
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
    --exclude-dir="scripts/drevops" \
    -l "#;> $token" "${dir}" \
    | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/#;< $token/,/#;> $token/d" || true
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
    --exclude-dir="scripts/drevops" \
    -l "##### " "${dir}" \
    | xargs sed "${sed_opts[@]}" -e "s/##### //g" || true
}

# Get value for a variable by name.
# - name: Name of the environment variable (uppercase suffix for DREVOPS_OPT_).
# - default: Default value to return. If not set, defaults to calculated project
#   name $DREVOPS_OPT_NAME.
get_value(){
  local name="${1}"
  local default="${2:-${DREVOPS_OPT_NAME}}"

  suffix=$(to_upper "${name}")
  existing_value=$(eval "echo \${DREVOPS_OPT_${suffix}}")

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
    --exclude-dir="scripts/drevops" \
    -l "${string}" "${dir}"
}

git_init(){
  local dir="${1}"
  [ -d "${dir}/.git" ] && return
  [ -n "${DREVOPS_INSTALL_DEBUG}" ] && echo "DEBUG: Initialising new git repository"
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
