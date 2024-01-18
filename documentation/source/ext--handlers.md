<!--
id: handlers
title: Handlers
-->

# Handlers: Full Customization

# Handlers

## Using Composer Dependencies

1. Inside the handler folder require your dependency with the `--no-install` flag.
2. Do not add _HANDLER/composer.lock_ to the repo, nor _HANDLER/vender/_.
3. Add your handler's _composer.json_ path to the `/extra/merge-plugin/require` array.
4. Run `composer update`.

## Proper Use of Letter Case

Configuration keys provided by handlers should be lower-cased, space-separated in keeping with human-like syntax. See `pixel ratio` below for an example.

**Do not make them mixed- or snake-case.**

```yaml
-
  why: Demonstrate testing against a "retina" display
  url: /test_subject.html
  device:
    pixel ratio: 2
```

@todo Below here is old, needs update.

---

## How it Looks

_How it looks is myriad and you must refer to the handler code to determine how it's implemented when writing tests._

## Explained

This is the most involved method of extending Check Pages, and offers the greatest control as well.

@todo Where do I put these as an end user?

## Creating a Handler

1. Create a unique folder in _handlers_ with the following structure. In this example the new handler will be called `foo_bar`.

   ```
   handlers
   └── foo_bar
       ├── compile.php
       ├── FooBar.php
       ├── README.md
       ├── schema.definitions.json
       ├── schema.assertion.json
       ├── schema.test.json
       ├── src
       │   └── Alpha.php
       └── suite.yml
       └── test_subject.html
   ```


1. Write the _suite.yml_ file which will be run against _test_subject.html, test_subject.php, test_subject.json_, etc
2. Create _test_subject.html_ or _test_subject.php_ as needed to test _
   suite.yml_.
3. You may include more than one _test_subject.*_ file.   
4. _README.md_ is optional, but will be added to the Check Pages documentation when it's compiled and should be used to give examples of how the handler should be implemented.

### Handler Objects/Classes

* Each handler will provide it's main class in the namespace `AKlump\CheckPages\Handlers` with an upper-camel case file matching it's id, e.g. _foo_bar/FooBar.php_.
* Any additional classes should be namespaced to the handler, e.g. `AKlump\CheckPages\Handlers\FooBar` and saved to _foo_bar/src/Alpha.php_`.

## Extending the JSON Schema for Suite Validation

The handler may provide schema with any of the following files:

1. _my_handler/schema.definitions.json_
2. _my_handler/schema.test.json_
3. _my_handler/schema.assertion.json_

_(Inspect handlers to see how these are used. Be aware that some properties are reserved and added automatically, i.e., `why`, `extra`; you should not add these in your handler. Here are some examples.)_

```yaml
# file: schema.assertion.json
```

```json
{
    "type": "object",
    "required": [
        "foo"
    ],
    "properties": {
        "foo": {
            "$ref": "#/definitions/dom__dom"
        }
    },
    "additionalProperties": false
}
```

```yaml
# file: schema.assertion.json
```

```json
{
    "js_eval": {
        "type": "string",
        "pattern": ".+",
        "examples": [
            "location.hash"
        ]
    }
}
```

## Disabled

If you rename a handler directory with a leading underscore, e.g. "foo" to "_foo" then it will be ignored. This is a "disabled handler".

## Advanced

The _json_schema_ handler is a good example of a handler that totally handles the assertion on it's own. You may want to study that if you need to do something fancy.

## Testing Handlers

To run a handler's tests do this: `./bin/run_handler_tests <handler>`.  (Don't forget to `./bin/compile_app.sh` if you make a change.)

## Compiled Files

Do not edit the following, as they are created in the build step and will be overwritten. To affect these files you need to look to _handlers/_
directory, which contains the source code.

```
.
├── tests
│     ├── handlers
│     │     ├── foo.yml
│     │     └── javascript.yml
│     ├── handlers_runner.php
└── web
    └── handlers
        ├── foo.html
        └── javascript.html

```

## Unique Compilation

If your handler needs to do something unique during compilation, such as provide extra files, it can implement _compile.php_. Look for class constants in `\AKlump\CheckPages\CheckPages` to use in your code: Here's an example from the _imports_ handler.

```php
# file: imports/compile.php

/**
 * @file Copy over the imports files during compile.
 */

// These variables are available:

/** @var array $handler */
/** @var string $output_base_dir */

$source = $handler['path'] . '/imports';
$destination = "$output_base_dir/tests/imports";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}
copy("$source/_headings.yml", "$destination/_headings.yml");
copy("$source/_links.yml", "$destination/_links.yml");

foreach ([
           "$destination/_headings.yml",
           "$destination/_links.yml",
         ] as $path) {
  if (!file_exists($path)) {
    return FALSE;
  }
  $data = file_get_contents($path);
  $data = str_replace(\AKlump\CheckPages\CheckPages::FILENAME_HANDLERS_TEST_SUBJECT, $handler['id'], $data);
  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}
```
