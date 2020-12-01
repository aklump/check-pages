# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- Add `login` and `logout` in _includes/drupal_ to be able to run suites as authenticated users.

## [0.4] - 2020-12-01

### Added
- Javascript support with Chrome.
  
### Changed
- The way the CSS selector works has changed fundamentally and may break your tests.  Refer to the following test YAML.  Prior to 0.4 that would only choose the first `.card__title` on the page and assert it's text matched the expected.  Starting in 0.4, all `.card__titles` found on the page will be matched and the assert will pass if any of them have matching text.
    ```yaml
    -
      url: /foo/bar
      find:
        -
          dom: .card__title
          text: The Top of the Mountain
    ```
-  If you need the earlier functionality you should use the `xpath` selector as shown here to indicate just the first element with that class.

    ```yaml
    -
      url: /foo/bar
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
  
