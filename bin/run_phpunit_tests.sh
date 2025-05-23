#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

cd "$__DIR__/.."

! [ -f ./vendor/bin/phpunit ] && echo && echo "❌️ Missing dependencies; try \`composer install\`" && echo && exit 1

# https://phpunit.readthedocs.io/en/9.5/textui.html#command-line-options
./vendor/bin/phpunit -c tests_phpunit/phpunit.xml "$@"
#./vendor/bin/phpunit -c tests_phpunit/phpunit.xml --testdox "$@"
#export XDEBUG_MODE=$XDEBUG_MODE,coverage;./vendor/bin/phpunit -c tests_phpunit/phpunit.xml "$@" --coverage-html=tests_phpunit/reports
#echo tests_phpunit/reports/index.html
