# Testing APIs with JSON Schema

You can test APIs using [JSON Schema](https://json-schema.org/), here's the basic idea...

```text
.
├── runner.php
├── json_schema
│   └── object.json
└── suite.yml
```

1. Create a JSON file with the JSON schema and save it somewhere, e.g. _schemas/object.json_.

    ```yaml
   # file: object.json
   ```
   ```json
    {
        "type": "object"
    }
    ```
2. Then write a test to use that schema:

    ```yaml
    # file: suite.yml
    -
      visit: /api/2/thing/99
      find:
        -
          schema: json_schema/object.json
        -
          schema: json_schema/array.json
          matches: false
    ```
3. `matches` may also be `false`; it may be omitted and defaults to `true`.
4. Notice the usage of a second schema to use as a NOT match, in this case _schemas/array.json_.
5. The `content-type` header will be used to decode the response body.
6. `schema` should be resolvable filepath, or a JSON string representing a schema.

## Testing Only a Portion of the Response

You may also apply a schema on just part of the reponse data using the `path` modifier.

1. Given the following response:

   ```json
   {
      "lorem": {
           "ipsum": [
               {
                   "dolar": true
               }
           ]
       }
   }
   ```

2. You can apply a schema to the array at `ipsum`. Provide the schema subject using dot notation as `path`, in this case `lorem.ipsum`. Also notice that `schema` can be a JSON string, and doesn't have to reference a filepath.

   ```yaml
   # file: suite.yml
   -
     visit: /api/2/thing/99
     find:
       -
         schema: '{"type":"array"}'
         path: lorem.ipsum
   ```

## References in JSON Schema

* [learn more](https://opis.io/json-schema/2.x/references.html)

Here's now to write a schema that references another file.

The file that provides the `$defs` is called (in the case and arbitrary) _shared.json_:

```json
{
  "$defs": {
    "date": {
      "type": "string",
      "pattern": "^\\d{4}\\-\\d{2}\\-\\d{2}[ T]\\d{2}:\\d{2}:\\d{2}$"
    }
  }
}
```

Here is the file that references the date property. Two examples are given, which are effectively identical. You cannot use `file:` prefix as shown online in some examples.

```json
{
  "type": "array",
  "items": {
    "type": "object",
    "properties": {
      "date": {
        "$ref": "./shared.json#/$defs/date"
      },
      "date": {
        "$ref": "shared.json#/$defs/date"
      }
    }
  }
}
```


