<!--
id: testing_legacy
tags: ''
-->

# Testing Legacy Sites

If your website project uses Composer but is less than the minimum requirments for this package, then you cannot install this package in your project as an additional Composer dependency.

1. Use composer to install it on a server with the proper requirements, in this example it was installed at _/Users/aklump/Code/Packages/php/check-pages_
2. Create a file in your app in _./bin/run_check_pages.sh_
3. Add the code as shown below.
4. Execute testing with `./bin/run_check_pages.sh`

```text
.
├── bin
│   └── run_check_pages.sh
└── tests_check_pages
```

```shell
#!/bin/bash

#
# @file
# Wrapper for aklump/check-pages for local testing.
#

source="${BASH_SOURCE[0]}"
while [ -h "$source" ]; do # resolve $source until the file is no longer a symlink
  dir="$(cd -P "$(dirname "$source")" && pwd)"
  source="$(readlink "$source")"
  [[ $source != /* ]] && source="$dir/$source" # if $source was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
root="$(cd -P "$(dirname "$source")" && pwd)"

cd "$root/../tests_check_pages" && /Users/aklump/Code/Packages/php/check-pages/check_pages run runner.php $@
```
