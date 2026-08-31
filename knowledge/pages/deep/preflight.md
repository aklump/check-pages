<!--
id: preflight
tags: ''
-->

# Preflight Checks

Preflight checks allow you to verify the health and readiness of your test environment *before* running any test suites. They help prevent false-positive failures and save time by ensuring external services, databases, configurations, and runtime settings are properly set up prior to test execution.

## Overview

Preflight checks are registered and executed inside your test runner script (e.g. `runner.php`) using two functions:

- `add_preflight_check(string $assertion_message, callable $assertion): void`
- `run_preflight(): void`

When `run_preflight()` is called, Check Pages iterates through all registered checks, displays a progress indicator in the console, and executes each callback.

### CLI Optimization

To save time during rapid test iteration, preflight checks are automatically skipped when:
- Retesting failed tests with the `--retest` option.
- Running filtered tests with the `--filter` option.
- No preflight checks have been registered.

## Registering Checks

Use `add_preflight_check()` to register a check. It accepts:

1. **`$assertion_message`** (`string`): A statement describing what is expected to be true (e.g., `'All Drupal database updates have run'` or `'Xdebug should be disabled'`).
2. **`$assertion`** (`callable`): A callback receiving the `\AKlump\CheckPages\Parts\Runner` instance as an argument.

### Check Results and Return Values

Your assertion callback communicates the status of the check via its return value or by throwing an exception:

| Return / Behavior | Meaning | Action Taken by Check Pages |
| --- | --- | --- |
| `[]` (empty array) | **Pass** | The check passes and execution continues to the next check. |
| `[$message, $defaultAnswer]` | **Warning / Prompt** | Displays a warning with `$message` and prompts the user `Continue with testing?`. The prompt defaults to `$defaultAnswer` (`true` or `false`). If the user chooses `no`, testing aborts with exit code `1`. |
| Throw an `Exception` | **Fatal Error** | Testing immediately aborts, displays the exception message as an error, and exits with code `1`. No subsequent preflight checks or test suites will run. |

## Implementation Example

Here is an example showing how to configure preflight checks in your test runner and implement a dedicated environment check class.

### 1. Test Runner (`runner.php`)

```php
<?php

use MyApp\EndToEndTests\Preflight\CheckTestEnvironment;

require_once __DIR__ . '/vendor/autoload.php';

load_config('config/localdev.yml');
add_directory(__DIR__ . '/json_schema');

// Register preflight checks.
add_preflight_check('All Drupal database updates have run', fn() => CheckTestEnvironment::hasPendingDatabaseUpdates());
add_preflight_check('Xdebug is disabled', fn() => CheckTestEnvironment::isXdebugEnabledOnServer());

add_mixin('env_vars');
add_mixin('sessions', [
  'users' => config_get('extras.sessions'),
]);

// Execute all preflight checks before running suites.
run_preflight();

run_suite('suites/anonymous/*');
```

### 2. Environment Checker Class

```php
<?php

namespace MyApp\EndToEndTests\Preflight;

final class CheckTestEnvironment {

  /**
   * Warn the user if Xdebug is enabled on the server.
   *
   * @return array
   *   Empty array on pass, or [$warningMessage, $defaultContinue] on issue.
   */
  public static function isXdebugEnabledOnServer(): array {
    $output = [];
    $exitCode = NULL;
    exec('lando php -m | grep -i "xdebug"', $output, $exitCode);
    if (0 === $exitCode) {
      return [
        'Some tests may fail because the server has Xdebug enabled.',
        TRUE,
      ];
    }

    return [];
  }

  /**
   * Ensure database updates are up to date.
   *
   * @return array
   *   Empty array on pass, or [$warningMessage, $defaultContinue] on issue.
   *
   * @throws \RuntimeException
   *   If the status command fails to run.
   */
  public static function hasPendingDatabaseUpdates(): array {
    $output = [];
    $exitCode = 0;
    exec('lando drush updatedb:status --no-interaction', $output, $exitCode);
    if ($exitCode !== 0) {
      throw new \RuntimeException('Could not check Drupal database update status.');
    }
    if (str_contains(implode(PHP_EOL, $output), 'hook_update_n')) {
      return [
        'There are pending database updates.',
        FALSE,
      ];
    }

    return [];
  }

}
```

## Related Topics

