<!--
id: plugins
title: Plugins
-->

# Plugins: Full Customization

# Plugins

## Using Composer Dependencies

1. Inside the plugin folder require your dependency with the `--no-install` flag.
3. Do not add _PLUGIN/composer.lock_ to the repo.
2. Compile the plugins `./bin/compile_plugins.php.php`.


@todo Below here is old, needs update.

---

## How it Looks

_How it looks is myriad and you must refer to the plugin code to determine how it's implemented when writing tests._

## Explained

This is the most involved method of extending Check Pages, and offers the greatest control as well.

@todo Where do I put these as an end user?

## Creating a Plugin

1. Create a unique folder in _plugins_ with the following structure. In this example the new plugin will be called `foo_bar`.

   ```
   plugins
   └── foo_bar
       ├── compile.php
       ├── FooBar.php
       ├── README.md
       ├── schema.definitions.json
       ├── schema.assertion.json
       ├── schema.test.json
       ├── suite.yml
       └── test_subject.html
   ```


1. Write the _suite.yml_ file which will be run against _test_subject.html, test_subject.php, test_subject.json_, etc
2. Create _test_subject.html_ or _test_subject.php_ as needed to test _
   suite.yml_.
3. _README.md_ is optional, but will be added to the Check Pages documentation when it's compiled and should be used to give examples of how the plugin should be implemented.

## Extending the JSON Schema for Suite Validation

The plugin may provide schema with any of the following files:

1. _my_plugin/schema.definitions.json_
2. _my_plugin/schema.test.json_
3. _my_plugin/schema.assertion.json_

_(Inspect plugins to see how these are used. Be aware that some properties are reserved and added automatically, i.e., `why`, `extra`; you should not add these in your plugin. Here are some examples.)_

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
            "$ref": "#/definitions/dom_dom"
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

## Advanced

The _json_schema_ plugin is a good example of a plugin that totally handles the assertion on it's own. You may want to study that if you need to do something fancy.

You can see the basic strategy here:

1. You may only return `TRUE`, indicating the assertion passed.
2. If it failed you must throw an exception, the message of which will be printed in the test results as to the reason for the failure.

```php
public function onBeforeAssert(\AKlump\CheckPages\Event\AssertEventInterface $event) {
    $assert = $event->getAssert();
    $response = $event->getReponse();

  $assert->setAssertion(Assert::ASSERT_CALLABLE, function ($assert) {
    
    // ... do your fancy assertion
    
    if ($it_failed) {
      throw new \RuntimeException(sprintf('The reason for the failure'));
    }

    return TRUE;
  });
}
```

## Testing Plugins

To run a plugin's tests do this: `./bin/run_plugin_tests <plugin>`.  (Don't forget to `bump build plugins` if you make a change.)

## Compiled Files

Do not edit the following, as they are created in the build step and will be overwritten. To affect these files you need to look to _plugins/_
directory, which contains the source code.

```
.
├── tests
│     ├── plugins
│     │     ├── foo.yml
│     │     └── javascript.yml
│     ├── runner_plugins.php
└── web
    └── plugins
        ├── foo.html
        └── javascript.html

```

## Unique Compilation

If your plugin needs to do something unique during compilation, such as provide extra files, it can implement _compile.php_. Here's an example from the _imports_ plugin.

```php
# file: imports/compile.php

/**
 * @file Copy over the imports files during compile.
 */

$source = "$plugin_dir/imports";
$destination = "$compile_dir/tests/imports";

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
  $data = str_replace('test_subject', $plugin['id'], $data);
  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}
```
