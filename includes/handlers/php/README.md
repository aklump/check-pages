# PHP

* Allows your tests to evalulate PHP for interpolation.
* If the exit code is 0, the test will be passed. Anything else will fail it.

```yaml
-
  why: Get the current unix timestamp for interpolation as ${timestamp}
  php: echo time()
  set: timestamp
```
