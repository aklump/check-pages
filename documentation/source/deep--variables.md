<!--
id: variables
-->

# Variables

* The user ID of the authenticated user is available to your suite as `${id}`.
* You can capture the value of h1 into a variable as `${title}` and used in a subsequent test.
* With javascript you can read the url into `${url}` and assert against it.

All of these are examples of variables.

## Sharing Values Between Tests

You create variables like this:

```yaml
-
  set: title
  is: 'Lorem Ipsum'
```

Which can be used like this:

```yaml
-
  set: title
  is: 'Lorem Ipsum'
-
  visit: /foo.html
  find:
    -
      dom: h1
      is: ${title}
```

**Note: Interpolation will only occur if the variable exists.** That means that in the following example, the second assert will assert against the literal value `${other}`.

```yaml
-
  set: title
  is: 'Lorem Ipsum'
-
  visit: /foo.html
  find:
    -
      dom: h1
      is: ${title}
    -
      dom: h2
      is: ${other}
```

## Using Response Values

In this example we'll `GET` a resource ID and store it using `set`, then `DELETE` it in a subsequent test using interpolation.

```yaml
-
  visit: /api/2/foo
  find:
    -
      node: collection.items.data.0.id
      set: fooId

-
  url: /api/2/foo/${fooId}
  request:
    method: delete
  expect: 204
```

## Scope

Variables used this way are scoped to the suite. They can be shared across tests and assertions, but not across suites.

## Variable Re-Assignment

The authentication plugin will create `${user.uid}`, however it will be overwritten on the next authentication. To capture and reassign to a different variable name do like the following:

```yaml
-
  why: Capture UID from session for reassignment
  user: foo_user
  visit: /

-
  why: Reassign to authenticated user ID to variable fooUserId
  set: fooUserId
  is: ${user.uid}

-
  why: Assert the title of Foo's user page is correct.
  visit: /user/${fooUserId}
  find:
    -
      dom: h1
      text: Foo User
```
