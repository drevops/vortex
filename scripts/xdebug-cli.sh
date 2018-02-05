#!/usr/bin/env bash
#
# Enable PHP CLI XDebug debugging inside of the VM.
#
# CLI debugging will stay enabled during current ssh session. This allows
# disabling it by simply re-ssh'ing into the VM.
#
# Usage (from within the VM):
# . scripts/xdebug-cli.sh mysite.docker.amazee.io
# where mysite.docker.amazee.io is a server name and URL mapping in PHPStorm.

# IDE key as set in PHPStorm's server configuration.
IDEKEY=PHPSTORM
# Host OS' IP address.
REMOTE_HOST=$(netstat -rn | grep "^0.0.0.0 " | cut -d " " -f10)
# Default server name.
SERVER_NAME="mysite.docker.amazee.io"

# Pass server name as a first parameter as set in PHPStorm's server
# configuration.
if [ -n "$1" ] ; then
  SERVER_NAME=$1
fi

export XDEBUG_CONFIG="idekey=$IDEKEY remote_host=$REMOTE_HOST"
export PHP_IDE_CONFIG="serverName=$SERVER_NAME"

echo XDEBUG_CONFIG=$XDEBUG_CONFIG
echo PHP_IDE_CONFIG=$PHP_IDE_CONFIG
