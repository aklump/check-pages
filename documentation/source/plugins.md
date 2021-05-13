# Plugins

To add new functionality to `find`...

1. Create a unique folder in _plugins_ with the following structure. In
   this example the new plugin will be called `click`.

```
├── plugins
│   └── find
│       └── click
│           └── schema.json
```

1. Write the find portion of the schema file as _schema.find.json_.

```json
{
    "type": "object",
    "required": [
        "click"
    ],
    "properties": {
        "click": {
            "$ref": "#/definitions/dom"
        }
    },
    "additionalProperties": false
}
```

1. Optionally, you may provide definitions in the schema as _
   schema.definitions.json_, e.g.,

```json
{
    "js_eval": {
        "type": "string",
        "pattern": "^.+$",
        "examples": [
            "location.hash"
        ]
    }
}
```

1. Write the _suite.yml_ file which will be run against _test_subject.html_ or _
   test_subject.php_
1. Create _test_subject.html_ or _test_subject.php_ as needed to test _
   suite.yml_.
