<!--
id: with_extras
-->

# Add Runner Functionality

Another way to extend _Check Pages_ is to use the `with_extras()` function. This method is easier and faster than writing a plugin. It is a simple means to share runner customisation across different runners, or even projects. This is the basis for the authentication providers shipped with Check Pages.

1. Create a directory `extras`, for example inside your _tests_ directory. (Note: It must be resolvable by `\AKlump\CheckPages\Parts\Runner::resolve()`.)
2. In that directory create a file, whose filename will be used as the first argument to `with_extras()`. So for `with_extras('foo')` you should create _extras/foo.php_.

    ```php
    # file: runner.php
    
    with_extras('foo', [
      "title" => "Lorem",
      "color" => "blue",
    ]);
    ...
    ```
3. The second argument passed to `with_extras()` is available in _extras/foo.php_ as `$config`.

    ```php
    # file: extras/foo.php
    
    $title = $config['title'];
    $color = $config['color'];
   
    ```
1. Also, you have access to the `\AKlump\CheckPages\Parts\Runner` instance in the variable `$runner`.
4. You may use any of the _runner_functions.inc_ as you might otherwise do in a runner file.

## Output

You may write output if desired, for example:

```php
echo sprintf('Base URL is %s', config_get('base_url')) . PHP_EOL;
echo \AKlump\LoftLib\Bash\Color::wrap('blue', 'foo');
```
