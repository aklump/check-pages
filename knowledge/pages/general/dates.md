<!--
id: dates
tags: ''
-->

# Dates

**The YAML parser will convert unquoted strings which look like a date or a date-time into a Unix timestamp. To avoid this you must quote your dates as shown below.**

```yaml
-
  my_custom_mixin:
    -
      d: '2023-03-09'
      cd: 8
      or: 100
      cf: soft
```

[learn more](https://symfony.com/doc/current/components/yaml.html#date-handling)
