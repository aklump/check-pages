#!/bin/bash

#
# @file
# Check pages test runner.
#
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)
cd "$__DIR__/../"

# ========= CONFIG =========
CHECK_PAGES_BIN=$(which checkpages)
#CHECK_PAGES_PHP="/Applications/MAMP/bin/php/php8.1.31/bin/php"
# ========= /CONFIG =========

mode=$(lando xdebug --mode 2> /dev/null | tr -cd "[:print:]\n" | head -n 1)
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

if [[ "$CHECK_PAGES_PHP" ]]; then
  "$CHECK_PAGES_PHP" "$CHECK_PAGES_BIN" run ./tests_check_pages/runner.php --dir=./tests_check_pages "$@"
else
  "$CHECK_PAGES_BIN" run ./tests_check_pages/runner.php --dir=./tests_check_pages "$@"
fi
