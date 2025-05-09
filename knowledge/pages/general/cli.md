<!--
id: cli
tags: ''
-->

# Command Line Runner

## Filter

Use `--filter` to limit which suites are run.

The value passed to the filter will be matched against the `$group/$id` of the suite. Behind the scenes it is treated as a regex pattern, if you do not include delimiters, they will be added and case will not matter.

**Be careful with regex filters on the CLI; and be sure to wrap the filter in single quotes to prevent BASH interpolation.**

This is correct:

```shell
bin/run_check_pages_tests.sh --filter='#api_day/day$#'
```

Given the following test suites...

```text
.
├── api
│   ├── menus.yml
│   ├── reports.yml
│   └── users.yml
└── ui
    ├── footer.yml
    ├── login.yml
    └── menus.yml
```

| CLI                 | Matches                                |
|---------------------|----------------------------------------|
| `--filter=ui/`      | ui/footer.yml, ui/login.yml, menus.yml |
| `--filter=/menus`   | api/menus.yml, ui/menus.yml            |
| `--filter=ui/menus` | suites/ui/menus.yml                    |

Notice the usage of the `/` separator to control how the group influences the result.

### Complex Filter

It's possible to provide a complex filter that uses `or` logic like this:

    ./checkpages runner.php -f reports -f menus

## Troubleshooting

Try using the `--response` to see the response source code as well.

    ./checkpages runner.php --response

