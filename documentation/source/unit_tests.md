# Unit Tests

Currently the way the handler autoloading works, the unit tests do not have access to those classes, therefor if you write a unit test you need to manually add to the `autoload-dev` array in _composer.json_.
