# Plugins

To add new functionality to `find`...

1. Create a unique folder in _plugins/find_ with the following structure.  In this example the new plugin will be called `click`.

```
├── plugins/find
│   └── click
│       └── schema.json
```

1. Write the schema file.
1. Write the _tests_suite.yml_ file which will operate against _example/web/_; mo 
