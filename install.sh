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
# shellcheck disable=SC2046
[ -f "${DST_DIR}/.env" ] && export $(grep -v '^#' "${DST_DIR}/.env" | xargs) && [ -f "${DST_DIR}/.env.local" ] && export $(grep -v '^#' "${DST_DIR}/.env.local" | xargs)

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
# Flag to allow writing downloaded files into local ignore for current repository.
DRUPALDEV_ALLOW_USE_LOCAL_IGNORE="${DRUPALDEV_ALLOW_USE_LOCAL_IGNORE:-1}"
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
# Temporary directory to download and expand files to.
DRUPALDEV_TMP_DIR="${DRUPALDEV_TMP_DIR:-$(mktemp -d)}"
# Internal flag to remove demo configuration.
DRUPALDEV_REMOVE_DEMO=${DRUPALDEV_REMOVE_DEMO:-1}

install(){
  if [ "${DRUPALDEV_IS_INTERACTIVE}" -eq 1 ]; then
   echo
   echo "**********************************************************************"
   echo "*                 WELCOME TO DRUPAL-DEV INSTALLER                    *"
   echo "**********************************************************************"
   echo "*                                                                    *"
   echo "* Please answer the questions below to install configuration         *"
   echo "* relevant to your site.                                             *"
   echo "*                                                                    *"
   echo "* Existing files are not modified until confirmed at the last        *"
   echo "* question.                                                          *"
   echo "*                                                                    *"
   echo "* Press Ctrl+C at any time to exit this installer.                   *"
   echo "*                                                                    *"
   echo "**********************************************************************"
   echo
  fi

  gather_answers "${DRUPALDEV_IS_INTERACTIVE}"

  local proceed=Y
  proceed=$(ask "> Proceed with installing Drupal-Dev into your project '$(get_value "name")'? (Y,n)" "${proceed}" "${DRUPALDEV_IS_INTERACTIVE}")

  if [ "${proceed}" != "Y" ] ; then
    echo
    echo "**********************************************************************"
    echo "*                                                                    *"
    echo "* Aborting project installation. No files were changed               *"
    echo "*                                                                    *"
    echo "**********************************************************************"
    return;
  fi

  if [ "${DRUPALDEV_LOCAL_REPO}" != "" ]; then
    echo "==> Downloading Drupal-Dev from local repository ${DRUPALDEV_LOCAL_REPO}"
    download_local "${DRUPALDEV_LOCAL_REPO}" "${DRUPALDEV_TMP_DIR}" "${DRUPALDEV_COMMIT}"
  else
    echo "==> Downloading Drupal-Dev from remote repository https://github.com/${DRUPALDEV_GH_ORG}/${DRUPALDEV_GH_PROJECT}"
    download_remote "${DRUPALDEV_TMP_DIR}" "${DRUPALDEV_GH_ORG}" "${DRUPALDEV_GH_PROJECT}" "${DRUPAL_VERSION}.x" "${DRUPALDEV_COMMIT}"
  fi

  [ ! -d "${DST_DIR}" ] && echo "==> Creating ${DST_DIR} directory" && mkdir -p "${DST_DIR}"

  if [ "${DRUPALDEV_INIT_REPO}" -eq 1 ]; then
    git_init "${DST_DIR}"
  fi

  process_stub "${DRUPALDEV_TMP_DIR}"

  copy_files "${DRUPALDEV_TMP_DIR}" "${DST_DIR}" "${DRUPALDEV_ALLOW_OVERRIDE}" "${DRUPALDEV_ALLOW_USE_LOCAL_IGNORE}"

  echo
  echo "**********************************************************************"
  echo "*                                                                    *"
  echo "* Finished installing Drupal-Dev.                                    *"
  echo "*                                                                    *"
  echo "* Please review changes and commit required files.                   *"
  echo "*                                                                    *"
  echo "**********************************************************************"
}

gather_answers(){
  local is_interactive=${1}

  gather_project_name

  expand_answer "name"                    "$(ask "What is your site name?"                            "$(capitalize "$(to_human_name "$(get_value "name")" )"           )"  "${is_interactive}" )"
  expand_answer "name" "$(capitalize "$(to_human_name "$(get_value "name")" )" )"
  name=$(get_value "name")
  expand_answer "machine_name"            "$(ask "What is your site machine name?"                    "$(to_machine_name "$(get_value       "machine_name")"            )"  "${is_interactive}" )"
  machine_name=$(get_value "machine_name")
  expand_answer "org"                     "$(ask "What is your organization name?"                    "$(get_value "org"                    "${name} Org"               )"  "${is_interactive}" )"
  expand_answer "org_machine_name"        "$(ask "What is your organization machine name?"            "$(to_machine_name "$(get_value       "org")"                     )"  "${is_interactive}" )"
  expand_answer "module_prefix"           "$(ask "What is your project-specific module prefix?"       "$(get_value "module_prefix"          "${machine_name}"           )"  "${is_interactive}" )"
  expand_answer "theme"                   "$(ask "What is your theme machine name?"                   "$(get_value "theme"                  "${machine_name}"           )"  "${is_interactive}" )"
  expand_answer "url"                     "$(ask "What is your site public URL?"                      "$(get_value "url"                    "${machine_name//_ /-}.com" )"  "${is_interactive}" )"
  expand_answer "preserve_acquia"         "$(ask "Do you want to keep Acquia Cloud integration?"      "$(get_value "preserve_acquia"        "Y"                         )"  "${is_interactive}" )"
  expand_answer "preserve_lagoon"         "$(ask "Do you want to keep Lagoon integration?"            "$(get_value "preserve_lagoon"        "Y"                         )"  "${is_interactive}" )"
  expand_answer "preserve_ftp"            "$(ask "Do you want to keep FTP integration?"               "$(get_value "preserve_ftp"           "n"                         )"  "${is_interactive}" )"
  expand_answer "remove_drupaldev_info"   "$(ask "Do you want to remove all Drupal-Dev information?"  "$(get_value "remove_drupaldev_info"  "Y"                         )"  "${is_interactive}" )"

  [ "${is_interactive}" -eq 1 ] && echo

  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && print_resolved_variables
}

