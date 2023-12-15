# Bash

Allows your tests to execute BASH commands and capture results for interpolation.

You can use this to leverage Drush, for example to delete a Drupal user.

```yaml
-
  bash: drush ucan test_user --delete-content -y

-
  bash: date
  set: currentDate
```

## Todo

- Make assertions on the output.
