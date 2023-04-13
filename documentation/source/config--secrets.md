# Config Secrets

You may need to use secrets in your configuration. You should not include them directly in the configuration files, which are version controlled. Instead do like this:

1. Create a file called _secrets.yml_ in the same directory as your main configuration file (_dev.yml_).
2. Add _secrets.yml_ to your _.gitignore_
1. Add secret values to this file.
2. In the main configuration file, use interpolation to reference the secrets.
3. Note: the same _secrets.yml_ will be shared for all configuration files in the same directory.

_config/secrets.yml_

```yaml
new:
  api:
    secret: OWqQtt741kDd6x8c4zZgK0kCKXc51mZ
```

_config/dev.yml_

```yaml
...
extras:
  apiSecret: ${new.api.secret}
```

_runner.php_

```php
load_config('config/dev.yml');
```