# Special case to gather project name from different sources.
gather_project_name(){
  dir_name=$(basename "${CUR_DIR}")
  default=${PROJECT:-${dir_name}}
  DRUPALDEV_OPT_NAME=${DRUPALDEV_OPT_NAME:-${default}}

  export DRUPALDEV_OPT_NAME
}

download_local(){
  local src="${1}"
  local dst="${2}"
  local commit="${3:-HEAD}"
  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && echo "DEBUG: Downloading from the local repo"

  echo "==> Downloading Drupal-Dev at ref ${commit} from local repo ${src}"
  git --git-dir="${src}/.git" --work-tree="${src}" archive --format=tar "${commit}" \
    | tar xf - -C "${dst}"
}

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
  fi
}

process_stub(){
  local dir="${1}"

  # @note: String replacement may break symlinks to the file where replacement
  # occurs.
  replace_string_content  "yoursitetheme"  "$(get_value "theme")"             "${dir}" && bash -c "echo -n ."
  replace_string_content  "yourorg"        "$(get_value "org_machine_name")"  "${dir}" && bash -c "echo -n ."
  replace_string_content  "yoursiteurl"    "$(get_value "url")"               "${dir}" && bash -c "echo -n ."
  replace_string_content  "yoursite"       "$(get_value "machine_name")"      "${dir}" && bash -c "echo -n ."
  replace_string_content  "YOURSITE"       "$(get_value "name")"              "${dir}" && bash -c "echo -n ."

  replace_string_filename "yoursitetheme"   "$(get_value "theme")"            "${dir}" && bash -c "echo -n ."
  replace_string_filename "yourorg"         "$(get_value "org_machine_name")" "${dir}" && bash -c "echo -n ."
  replace_string_filename "yoursite"        "$(get_value "machine_name")"     "${dir}" && bash -c "echo -n ."

  if [ "$(get_value "preserve_acquia")" != "Y" ] ; then
    rm -Rf "${dir}"/hooks > /dev/null
    rm "${dir}"/scripts/download-backup-acquia.sh > /dev/null
    rm "${dir}"/.gitignore.artefact > /dev/null
    rm "${dir}"/DEPLOYMENT.md > /dev/null
    remove_special_comments_with_content "ACQUIA"       "${dir}" && bash -c "echo -n ."
    remove_special_comments_with_content "DEPLOYMENT"   "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_lagoon")" != "Y" ] ; then
    rm "${dir}"/drush/aliases.drushrc.php > /dev/null
    rm "${dir}"/.lagoon.yml > /dev/null
    remove_special_comments_with_content "LAGOON"       "${dir}" && bash -c "echo -n ."
  fi

  if [ "$(get_value "preserve_ftp")" != "Y" ] ; then
    remove_special_comments_with_content "FTP"         "${dir}" && bash -c "echo -n ."
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
  local allow_use_local_gitignore="${4:-}"

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
      # Add files to local ignore (not .gitignore), if all conditions pass:
      #  - flag is set to allow to add to local ignore
      #  - not already ignored
      #  - not currently tracked
      #  - not in a list of required files
      file_is_required="$(file_is_required "${relative_file}")"
      # @todo; Refactor return values.
      if [ "${allow_use_local_gitignore}" -eq 1 ] \
        && [ -d ./.git/ ] \
        && [ "$(git_file_is_ignored "${relative_file}")" != "0" ] \
        && [ "${file_is_tracked}" != "0" ] \
        && [ "${file_is_required}" != "0" ]; then
        git_add_to_local_ignore "${relative_file}"
      fi
    else
      echo "    Skipped file ${relative_file}"
    fi
  done

  popd > /dev/null || exit 1
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
    drupal-dev.sh
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

# Add specified file to local git ignore (not .gitgnore).
git_add_to_local_ignore(){
  if [ -d ./.git/ ]; then
    if ! grep -Fxq "${1}" ./.git/info/exclude; then
      echo "${1}" >> ./.git/info/exclude
      echo "    Added file ${1} to local git ignore"
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

# Expand answer into DRUPALDEV_OPT_ env variable.
expand_answer(){
  local name="${1}"
  local value="${2}"
  name="$(to_upper "${name}")"
  export DRUPALDEV_OPT_"${name}"="${value}"
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
    -l "${token}" "${dir}" \
    | LC_ALL=C.UTF-8  xargs sed "${sed_opts[@]}" -e "/${token}/d"
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
    -l "##### " "${dir}" \
    | xargs sed "${sed_opts[@]}" -e "s/##### //g"
}

# Get default value for a variable by name.
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

git_init(){
  local dir="${1}"
  [ -d "${dir}/.git" ] && return
  [ "${DRUPALDEV_DEBUG}" -ne 0 ] && echo "DEBUG: Initialising new git repository"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init > /dev/null
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

to_lower() {
  echo "${1}" | tr '[:upper:]' '[:lower:]'
}

to_upper() {
  echo "${1}" | tr '[:lower:]' '[:upper:]'
}

capitalize() {
  echo "$(tr '[:lower:]' '[:upper:]' <<< "${1:0:1}")${1:1}"
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
