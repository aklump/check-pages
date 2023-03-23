# Suite Groups

It may be helpful to combine suites into groups.

This is done by placing one or more suites into a folder. A group is defined by the folder containing the suite file(s), where the folder is the group. That's it.

You can use the `--group=<GROUP_ID>` to filter by group.

Given this example structure...

```
.
└── suites
    ├── admin
    │   └── status.yml
    │   ├── scenario1.yml
    ├── api
    │   ├── crud.yml
    │   ├── scenario1.yml
    │   └── scenario2.yml
    └── web_ui
        ├── contact.yml
        ├── footer.yml
        └── homepage.yml
```

Setup the runner like this:

```php
<?php
add_directory(__DIR__ . '/suites');
run_suite('admin/*');
run_suite('api/*');
run_suite('web_ui/*');
```

You are able to use these CLI arguments:

```shell
--group=admin
--group=api

# As well as via alias...
-g web_ui
```

Notice that two groups (admin, api) have scenarios with the same name (scenario1.yml). This is possible, but would require the use of both `--group` and `--filter` to run only one, as you might conclude:

```shell
run runner.php -g admin -f scenario1
```
