# Sharing Values Between Tests

You create variables like this:

```yaml
- set: title
  is: 'Lorem Ipsum'
```

Which can be used like this:

```yaml
- set: title
  is: 'Lorem Ipsum'
- visit: /foo.html
  find:
    - dom: h1
      is: ${title}
```

**Note: Interpolation will only occur if the variable is set.** That means that in the following example, the second assert will assert against the literal value `${other}`.

```yaml
- set: title
  is: 'Lorem Ipsum'
- visit: /foo.html
  find:
    - dom: h1
      is: ${title}
    - dom: h2
      is: ${other}
```

## Using Response Values

In this example we'll `GET` a resource ID and store it using `set`, then `DELETE` it in a subsequent test using interpolation.

```yaml
- visit: /api/2/foo
  find:
    - node: collection.items.data.0.id
      set: fooId

- url: /api/2/foo/${fooId}
  request:
    method: delete
  expect: 204
```

## Scope

Variables used this way are scoped to the suite. They can be shared across tests and assertions, but not across suites.
