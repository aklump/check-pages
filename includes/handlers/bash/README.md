# Bash

Allows your tests to execute BASH commands.

You can use this to leverage Drush, for example to delete a Drupal user.

```yaml
- bash: drush ucan test_user --delete-content -y
```

## Todo

- Make assertions on the output.
- Set the output to a variable.
