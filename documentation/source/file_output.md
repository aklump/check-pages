---
id: files
---

# File Output

1. Create a writeable directory for file output.
2. Add that path to `files` in your runner config.

```yaml
files: files/dev
```

Once this is configured and exists:

* Each suite will create a subdirectory by it's filename.
* In that directory the following files will be created:
    * _urls.txt_ a list of urls that failed testing.
    * _failures.txt_ verbose output of the failures only.

Try using `tail -f files/SUITE/urls.txt` during development.
