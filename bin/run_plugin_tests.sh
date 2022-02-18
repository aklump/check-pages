#!/usr/bin/env bash

#
# @usage ./run_plugin_tests.sh <plugin> ...
#

source="${BASH_SOURCE[0]}"
source="${BASH_SOURCE[0]}"
if [[ ! "$source" ]]; then
  source="${(%):-%N}"
fi
while [ -h "$source" ]; do # resolve $source until the file is no longer a symlink
  dir="$( cd -P "$( dirname "$source" )" && pwd )"
  source="$(readlink "$source")"
  [[ $source != /* ]] && source="$dir/$source" # if $source was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
root="$( cd -P "$( dirname "$source" )" && pwd )"

cd "$root/.."
"./check_pages" example/tests/runner_plugins.php --filter=$1 $@
