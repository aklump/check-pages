# Developing Check Pages Locally

If you want to fork and develop check pages locally and use the fork in other local projects. You should setup as described on this page.

1. Establish a local copy of check_pages.
2. Use something like the following in your project that references the local fork, i.e., `"$HOME/Code/Packages/php/check-pages/check_pages"`

```shell
(cd "$app_root" && "$HOME/Code/Packages/php/check-pages/check_pages" run ./tests_check_pages/runner.php --dir=./tests_check_pages $@)
```
