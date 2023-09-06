#!/bin/bash
#
# Lint and fix variables not wrapped in ${VAR}.
#
# Usage:
#   ./lint-variables.sh <file> [1 to replace]#
# shellcheck disable=SC1003

if [ -z "${1}" ]; then
  echo "Error: No file name provided."
  exit 1
fi

if [ ! -f "${1}" ]; then
  echo "Error: File does not exist."
  exit 1
fi

if [ ! -s "${1}" ]; then
  echo "Error: File is empty."
  exit 1
fi

file="${1}"
should_replace="${2:-0}"

found=0
line_number=0

temp_file=$(mktemp)

while IFS= read -r line; do
  inside_single_quotes=0
  inside_double_quotes=0
  escape_next=0
  new_line=""
  line_number=$((line_number + 1))

  if [[ ${line} =~ ^[[:space:]]*# ]]; then
    echo "${line}" >>"${temp_file}"
    continue
  fi

  for ((i = 0; i < ${#line}; i++)); do
    char="${line:i:1}"

    if [[ ${escape_next} == "1" ]]; then
      escape_next=0
      new_line+="${char}"
      continue
    fi

    if [[ ${char} == '\' ]]; then
      escape_next=1
    fi

    if [[ ${char} == "'" ]]; then
      inside_single_quotes=$((1 - inside_single_quotes))
    elif [[ ${char} == '"' ]]; then
      inside_double_quotes=$((1 - inside_double_quotes))
    fi

    if [[ ${inside_single_quotes} -eq 0 && ${escape_next} -eq 0 && ${char} == "$" && ${line:$((i + 1)):1} =~ [a-zA-Z0-9_] ]]; then
      var=""
      for ((j = i + 1; j < ${#line}; j++)); do
        next_char="${line:j:1}"
        if [[ ${next_char} =~ [a-zA-Z0-9_] ]]; then
          var+="${next_char}"
        else
          break
        fi
      done
      new_line+="\${${var}}"
      i=$((j - 1))
      found=1
      echo "Line ${line_number}: Non-conforming variables found"
    else
      new_line+="${char}"
    fi
  done
  if [[ ${should_replace} == "1" && ${found} == "1" ]]; then
    echo "${new_line}" >>"${temp_file}"
  else
    echo "${line}" >>"${temp_file}"
  fi
done <"${file}"

if [[ ${found} == "1" && ${should_replace} == "1" ]]; then
  mv "${temp_file}" "${file}"
  exit 0
else
  rm -f "${temp_file}"
fi

exit ${found}
