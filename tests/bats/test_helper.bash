#!/usr/bin/env bash
##
# @file
# Bats test helpers.
#

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
    flunk "command failed with exit status ${status}"
  elif [ "$#" -gt 0 ]; then
    assert_output "${1}"
  fi
}

assert_failure(){
  # shellcheck disable=SC2154
  if [ "${status}" -eq 0 ]; then
    flunk "expected failed exit status"
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
    { echo "string:   ${haystack}"
      echo "contains: ${needle}"
    } | flunk
  fi
}

assert_not_contains(){
  local needle="${1}"
  local haystack="${2}"

  if echo "$haystack" | $(type -p ggrep grep | head -1) -F -- "$needle" > /dev/null; then
    { echo "string:   ${haystack}"
      echo "contains: ${needle}"
    } | flunk
  else
    return 0
  fi
}

assert_file_exists(){
  local file="${1}"
  [ ! -f "${file}" ] && flunk "File ${file} does not exist"
  return 0
}

assert_file_not_exists(){
  local file="${1}"
  [ -f "${file}" ] && flunk "File ${file} exists, but should not"
  return 0
}

assert_dir_exists(){
  local dir="${1}"
  [ ! -d "${dir}" ] && flunk "Directory ${dir} does not exist"
  return 0
}

assert_dir_not_exists(){
  local dir="${1}"
  [ -d "${dir}" ] && flunk "Directory ${dir} exists, but should not"
  return 0
}

assert_dir_empty(){
  local dir="${1}"
  [ "$(ls -A "${dir}")" ] && flunk "Directory ${dir} exists, but should not"
  return 0
}

assert_dir_not_empty(){
  local dir="${1}"
  [ -z "$(ls -A "${dir}")" ] && flunk "Directory ${dir} exists, but should not"
  return 0
}

assert_symlink_exists(){
  local file="${1}"
  [ ! -h "${file}" ] && flunk "Symlink ${file} does not exist"
  return 0
}

assert_symlink_not_exists(){
  local file="${1}"
  [ -h "${file}" ] && flunk "Symlink ${file} exists, but should not"
  return 0
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

  [ "${parsed}" != "${perm}" ] && flunk "File permissions for file ${file} is '${parsed}', but expected '${perm}'"
  return 0
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
  grep -r --exclude '.*\.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${string}" "${dir}" || flunk "Directory ${dir} does not contain a string ${string}"
  return 0
}

assert_dir_not_contains_string(){
  local dir="${1}"
  local string="${2}"
  grep -r --exclude '.*\.sh' --exclude-dir='.git' --exclude-dir='.idea' --exclude-dir='vendor' --exclude-dir='node_modules' -l "${string}" "${dir}" > /dev/null && flunk "Directory ${dir} contains string ${string}, but should not"
  return 0
}

assert_empty(){
  if [[ "${1}" == "" ]] ; then
    return 0
  else
    { echo "string:   ${1}"
    } | flunk
  fi
}

assert_not_empty(){
  if [[ "${1}" != "" ]] ; then
    return 0
  else
    { echo "string:   ${1}"
    } | flunk
  fi
}

assert_output(){
  local expected
  if [ $# -eq 0 ]; then expected="$(cat -)"
    else expected="${1}"
  fi
  # shellcheck disable=SC2154
  assert_equal "${expected}" "${output}"
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

# Run bats with `--tap` option to debug the output.
debug(){
  echo "${1}" >&3
}
