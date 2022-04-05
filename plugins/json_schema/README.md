# Testing APIs with JSON Schema

You can test APIs using [JSON Schema](https://json-schema.org/), here's the basic idea...

```text
.
├── runner.php
├── schemas
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
          schema: schemas/object.json
        -
          schema: schemas/array.json
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
