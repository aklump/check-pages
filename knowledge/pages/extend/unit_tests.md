<!--
id: unit_tests
tags: ''
-->

# Unit Tests

## Autoloading

Currently the way the handler autoloading works, the unit tests do not have access to those classes, therefor if you write a unit test you need to manually add to the `autoload-dev` array in _composer.json_. Here is an example for the form handler, which has multiple classes.

```json
{
  "autoload-dev": {
    "psr-4": {
      "AKlump\\CheckPages\\Handlers\\Form\\": [
        "includes/handlers/form/",
        "includes/handlers/form/src/"
      ]
    }
  }
}
```

## Test Coverage

Add the files/directories which your handler uses to provide php classes, to _app/tests_unit/phpunit.xml_. You can probably exclude the handler controller class as this will be covered by the check pages test.

```xml

<coverage processUncoveredFiles="true">
    <include>
        <file>../includes/handlers/form/Form.php</file>
        <directory suffix=".php">../includes/handlers/form/src</directory>
    </include>
</coverage>
```
