<!-- Compiled from ./source/CHANGELOG.md: DO NOT EDIT -->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.18.0] - 2023-03-14

### Added

- lorem

### Changed

- `\AKlump\CheckPages\Event::RUNNER_CONFIG` -> `\AKlump\CheckPages\Event::RUNNER_STARTED`
- `resolve` -> `tryResolveDir`
- `resolveFile` -> `tryResolveFile`
- `end_loop` changed back to `end loop`
- `\AKlump\CheckPages\Parts\Runner::url()` -> `\AKlump\CheckPages\Parts\Runner::withBaseUrl()`

### Deprecated

- lorem

### Removed

### Fixed

- An issue with `location` and multiple redirects where it would not return the final redirect URL, but the first. This will break some tests. You will need to update your tests with the final URL.

### Security

- lorem

## [0.17] - 2022-11-27

### Removed

- The configuration for `chrome` can be removed from your .yml file, it's now automatic.

### Changed

- `TEST_FINISHED` changed to `TEST_FINISHED`
- `TEST_FINISHED` now is for `\AKlump\CheckPages\Event\TestEventInterface`
- How user feedback is handled and written.

## [0.16.0] - TBD

### Deprecated

- `Runner::getRunner`
- `Runner::setRunner`
- `with_extras()`

### Changed

- the _drupal_ mixin no longer sets variables with `user.` as the base prefix, but instead uses the passed value of `user`, e.g. `user: foo` sets: `foo.id`, etc.
- The mixin config var has changed to `$mixin_config` instead of `$config`.
- `add_shorthand()` callback arguments changed to ($shorthand, Test). Also you will no longer `unset()` the shorthand key because it's been removed from the test config.
- `Event::TEST_FINISHED` has changed from `\AKlump\CheckPages\Event\DriverEventInterface` to `\AKlump\CheckPages\Event\TestEventInterface`.
- Changed `is/set` to `value/set`; replace `is:` with `value:`. See value plugin for details.
- `request.method` is now required; previously it could be omitted and would be assumed GET.
- Renamed _data_ plugin to _path_ plugin.
- Swithed to Symfony console.
- `./check_pages` became `./check_pages run` (added `run` as a required argument).
- BREAKING CHANGE! `drupal8` and `drupal7` mixins changed to a single `drupal`.
- BREAKING CHANGE! You must use `$config` instead of the like-named variable for mixin configuration. E.g. `$drupal8, $drupal7` are now both just `$config`.
- BREAKING CHANGE! Rename `global $app` to `$runner`.
- BREAKING CHANGE! The style plugin now uses this format; (you must replace `style` with `dom` and `property` with `style`).
  ```yaml
  -
    dom: .visible
    style: display
    ...
  ```
- If `expect` is not explicitly provided, any value from `200` to `299` will pass the test. Previously the response code would have to be exactly `200` to pass.

### Added

- The concept of groups.
- The group (--group, -g) filter
- The import feature.
- The authentication for Drupal now adds the following variables automatically: `${user.uid}, ${user.id}, ${user.name}, ${user.pass}` for the authenticated user. That means you can use these in subsequent tests, even if not authenticating said test.
- `--request` and `--response`.
- Request headers and body to display with `--request` or `--show-source`. If you do not want to see request and ONLY response, use `--response` instead of `--show-source`.
- --help and -h to printout CLI options.
- A debug message if test is missing assertions.
- Start and stop date and times.
- `--filter` now accepts multiple suites as CSV, e.g. `--filter=foo,bar` as well as single suites.
- The ability to test data-serving URLs (i.e. API endpoints) using JSON Schema.

### Fixed

- --quite mode was not working.

## [0.15.0] - 2021-07-16

### Added

- `config_get()`

## [0.14.0] - 2021-07-16

### Added

- The ability to omit _config/_ in the CLI. Where before you had to pass `--config=config/live`, which still works, you may also pass `--config=live` if your configuration directory is named by the standard name of _config_. If it is not then this shortcut will fail.

## [0.13.0] - 2021-07-07

### Added

- Output file urls.txt
- Output file failures.txt

## [0.12.0] - 2021-07-06

### Added

- `why` key for message overrides.
- Disk storage of sessions storage across runners. See docs for more info.

## [0.11.0] - 2021-07-03

### Added

- Create tests directory prompt on package install.
- Authentication for Drupal 7 and Drupal 8 via `with_extras()` function.
- `add_test_option()` function for custom functionality.

### Removed

- `composer create-project` is no longer supported as it was too confusing and unnecessary to have two installation means.

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
  selector. It will now be forcefully set (or overridden) to `true`. This is because the `style` selector only works when javascript is enabled.

## [0.7.0] - 2021-04-08

### Added

- Added the `none` assertion to ensure a substring does not appear on the page.

## [0.6.0] - 2021-01-16

### Added

- Added new selector 'attribute'.
- Added ability to do style asserts.
- Added globbing to run_suite(), e.g. `run_suite('*')` to run all suites. Normal glob patterns work as well, which are relative to the --dir directory, or defaults to the directory containing _runner.php_.

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

- The way the CSS selector works has changed fundamentally and may break your tests. Refer to the following test YAML. Prior to 0.4 that would only choose the first `.card__title` on the page and assert it's text matched the expected. Starting in 0.4, all `.card__titles` found on the page will be matched and the assert will pass if any of them have matching text.

    ```yaml
    -
      visit: /foo/bar
      find:
        -
          dom: .card__title
          text: The Top of the Mountain
    ```

- If you need the earlier functionality you should use the `xpath` selector as shown here to indicate just the first element with that class.

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

- The default output is now how it was when adding the `--debug` flag, use the `--quiet` flag for less verbosity.
- Visual layout to make reading results easier and more clear.

### Removed

- The `--debug` flag
  
