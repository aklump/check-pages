# Using `why` For Better Clarity

The test suite output will automatically generate a line for each assertion, which in many cases is sufficient for test readability. However you may use the `why` key in your tests if you wish. This serves two purposes, it makes the tests more clear and readable, and it makes the test output more understandable, and especially if you're trying to troubleshoot a failed assertion.

# At the Test Level

You may attach `why` to a test, like this...

```yaml
-
  why: Assert homepage has lorem text.
  url: /index.php
  find:
    - Lorem ipsum.
```

```
⏱  RUNNING "SUITE" SUITE...
🔎 Assert homepage has lorem text.
👍 http://localhost:8000/index.php
├── HTTP 200
├── Find "Lorem ipsum" on the page.
└── Test passed.
```

# At the Assert Level

... or to any assertion, like this:

```yaml
-
  url: /index.php
  find:
    -
      dom: h1
      why: Assert page title is lorem text.
      text: Lorem ipsum
```

```
⏱  RUNNING "SUITE" SUITE...
👍 http://localhost:8000/index.php
├── HTTP 200
├── Assert page title is lorem text.
└── Test passed.
```
