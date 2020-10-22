Scripts in this directory used for workflow actions.

They are expected to be ran inside of containers.

There is no need to set values within scripts - update values in `.env` file
instead.

Add your custom per-project scripts into `./scripts/custom` directory.

## Bash cheatsheet
[//]: # ( Content mostly taken from https://gist.github.com/lee2sman/423ef08fc2318969b3eaaf5d1e14e02e)

Full cheatsheet: https://devhints.io/bash

## Shebang
Add this as the first line of your file.

    #!/usr/bin/env bash

## Comments

    # this symbol makes everything after it on a line a comment

## Variables

- No data types.
- No need to declare. Just assign a value to create it.
- Get the value of a variable (whatever is stored in it) by calling it with `$` in front.
- Declare a local variable in a function by stating `local` before creating it.

      local var1

Built-in shell variables:

    $0    # Name of the shell script itself.
    $1    # Value of the first command line parameter (similarly $2, $3, etc)
    $#    # In a shell script, the number of the command line parameters.
    $*    # All of the command line parameters.
    $-    # Options given to the shell.
    $?    # Return the exit status of the last command.
    $$    # Process id of script (really id of the shell running the script).

Assign a variable:

    VAR=1

Use a variable:

    VAR2=$VAR
    VAR2=${VAR}
    VAR2="${VAR}"

Print a variable:

    echo $VAR
    echo ${VAR}
    echo "${VAR}"

Default value:

    VAR="${VAR:-default}"
    VAR="${VAR:-${ANOTHER_VAR}}"

> Pro tip: Always use `${VAR}` syntax instead of `$VAR` to differentiate variables
from other constructs.

## File redirection and Piping

- Three default files:
  - `0` - standard input (`stdin`)
  - `1` standard output (`stdout`)
  - `2` standard error (`stderr`)
- `> filename` redirects `stdout` to a file `filename`. If file does not exist
  - it is created.
- `>> filename` redirects `stdout` to a file `filename`. Appends to the end of
   the file if it already exists.
- `|` is the pipe. Piping is used to chain commands, scripts, files and programs
   together.

      cat *.txt | sort > result_file.txt # Sorts the output of all the .txt files, saving outcome to result_file.txt.

## Input
Ask for user input:

    `read num` # Asks for input and puts it in $num.

## Conditions

    if command; then
      echo "output"
      echo "another line"
    fi

    if command; then
      echo "output"
      echo "another line"
    else
      echo "and another line"
    fi

    if [ "${VAR}" == "value" ] then
      echo "output"
      echo "another line"
    fi

    if [ "${VAR}" != "value" ] then
      echo "output"
      echo "another line"
    fi

    if [ "${VAR1}" == "value1" ] && [ "${VAR2}" == "value2" ] then
      echo "output"
      echo "another line"
    fi

    if { [ "${VAR1}" == "value1" ] && [ "${VAR2}" == "value2" ] } || [ "${VAR3}" == "value3" ]; then
      echo "output"
      echo "another line"
    fi

One-liners:

    [ "${VAR1}" == "value1" ] && echo "line" || exit 1

    { [ "${VAR1}" == "value1" ] && [ "${VAR2}" == "value2" ] } || [ "${VAR3}" == "value3" ] && echo "line" || exit 1

String comparisons:

    [ "${VAR1}" == "value1" ] && echo "equal"

    [ "${VAR1}" != "value1" ] && echo "not equal"

    [ "${VAR1}" < "value1" ] && echo "less than (ascii-betically)"

    [ "${VAR1}" > "value1" ] && echo "more than (ascii-betically)"

    [ -z "${VAR1}" ] && echo "string is null"

    [ -z "${VAR1+x}" ] && echo "string is null or not set"

    [ -n "${VAR1}" ] && echo "string is not null"

## Loops

For loop:

    for i in 1 2 3 4 5
    do
    	echo "Welcome $i times"
    done

    for i in {1..5}
    do
    	echo "Welcome $i times"
    done

    # With increment of 2.
    for i in {1..10..2}
    do
    	echo "Welcome $i times"
    done

While loop:

    while [ condition ]
    do
    	command
    done

## Switch

    case "$C" in
    "1")
    	do_this()
    	;;
    "2" | "3")
    	do_what_you_are_supposed_to_do()
    	;;
    *)    # fallback default case
    	do_nothing()
    	;;
    esac

## Functions

Declare a function:

    function myfunc {
      echo $1
    }

Call a function:

    myfunc "Hello" # Will echo Hello when called.

## Sourcing and exporting

Export a variable:

    VAR="val1"
    export "${VAR}"

    export VAR="val2"

Call another script (must have execute permission)

    ...

    ./path/to/script.sh

    ...

Source another script - will be executed in the context of the current script:

    ...

    . ./path/to/script.sh

    ...

## Useful snippets

DotEnv in bash:

    t=$(mktemp) && export -p > "$t" && set -a && . ./.env && set +a && . "$t" && rm "$t" && unset t
