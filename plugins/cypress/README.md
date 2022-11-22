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

## Todo

- debug output
- update title correctly
- user feedback currently sucks.
- implement https://docs.cypress.io/guides/guides/command-line#Debugging-commands
