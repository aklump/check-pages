#!/usr/bin/env bash

cd "$7"
./bin/run_tests.sh || build_fail_exception
