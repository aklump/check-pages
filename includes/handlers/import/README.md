# Imports (Code Reuse with Partials)

## How it Looks

```yaml
# file: suite.yml

# Here is an example of a test import
-
  import: imports/headings

# Here is an example of an assertion import
-
  visit: /foo.html
  find:
    -
      import: imports/find/sections
```

> Imports can only be configured via interpolation (see below). Other configurable options for reusable code to consider are: [shorthand](@shorthand), [options](@options) or [plugins](@plugins).

## Explained

The concept of imports is not new. Write a partial and include it in other files. You can do this too with _Check Pages_.

If you write five suites and realize that you repeat the same tests in several, this is a time to consider an import. It creates coupling and dependency, so it may or may not be a good idea.

What used to look like this, repeated in several suites:

```yaml
# files: suite.yml, suite2.yml, suite3.yml, ...

-
  visit: /
  find:
    -
      dom: h1
      set: title
-
  visit: /contact
  find:
    -
      dom: h1
      set: contactTitle
-
  visit: ...
```

Can be moved to a file called _imports/_headings.yml_ and those repeated sections in your suite files can be replaced with an import, like this:

```yaml
# files: suite.yml, suite2.yml, suite3.yml, ...

-
  import: imports/headings
-
  visit: ...
```

## Key Points

* Where you save import files is up to you, _imports_ directory is given as an example. The value of `import` must be a resolvable path.
* The leading underscore is optional, and like SASS partials it is ignored by the parser. You may use it or not, that is to say _\_headings.yml_ and _headings.yml_ are seen as the same import file.
* The extension is optional and when excluded, is assumed as _.yml_.
* A single import line in your test (one YAML array element) maps to one or many tests in the import file. That is to say, a single import can include one test, or several tests.
* If you use `why` as a sibling to `import`, that is only for your test reading, it will not be printed when the test is run. So think of it as answering the question of "Why use this import?", if you use it.
* You may use an import to substitute _tests_ or _assertions_; see the examples below.
* It's good practice to put your find imports in a subfolder _find_ as it's easier to reason about.
* Imports cannot recursively import other imports at this time.

## Configure Imports with Interpolation

```yaml
# file: suite.yml
-
  why: Set `id` which is used in our import file to build the URL.
  set: baz
  is: 123

-
  import: imports/setup_timesheet
```

```yaml
# file: imports/_setup_timesheet.yml
-
  url: /foo/bar/${baz}
```

## Usage by Other Handlers

Use this pattern in other plugins to implement imports.

```php
$importer = new \AKlump\CheckPages\Handlers\Importer($test->getRunner());
$importer->resolveImports($config['form']['input']);
```

In this example the form plugin allows the usage of imports, e.g.,

```yaml
-
  url: /form.html
  form:
    dom: form
    input:
      -
        import: imports/form_data
```
