<!-- Compiled from ./source/CHANGELOG.md: DO NOT EDIT -->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Add `login` and `logout` in _includes/drupal_ to be able to run suites as
  authenticated users.

## [0.10.0] - 2021-05-28

### Added

- `is not`
- `not matches`

### Changed

- `exact` is now `is`; change all usages.
- `match` is now `matches`; change all usages.
- `none` is now `not contains`; change all usages.

### Removed

- `exact`
- `match`
- `none`

### Fixed

- JS error when the eval is used more than once per test.

## [0.9.0] - 2021-05-28

### Added

- The header assertion plugin

## [0.8.0] - 2021-04-14

### Added

- The `javascript` selector for expression evaluation.

### Changed

- It's no longer required to add `js: true` to a test implementing a `style`
  selector. It will now be forcefully set (or overridden) to `true`. This is
  because the `style` selector only works when javascript is enabled.

## [0.7.0] - 2021-04-08

### Added

- Added the `none` assertion to ensure a substring does not appear on the page.

## [0.6.0] - 2021-01-16

### Added

- Added new selector 'attribute'.
- Added ability to do style asserts.
- Added globbing to run_suite(), e.g. `run_suite('*')` to run all suites. Normal
  glob patterns work as well, which are relative to the --dir directory, or
  defaults to the directory containing _runner.php_.

### Changed

- run_suite() now returns void().

## [0.5.1] - 2021-01-14

### Added

- The alias `visit:` may be used instead of `url:`
- Examples now show using `visit:`, though `url:` still works.

## [0.5] - 2021-12-30

### Added

- `--filter` parameter to limit runner to a single suite from the CLI.

## [0.4] - 2020-12-01

### Added

- Javascript support with Chrome.

### Changed

- The way the CSS selector works has changed fundamentally and may break your
  tests. Refer to the following test YAML. Prior to 0.4 that would only choose
  the first `.card__title` on the page and assert it's text matched the
  expected. Starting in 0.4, all `.card__titles` found on the page will be
  matched and the assert will pass if any of them have matching text.

    ```yaml
    -
      visit: /foo/bar
      find:
        -
          dom: .card__title
          text: The Top of the Mountain
    ```

- If you need the earlier functionality you should use the `xpath` selector as
  shown here to indicate just the first element with that class.

   ```yaml
   -
     visit: /foo/bar
     find:
       -
         xpath: '(//*[contains(@class, "card__title")])[1]'
         text: The Top of the Mountain
   ```

## [0.3] - 2020-08-15

### Added

- Added the `--quiet` flag

### Changed

- The default output is now how it was when adding the `--debug` flag, use
  the `--quiet` flag for less verbosity.
- Visual layout to make reading results easier and more clear.

### Removed

- The `--debug` flag
  
