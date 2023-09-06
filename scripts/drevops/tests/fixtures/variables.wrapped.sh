#!/usr/bin/env bash
##
# Test 1
#


VAR1=1
VAR2=1
  # indent
  # indent with $VAR
var=${VAR1}
var="${VAR2}"
var=${VAR2:-${VAR1}}
var=${VAR3}

echo "  \$config['stage_file_proxy.settings']['origin'] = 'http://www.resistance-star-wars.com/';"
echo '  $config["stage_file_proxy.settings"]["origin"] = "http://www.resistance-star-wars.com/";'
