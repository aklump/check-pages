<!--
id: autoload
tags: ''
-->

# Autoloading with Composer

As your Check Pages test suite grows, your test codebase may require custom helper classes, preflight assertion checkers, and specialized shorthand functions. You can leverage Composer directly in your test directory (e.g., `tests_check_pages/`) to autoload both custom PHP classes and standalone helper files.

## Benefits

Using Composer autoloading in your test directory provides several advantages:
- **Separation of Concerns**: Keep `runner.php` concise and focused on test execution orchestration.
- **Class Organization**: Organize helper classes, services, and preflight assertion methods cleanly into PSR-4 namespaced directories under `src/`.
- **Automatic File Inclusion**: Separate custom shorthand registrations or utility functions into dedicated files (e.g., `shorthands.php`) that load automatically.
- **No Manual Requires**: Avoid chaining multiple `require_once` calls throughout your test runner script.

---

## Directory Layout

A typical test directory using Composer autoloading looks like this:

```
tests_check_pages/
├── composer.json
├── config/
│   └── localdev.yml
├── runner.php
├── shorthands.php
├── src/
│   └── Preflight/
│       └── CheckTestEnvironment.php
├── suites/
│   └── users.yml
└── vendor/
    └── autoload.php
```

---

## Setting Up `composer.json`

Create a `composer.json` file inside your test directory (e.g., `tests_check_pages/composer.json`). Use the `autoload` property to configure both file-based and PSR-4 class-based autoloading:

```json
{
    "name": "my-project/check-pages-tests",
    "type": "project",
    "autoload": {
        "files": [
            "./shorthands.php"
        ],
        "psr-4": {
            "MyProject\\EndToEndTests\\": "src/"
        }
    }
}
```

### Autoloading Options

1. **`autoload.psr-4`**: Maps a namespace prefix (such as `MyProject\EndToEndTests\`) to a source directory (e.g., `src/`). Any PHP classes defined under `src/` conforming to PSR-4 will be lazily autoloaded when referenced.
2. **`autoload.files`**: Specifies an array of file paths to be included automatically as soon as the autoloader initializes. This is ideal for scripts that register custom shorthands via `add_shorthand()` or procedural helper functions.

---

## Generating the Autoloader

After defining or updating `composer.json`, generate the `vendor/autoload.php` file by running `composer dump` (or `composer dump-autoload`) from your test directory:

```bash
cd tests_check_pages
composer dump
```

*Note: You do not need external dependencies installed via `composer install` to use Composer's autoloader for your local files and classes.*

Whenever you add new files to `autoload.files` or modify your PSR-4 mappings in `composer.json`, run `composer dump` again to refresh the generated autoloader.

---

## Initializing Autoloading in `runner.php`

At the very top of your test runner script (`runner.php`), require the generated autoloader:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
```

When this line executes:
1. All files listed in `autoload.files` (e.g., `shorthands.php`) are immediately loaded, registering custom shorthands and functions.
2. Any classes mapped under `autoload.psr-4` become immediately available for use throughout your runner script and test callbacks.

---

## Related Topics

@see [Preflight Checks](@preflight)
@see [Custom Shorthands](@shorthand)
@see [Mixins](@mixins)
@see [Filenaming and Organization](@deep__organizing)
