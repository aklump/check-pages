# Suite Groups

It may be helpful to combine suites into groups.

This is done by placing one or more suites into a folder. The name of the folder containing the suites is the group ID.

That's it.

You can use the `--group=GROUP ID` to filter by group.

Given this example structure...

```
.
└── suites
    ├── admin
    │   └── status.yml
    ├── api
    │   ├── crud.yml
    │   ├── scenario1.yml
    │   └── scenario2.yml
    └── web_ui
        ├── contact.yml
        ├── footer.yml
        └── homepage.yml
```

You are able to use these CLI arguments:

```shell
--group=admin
--group=api
--group=web_ui
```
