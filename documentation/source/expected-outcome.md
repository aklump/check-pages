# When You _Want_ Failure

There are some cases where you want to write a suite that fails, and such a result indicates the desired outcome. That's where `expected outcome: failure` comes into play.

If _bar.html_ does not exist then this test will fail. And the suite will fail. But I only want to know if this particular test **doesn't** fail, so I will add `expected outcome: failure`, which inverts the test result.

```yaml
- url: /bar.html
  expected outcome: failure
```
