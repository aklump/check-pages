# Using With Lando

If you get a sockets nto installed error add this:

```yaml
services:
    build_as_root:
      - apt-get update
      - apt-get install -y autoconf
      - docker-php-ext-install sockets
```

* https://github.com/lando/docs/issues/62#issuecomment-578870310
