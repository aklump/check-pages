<?php

namespace AKlump\CheckPages\Mixins\MyMixin;

/**
 * Provide Check Pages with a Mixin Called My Mixin
 *
 * Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
 * tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
 * quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
 * consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
 * cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
 * proident, sunt in culpa qui officia deserunt mollit anim id esta laborum.
 *
 * Usage in runner.php
 *
 * @code
 * add_mixin('mixins/my_mixin', [
 *   "title" => "Lorem",
 *   "color" => "blue",
 * ]);
 * @endcode
 */

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

// @see includes/runner_functions.php for built-in functions.

## Mixin Config
$title = $mixin_config['title'] ?? 'My Mixin';
$color = $mixin_config['color'] ?? 'blue';

# Output
echo sprintf('Base URL is %s', config_get('base_url')) . PHP_EOL;
echo \AKlump\LoftLib\Bash\Color::wrap($color, $title);

# Error
throw new \AKlump\CheckPages\Exceptions\StopRunnerException("Testing failed due to an unspecified error.");
