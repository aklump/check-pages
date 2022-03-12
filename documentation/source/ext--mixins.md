<!--
id: mixins
title: Mixins
-->

# Mixins: Shared Code Between Runners

## How it Looks

```yaml
# file: runner.php
add_mixin('my_custom_mixin');
```

> When you want to share code across runners, you should look to _mixins_. Often you will want to put your runner function implementations inside a mixin file.

## Explained

Another way to extend _Check Pages_ is to use the `add_mixin()` function. This method is easier and faster than writing a plugin. It is a simple means to share runner customisation across different runners, or even projects. This is the basis for the authentication providers shipped with Check Pages.

```text
.
└── cp_tests
    ├── mixins
    │   └── foo.php
    ├── runner.php
    └── suite.yml
```

1. Create a directory to contain one or more mixin files. This can be located within any resolvable directory. (See `\AKlump\CheckPages\Parts\Runner::resolve`.)
2. In that directory create a file, whose filename will be used as the first argument to `add_mixin()`--the mixin "name". So for `add_mixin('mixins/foo')` you should create _mixins/foo.php_ as shown in the diagram above.
3. In the runner file, make reference to the mixin, passing the configuration as expected by the mixin.
    ```php
    # file: runner.php
    
    add_mixin('mixins/foo', [
      "title" => "Lorem",
      "color" => "blue",
    ]);
    ...
    ```
4. The second argument, the configuration, is available in the mixin file, _mixins/foo.php_ as `$config`.

    ```php
    # file: mixins/foo.php
    
    $title = $config['title'];
    $color = $config['color'];
   
    ```
5. The runner instance `\AKlump\CheckPages\Parts\Runner` is available as `$runner`.
6. You may use any of the _runner_functions.inc_ as you might otherwise do in a runner file.
7. By convention, you may create an class named for the mixin in your file if necessary.
8. You should add the namespace `AKlump\CheckPages\Mixins` to your mixin file.

## Output

You may write output if desired, for example:

```php
echo sprintf('Base URL is %s', config_get('base_url')) . PHP_EOL;
echo \AKlump\LoftLib\Bash\Color::wrap('blue', 'foo');
```

## Namespace

You should use the namespace `AKlump\CheckPages\Mixins` for any class objects you might create.

## Errors

To stop testing immediately you should throw an instance of `\AKlump\CheckPages\Exceptions\StopRunnerException`. The message argument will be displayed to the user.
