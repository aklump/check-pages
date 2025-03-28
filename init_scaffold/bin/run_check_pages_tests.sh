#!/bin/bash

#
# @file
# Check pages test runner.
#
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)
cd "$__DIR__/../"

mode=$(lando xdebug --mode 2> /dev/null | tr -cd "[:print:]\n")
if [[ "$mode" && "$mode" != 'off' ]]; then
  message="                                                               "
  message="$message\n     XDebug is running in Lando and will slow things down.     "
  message="$message\n     You should disable unless you're actively debugging.      "
  message="$message\n                                                               "
  NO_FORMAT="\033[0m"
  F_BOLD="\033[1m"
  C_YELLOW="\033[48;5;226m"
  echo -e "${F_BOLD}${C_YELLOW}$message${NO_FORMAT}"
fi

check_pages_bin=$(which checkpages)
#check_pages_bin=./vendor/bin/check_pages
#if [ -f "$HOME/Code/Packages/php/check-pages/check_pages" ]; then
#  check_pages_bin="$HOME/Code/Packages/php/check-pages/check_pages"
#fi
#"$check_pages_bin" run ./tests_check_pages/runner.php --dir=./tests_check_pages "$@"
# Use this to force a PHP version.
CHECK_PAGES_PHP="/Applications/MAMP/bin/php/php7.4.33/bin/php"
"$CHECK_PAGES_PHP" "$check_pages_bin" run ./tests_check_pages/runner.php --dir=./tests_check_pages "$@"
