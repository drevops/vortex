#!/usr/bin/env bash
##
# @file
# Bats test helpers.
#
# shellcheck disable=SC2119,SC2120

# Guard against bats executing this twice
if [ -z "$TEST_PATH_INITIALIZED" ]; then
  export TEST_PATH_INITIALIZED=true

  # Rewrite environment PATH to make commands isolated.
  PATH=/usr/bin:/usr/local/bin:/bin:/usr/sbin:/sbin
  # Add BATS test directory to the PATH.
  PATH="$(dirname "${BATS_TEST_DIRNAME}"):$PATH"
fi

flunk(){
  { if [ "$#" -eq 0 ]; then cat -
    else echo "$@"
    fi
  } | sed "s:${BATS_TMPDIR}:\${BATS_TMPDIR}:g" >&2
  return 1
}

assert_success(){
  # shellcheck disable=SC2154
  if [ "${status}" -ne 0 ]; then
    format_error "command failed with exit status ${status}" | flunk
  elif [ "$#" -gt 0 ]; then
    assert_output "${1}"
  fi
}

assert_failure(){
  # shellcheck disable=SC2154
  if [ "${status}" -eq 0 ]; then
    format_error "expected failed exit status" | flunk
  elif [ "$#" -gt 0 ]; then
    assert_output "${1}"
  fi
}

assert_equal(){
  if [ "$1" != "$2" ]; then
    { echo "expected: ${1}"
      echo "actual:   ${2}"
    } | flunk
  fi
}

assert_contains(){
  local needle="${1}"
  local haystack="${2}"

  if echo "$haystack" | $(type -p ggrep grep | head -1) -F -- "$needle" > /dev/null; then
    return 0
  else
    format_error "String '${haystack}' does not contain '${needle}'" | flunk
  fi
}

assert_not_contains(){
  local needle="${1}"
  local haystack="${2}"

  if echo "$haystack" | $(type -p ggrep grep | head -1) -F -- "$needle" > /dev/null; then
    format_error "String '${haystack}' contains '${needle}', but should not" | flunk
  else
    return 0
  fi
}

assert_file_exists(){
  local file="${1}"
  if [ -f "${file}" ]; then
    return 0
  else
    format_error "File ${file} does not exist" | flunk
  fi
}

assert_file_not_exists(){
  local file="${1}"
  if [ -f "${file}" ]; then
    format_error "File ${file} exists, but should not" | flunk
  else
    return 0
  fi
}

assert_dir_exists(){
  local dir="${1}"

  if [ -d "${dir}" ] ; then
    return 0
  else
    format_error "Directory ${dir} does not exist" | flunk
  fi
}

assert_dir_not_exists(){
  local dir="${1}"

  if [ -d "${dir}" ] ; then
    format_error "Directory ${dir} exists, but should not" | flunk
  else
    return 0
  fi
}

assert_dir_empty(){
  local dir="${1}"
  assert_dir_exists "${dir}" || return 1

  if [ "$(ls -A "${dir}")" ]; then
    format_error "Directory ${dir} is not empty, but should be" | flunk
  else
    return 0
  fi
}

assert_dir_not_empty(){
  local dir="${1}"
  assert_dir_exists "${dir}"

  if [ "$(ls -A "${dir}")" ]; then
    return 0
  else
    format_error "Directory ${dir} is not empty, but should be" | flunk
  fi
}

assert_symlink_exists(){
  local file="${1}"

  if [ ! -h "${file}" ] && [ -f "${file}" ]; then
    format_error "Regular file ${file} exists, but symlink is expected" | flunk
  elif [ ! -h "${file}" ]; then
    format_error "Symlink ${file} does not exist" | flunk
  else
    return 0
  fi
}

assert_symlink_not_exists(){
  local file="${1}"

  if [ ! -h "${file}" ] && [ -f "${file}" ]; then
    return 0
  elif [ ! -h "${file}" ]; then
    return 0
  else
    format_error "Symlink ${file} exists, but should not" | flunk
  fi
}

assert_file_mode(){
  local file="${1}"
  local perm="${2}"
  assert_file_exists "${file}"

  if [ "$(uname)" == "Darwin" ]; then
    parsed=$(printf "%.3o\n" $(( $(stat -f '0%Lp' "$file") & ~0022 )))
  else
    parsed=$(printf "%.3o\n" $(( $(stat --printf '0%a' "$file") & ~0022 )))
  fi

  if [ "${parsed}" != "${perm}" ]; then
    format_error "File permissions for file ${file} is '${parsed}', but expected '${perm}'" | flunk
  else
    return 0
  fi
}

