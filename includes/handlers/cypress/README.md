# Cypress

Provides a wrapper around Cypress to incorporate Cypress testing from within Check Pages. Allows passing of Check Pages variables to Cypress for passing of context.

## Configuration

```yaml
extras:
  cypress:
    cypress: /Users/aklump/Code/Projects/ContechServices/AuroraTimesheet/site/app/node_modules/.bin/cypress
    config_file: /Users/aklump/Code/Projects/ContechServices/AuroraTimesheet/site/app/cypress/config/dev.config.js
    spec_base: /Users/aklump/Code/Projects/ContechServices/AuroraTimesheet/site/app/cypress/e2e/

```

## _suite.yml_ Example

```yaml
-
  set: timesheet.id
  value: 240
-
  set: timesheet.worker.id
  value: 160
-
  why: Demonstrate the Cypress Handler syntax.
  cypress: worker/3632.cy.js
  env:
    user: site_test.worker1
    visit: node/${timesheet.id}/approve/${timesheet.worker.id}

```

## Passing User Credentials From the Drupal Mixin to Cypress

1. Use the drupal mixin to log in as normal.
2. Use variable interpolation to send values via `env`

```shell
- cypress: lorem.cy.js
  user: site_test.admin
  env:
    admin_username: ${user.name}
    admin_password: ${user.pass}
```

## Todo

- debug output
- update title correctly
- implement https://docs.cypress.io/guides/guides/command-line#Debugging-commands
