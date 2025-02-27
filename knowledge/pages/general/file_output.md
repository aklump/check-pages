<!--
id: files
tags: ''
-->

# File Output

File output is enabled in the runner configuration file. Two things are required to enable this feature:

1. An existing, writeable directory
2. An entry in the runner config with the location of the directory as a resolvable path.

```yaml
files: logfiles/dev
```

Once you have this enabled, handlers will make use of this directory by writing different files related to the testing.
When a suite is run, a subdirectory is created within the directory defined by the configuration.