assert_file_contains(){
  local file="${1}"
  local string="${2}"
  assert_file_exists "${file}"

  contents="$(cat "${file}")"
  assert_contains "${string}" "${contents}"
}

assert_file_not_contains(){
  local file="${1}"
  local string="${2}"
  assert_file_exists "${file}"

  contents="$(cat "${file}")"
  assert_not_contains "${string}" "${contents}"
}

assert_dir_contains_string(){
  local dir="${1}"
  local string="${2}"

  assert_dir_exists "${dir}" || return 1

  run grep -rI --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${string}" "${dir}"

  if [ "${status}" -eq 0 ]; then
    return 0
  else
    format_error "Directory ${dir} does not contain a string '${string}'" | flunk
  fi
}

assert_dir_not_contains_string(){
  local dir="${1}"
  local string="${2}"

  assert_dir_exists "${dir}" || return 1

  run grep -rI --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${string}" "${dir}"

  if [ "${status}" -eq 0 ]; then
    format_error "Directory ${dir} contains string '${string}', but should not" | flunk
  else
    return 0
  fi
}

assert_git_repo(){
  local dir="${1}"

  assert_dir_exists "${dir}" || return 1

  if [ -d "${dir}/.git" ]; then
    return 0
  else
    format_error "Directory ${dir} exists, but it is not a git repository" | flunk
  fi
}

assert_not_git_repo(){
  local dir="${1}"

  assert_dir_exists "${dir}" || return 1

  if [ -d "${dir}/.git" ]; then
    format_error "Directory ${dir} exists and it is a git repository, but should not be" | flunk
  else
    return 0
  fi
}

assert_files_equal(){
  local file1="${1}"
  local file2="${2}"

  assert_file_exists "${file1}" || return 1
  assert_file_exists "${file2}" || return 1

  if cmp "${file1}" "${file2}"; then
    return 0
  else
    format_error "File ${file1} is not equal to file ${file2}" | flunk
  fi
}

assert_files_not_equal(){
  local file1="${1}"
  local file2="${2}"

  assert_file_exists "${file1}" || return 1
  assert_file_exists "${file2}" || return 1

  if cmp "${file1}" "${file2}"; then
    format_error "File ${file1} is equal to file ${file2}, but it should not be" | flunk
  else
    return 0
  fi
}

assert_empty(){
  if [ "${1}" == "" ] ; then
    return 0
  else
    format_error "String ${1} is not empty, but should be" | flunk
  fi
}

assert_not_empty(){
  if [ "${1}" == "" ] ; then
    format_error "String ${1} is empty, but should not be" | flunk
  else
    return 0
  fi
}

assert_output(){
  local expected
  if [ $# -eq 0 ]; then
    expected="$(cat -)"
  else
    expected="${1}"
  fi
  # shellcheck disable=SC2154
  assert_equal "${expected}" "${output}"
}

assert_output_contains(){
  local expected
  if [ $# -eq 0 ]; then
    expected="$(cat -)"
  else
    expected="${1}"
  fi
  # shellcheck disable=SC2154
  assert_contains "${expected}" "${output}"
}

assert_output_not_contains(){
  local expected
  if [ $# -eq 0 ]; then
    expected="$(cat -)"
  else
    expected="${1}"
  fi
  # shellcheck disable=SC2154
  assert_not_contains "${expected}" "${output}"
}

random_string(){
  local ret
  ret=$(hexdump -n 16 -v -e '/1 "%02X"' /dev/urandom)
  echo "${ret}"
}

prepare_fixture_dir(){
  local dir="${1}"
  rm -Rf "${dir}" > /dev/null
  mkdir -p "${dir}"
  assert_dir_exists "${dir}"
}

# Format error message with optional output, if present.
format_error(){
  local message="${1}"
  echo
  echo "ERROR: ${message}"
  echo

  if [ "${output}" != "" ]; then
    echo "----------------------------------------"
    echo "$BATS_TMPDIR"
    echo "${output}"
    echo "----------------------------------------"
  fi
}

# Run bats with `--tap` option to debug the output.
debug(){
  echo "${1}" >&3
}
