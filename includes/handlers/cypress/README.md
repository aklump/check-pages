# Cypress

Provides a wrapper around Cypress to incorporate Cypress testing from within Check Pages. Provides a means os passing Check Pages variables to Cypress via Environment Variables.

## Configuration

Note: use paths relative to the configuration file.

```yaml
extras:
  cypress:
    cypress: ../../node_modules/.bin/cypress
    config_file: ../../cypress/config/dev.config.js
    spec_base: ../../cypress/e2e/
```

## Share Configuration with Cypress

Set up _Check Pages_ to be the canonical configuration for both _Check Pages_ and _Cypress_.

1. Use JSON for _Check Pages_ configuration since Cypress doesn't support YAML.
3. Create this file, e.g. _check_pages/config/dev.json_.
4. Create a symlink to _check_pages/config/dev.json_ as _cypress/config/check_pages.dev.json_
5. Require the file in your _Cypress_ configuration and implement values. Here is an example that uses `base_url`:

     ```javascript
     const { defineConfig } = require('cypress');
     const checkPages = require('./check_pages.dev.json');
     
     module.exports = defineConfig({
       e2e: {
         baseUrl: checkPages.base_url,
       },
     });
     ```

## Pass Environment Variables to Cypress
You can pass env vars per test by doing something like this in your _suite.yml_ file:
```shell
-
  cypress: lorem.cy.js
  env:
    foo: bar
-
  cypress: ipsum.cy.js
  env:
    alpha: bravo
```

## Share Users with Cypress

1. Create _tests_check_pages/config/users.json_.

      ```json
      [
        {
          "name": "site_test.authenticated",
          "pass": "pass"
        }
      ]
      ```

2. Create a symlink to the above as _cypress/fixtures/users.json_.
3. Create _cypress/config/dev.config.js_ with the following.  **This provides the users array to Cypress to be accessed as needed.**

      ```javascript
      const { defineConfig } = require('cypress');
      const users = require('../fixtures/users.dev.json');
      
      module.exports = defineConfig({
        e2e: {
          setupNodeEvents(on, config) {
            return {
              ...config,
              users: users,
            };
          },
        },
      });
      ```

4. Create _cypress/support/utils.js_ with a utility to easily get a user object.

      ```javascript
      export function getUserByName(username) {
        return Cypress.config('users').find(user => user.name === username);
      }
      ```

1. Create a test, e.g., _cypress/e2e/login.cy.js_ and implement `getUserByName`:

      ```javascript
      import { getUserByName } from '../../support/utils';
      
      describe('', () => {
        it('', () => {
          const user = getUserByName('site_test.authenticated');
          cy.get('#username').invoke('val', user.name);
          cy.get('#password').invoke('val', user.pass);
        });
      });
      ```

## Todo

- debug output
- update title correctly
- implement https://docs.cypress.io/guides/guides/command-line#Debugging-commands
