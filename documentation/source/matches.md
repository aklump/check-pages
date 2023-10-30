# The Power of Matches

The following example shows how you can use `matches` to capture the user ID from the `href` of an element on the page.

When you combine `matches` with `set` the value that is set is equal to the matched portion.

However, if you add groups to your RegEx, the set value is going to be the last group matched, that is the results array item with the highest numeric key. That is why the code below works, because we capture the user ID as `$matches[1]` and thus `newUserId` would not be `user/123` but rather `123`.

```yaml
- why: Capture UID of the new user.
  user: site_test.admin
  url: /admin/people
  find:
    - dom: .dropbutton__item>a
      attribute: href
      matches: '#\/user\/(\d+)\/#'
      set: newUserId

- why: Assert membership date was updated correctly.
  user: site_test.admin
  url: /user/${newUserId}/edit
  find:
    - dom: .t-field_membership_expires
      attribute: value
      matches: '#^\d{4}\-10\-20$#'      
```
