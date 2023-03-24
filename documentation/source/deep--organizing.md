# Filenaming and Organization

Given the scenario of a live server, a test server, and a local, development server, you will create three config files respective to each environment.

```
├── config
│   ├── dev.yml
│   ├── live.yml
│   └── test.yml
```

Then you will create some suites that are to be run on all environments

```
└── suites
    ├── bar.yml
    ├── baz.yml
    └── foo.yml
```

But let's say you have a suite that should only be run against the live environment. Here's an example that tests proper redirection and SSL prefixing and requires that the `url` values be absolute links. By definition then this suite should only run against a single server.

```yaml
-
  why: Assert canonical, secure does not redirect.
  visit: https://www.mysite.org
  status: 200
  location: https://www.mysite.org
-
  loop:
    - http://www.mysite.org
    - https://mysite.org
    - http://mysite.org
-
  why: Assert ${loop.value} redirects to SSL, canonical.
  visit: ${loop.value}
  status: 301
  location: https://www.mysite.org
-
  end loop:

```

To keep this clear adhere to the following convention.

### Step One

**Prefix the suite with the config filename, e.g. _live.redirects.yml_.**

```
└── suites
    ...    
    ├── bar.yml
    ├── baz.yml
    ├── dev.redirects.yml
    ├── foo.yml
    ├── live.redirects.yml
    └── test.redirects.yml
```

### Step Two

**List these as `suites_to_ignore` in the appropriate config files.**

In _config/dev.yml_:

```yaml
suites_to_ignore:
  - live.redirects
  - test.redirects
```

In _config/live.yml_:

```yaml
suites_to_ignore:
  - dev.redirects
  - test.redirects
```

In _config/test.yml_:

```yaml
suites_to_ignore:
  - dev.redirects
  - live.redirects
```

## Why Not Use Groups?

In my experience groups (folders) should not be used to indicate configuration-specific suites, but there is nothing technically stopping this strategy.
