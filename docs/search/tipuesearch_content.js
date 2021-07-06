var tipuesearch = {"pages":[{"title":"Changelog","text":"  All notable changes to this project will be documented in this file.  The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.  [0.12.0] - 2021-07-06  Added   why key for message overrides. Disk storage of sessions storage across runners.  See docs for more info.   [0.11.0] - 2021-07-03  Added   Create tests directory prompt on package install. Authentication for Drupal 7 and Drupal 8 via with_extras() function. add_test_option() function for custom functionality.   Removed   composer create-project is no longer supported as it was too confusing and unnecessary to have two installation means.   [0.10.0] - 2021-05-28  Added   is not not matches   Changed   exact is now is; change all usages. match is now matches; change all usages. none is now not contains; change all usages.   Removed   exact match none   Fixed   JS error when the eval is used more than once per test.   [0.9.0] - 2021-05-28  Added   The header assertion plugin   [0.8.0] - 2021-04-14  Added   The javascript selector for expression evaluation.   Changed   It's no longer required to add js: true to a test implementing a style selector. It will now be forcefully set (or overridden) to true. This is because the style selector only works when javascript is enabled.   [0.7.0] - 2021-04-08  Added   Added the none assertion to ensure a substring does not appear on the page.   [0.6.0] - 2021-01-16  Added   Added new selector 'attribute'. Added ability to do style asserts. Added globbing to run_suite(), e.g. run_suite('*') to run all suites. Normal glob patterns work as well, which are relative to the --dir directory, or defaults to the directory containing runner.php.   Changed   run_suite() now returns void().   [0.5.1] - 2021-01-14  Added   The alias visit: may be used instead of url: Examples now show using visit:, though url: still works.   [0.5] - 2021-12-30  Added   --filter parameter to limit runner to a single suite from the CLI.   [0.4] - 2020-12-01  Added   Javascript support with Chrome.   Changed   The way the CSS selector works has changed fundamentally and may break your tests. Refer to the following test YAML. Prior to 0.4 that would only choose the first .card__title on the page and assert it's text matched the expected. Starting in 0.4, all .card__titles found on the page will be matched and the assert will pass if any of them have matching text.  -   visit: \/foo\/bar   find:     -       dom: .card__title       text: The Top of the Mountain  If you need the earlier functionality you should use the xpath selector as shown here to indicate just the first element with that class.  -  visit: \/foo\/bar  find:    -      xpath: '(\/\/*[contains(@class, \"card__title\")])[1]'      text: The Top of the Mountain    [0.3] - 2020-08-15  Added   Added the --quiet flag   Changed   The default output is now how it was when adding the --debug flag, use the --quiet flag for less verbosity. Visual layout to make reading results easier and more clear.   Removed   The --debug flag  ","tags":"","url":"CHANGELOG.html"},{"title":"Check Pages","text":"  Very Simple QA for Websites    Summary  This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain. You write your tests using YAML and they can look as simple as this:  # Check the homepage to make sure it returns 200. - visit: \/  # Make sure the `\/admin` path returns 403 forbidden when not logged in. - visit: \/admin   expect: 403   In a third test we can assert there is one logo image on the homepage, like so:  - visit: \/   find:     - dom: '#logo img'       count: 1   Lastly, make sure there are no unprocessed tokens on the page (a.k.a. a substring does not appear):  - visit: \/   find:     - not contains: '[site:name]'   For more code examples explore the \/examples directory.  Visit https:\/\/aklump.github.io\/check_pages for full documentation.  Clarity of Purpose and Limitation  The mission of this tool is to provide testing for URLS and webpages in the most simple and concise syntax possible. For testing scenarios that require element interaction, such as clicks, hovers, scrolling, etc, there are amazing projects out there such as Cypress. This project will never try to compete with that crowd, and shall always restrict it's testing of the DOM to assertions against a single snapshot of the loaded URL.  Terms Used   Test Runner - A very simple PHP file that defines the configuration and what test suites to run, and in what order. @see includes\/runner.php. Test Suite - A YAML file that includes one or more checks against URLs. @see includes\/suite.yml. Test - A single URL check within a suite. Assertion - A single check action against the HTTP response of a test, i.e., headers, body, status code, javascript, etc.   Requirements   You must install with Composer. Tests suites are written in YAML. Little to no experience with PHP is necessary. Copy and paste will suffice.   Install  $ composer require aklump\/check-pages --dev    In most cases the --dev is appropriate, but use your own discretion. You will be asked if you want to create a directory for your tests when you install. This will copy over a basic scaffolding to build from. More detailed examples are located in the example directory.   Example Tests Demo  If you are new to this project and would like to see a demonstration, it would be a good idea to start with the examples. Run the example tests with the following commands. Then open up the files in the example\/tests directory and study them to see how they work.1   Open a new shell window which will run the PHP server for our example test pages.  $ .\/bin\/test_server.sh  Open a second shell window to execute the tests.  $ .\/bin\/test.sh    Some failing tests are also available to explore:  $ .\/check_pages failing_tests_runner.php   1 If you see no tests directory then create one and copy the contents of examples into tests. The example tests directory will only be created if you use create-project as the installation method.  Writing Your First Test Suite  If you created a test directory on install then you're ready to build on that.  If you did not you can do that now by running the script in vendor\/bin\/check_pages_init  Multiple Configuration Files  The project is designed to be able to run the same tests using different configurations. You can create multiple configuration files so that you are able to run the same test on live and then on dev, which have different base URLs.  . \u2514\u2500\u2500 tests     \u251c\u2500\u2500 config\/dev.yml     \u251c\u2500\u2500 config\/live.yml     \u251c\u2500\u2500 suite.yml     \u2514\u2500\u2500 runner.php   In runner.php use the following line to specify the default config file:  load_config('config\/dev');   When you're ready to run this using the live config add the config filename to the CLI command, e.g.,  $ .\/check_pages runner.php --config=config\/live   Test functions  The test functions for your PHP test files are found in includes\/runner_functions.inc.  Is JS Supported?  Yes, not by default, but you are able to indicate that given tests requires Javascript be run. Read on...  Javascript Testing Requirements   Your testing machine must have Chrome installed.   Javascript Testing Setup  To support JS testing, you must indicate where your Chrome binary is located in your runner configuration file, like so:  chrome: \/Applications\/Google Chrome.app\/Contents\/MacOS\/Google Chrome   Enable Javascript per Test  Unless you enable it, or in the case the selector type (i.e., style , javascript) requires it, javascript is not run during testing. If you need to assert that an element exists, which was created from Javascript (or otherwise need javascript to run on the page), you will need to indicate the following in your test, namely js: true.  -   visit: \/foo   js: true   find:     -       dom: .js-created-page-title       text: Javascript added me to the DOM!   Asserting Javascript Evaluations  Let's say you want to assert the value of the URL fragment. You can do that with the javascript selector. The value of javascript should be the expression to evaluate, once the page loads. Notice that you may omit the js: true as it will be set automatically.  -   visit: \/foo   find:     -       javascript: location.hash       is: \"#top\"   Javascript Testing Related Links   Chrome DevTools Protocol 1.3 Learn more CLI parameters More on parameters https:\/\/github.com\/GoogleChrome\/chrome-launcher https:\/\/peter.sh\/experiments\/chromium-command-line-switches\/ https:\/\/raw.githubusercontent.com\/GoogleChrome\/chrome-launcher\/v0.8.0\/scripts\/download-chrome.sh   Quiet Mode  To make the output much simpler, use the --quite flag. This will hide the assertions and reduce the output to simply pass\/fail.  .\/check_pages failing_tests_runner.php --quiet   Filter  Use the --filter parameter combined with a suite name to limit the runner to a single suite. This is faster than editing your runner file.  .\/check_pages runner.php --filter=page_header   Troubleshooting  Try using the --show-source to see the response source code as well.  .\/check_pages failing_tests_runner.php --show-source   Usage  In this case, since the project will be buried in your vendor directory, you will need to provide the directory path to your test files, when you run the test script, like this:  .\/vendor\/bin\/check_pages runner.php --dir=.\/tests_check_pages   This example assumes a file structure like this:  . \u251c\u2500\u2500 tests_check_pages \u2502\u00a0\u00a0 \u2514\u2500\u2500 runner.php \u2514\u2500\u2500 vendor     \u2514\u2500\u2500 bin         \u2514\u2500\u2500 check_pages     Contributing  If you find this project useful... please consider making a donation. ","tags":"","url":"README.html"},{"title":"Authentication","text":"  If you want to check pages as an authenticated user of a website, then you have to provide a login option to your test suites.  Drupal 8   Create a YAML or JSON file containing user login credentials. Do not commit this to source control. You can place this whereever, but in this example it will be located in the config directory as config\/users.yml. You may list as many users as you want. Each record must have the keys name and pass.  -  name: admin  pass: 123pass -  name: member  pass: secret5  Add the following to your test runner file. Notice we include the path to the user data file. It must be a resolvable path.  with_extras('drupal8', [   'users' =&gt; 'config\/users.yml', ]);   If the login form is located at a non-standard URL, you may indicate that URL, which renders the login form, as shown here.  with_extras('drupal8', [   'users' =&gt; 'config\/users.yml',   'login_url' =&gt; '\/login', ]);   In your test suite add the line user key to each test with the value of the username to be logged in as when visiting the URL.  -   user: admin   visit: \/admin -   user: member   visit: \/admin   expect: 403    Drupal 7   Follow instructions for Drupal 8, but change the first argument to with_extras() to drupal7, e.g.,  with_extras('drupal7', [   'users' =&gt; 'config\/users.yml', ]);     Custom Authentication  You can build your own authentication using the add_test_option() function. Refer to drupal8.inc as a starting point. ","tags":"","url":"authentication.html"},{"title":"Quick Reference: Test Writing","text":"  {% include('_cheatsheet.md') %}  Check Page Loads  The most simple test involves checking that a page loads.  -   visit: \/foo   Check with Javascript Enabled  By default the test will not run with Javascript. Use js: true to run the test with Javascript enabled.  Learn more.  Check Status Code  By saying that the \"page loads\", we mean that it returns a status code of 200. The following is exactly the same in function as the previous example. You can check for any code by changing the value of expect.  -   visit: \/foo   expect: 200   Check Redirect  For pages that redirect you can check for both the status code and the final location.  (redirect is a synonym for location.)  -   visit: \/moved.php   expect: 301   location: \/location.html  -   visit: \/moved.php   expect: 301   redirect: \/location.html   Check Content  Once loaded you can also look for things on the page with find. The most simple find assertion looks for a substring of text anywhere on the page. The following two examples are identical assertions.  -   visit: \/foo   find:     - Upcoming Events Calendar   -   visit: \/foo   find:     -       contains: Upcoming Events Calendar   Ensure something does NOT appear on the page like this:  -   visit: \/foo   find:     -       none: \"[token:123]\"   Selectors  Selectors reduce the entire page content to one or more sections.  {% include('_selectors.md') %}  Assertions  Assertions provide different ways to test the page or selected section(s).  In the case where there are multiple sections, such as multiple DOM elements, then the assertion is applied against all selections and only one must pass.  {% include('_assertions.md') %} ","tags":"","url":"cheatsheet.html"},{"title":"How To Use A Custom Directory For Suite Files","text":"  You may locate suite files in directories other than the main one, by registering those directories with the add_directory() function. After that run_suite() will also look for suite names in the added directory(ies).  &lt;?php \/**  * @file  * Assert URLs as an anonymous user.  *\/ load_config('config\/dev.yml'); add_directory(realpath(__DIR__ . '\/..\/web\/themes\/custom\/gop_theme\/components\/smodal'));  run_suite('*');  \/\/ Glob doesn't yet work with add_directory(), so you have to list the suite \/\/ explicitly like this: run_suite('smodal');   ","tags":"","url":"custom_dirs.html"},{"title":"Developers","text":"  Plugins &amp; Testing  Do not edit the following, as they are created in the build step and will be overwritten. To affect these files you need to look to plugins\/ directory, which contains the source code.  . \u251c\u2500\u2500 tests \u2502     \u251c\u2500\u2500 plugins \u2502     \u2502     \u251c\u2500\u2500 foo.yml \u2502     \u2502     \u2514\u2500\u2500 javascript.yml \u2502     \u251c\u2500\u2500 runner_plugins.php \u2514\u2500\u2500 web     \u2514\u2500\u2500 plugins         \u251c\u2500\u2500 foo.html         \u2514\u2500\u2500 javascript.html   ","tags":"","url":"developers.html"},{"title":"Examples","text":"  Assert a DOM element is visible  This example checks the CSS for both display and opacity properties to determine if the modal is visible based on CSS.  find:   -     dom: .modal     count: 1   -     style: .modal     property: display     matches: \/^(?!none).+$\/   -     style: .modal     property: opacity     is: 1   Assert the URL Hash Matches RegEx Pattern  find:     -       javascript: location.hash       matches: \/^#foo=bar&amp;alpha=bravo$\/  ","tags":"","url":"examples.html"},{"title":"Response Header Assertions","text":"  You can assert against response headers like this:  -   visit: \/foo   find:     -       header: content-type       contains: text\/html     header is NOT case-sensitive. But contains is, so... if you're trying to match a header value with case-insensitivity, you should use the match key, with the i flag like this:  - header: content-type   matches: \/text\\\/html\/i    See more examples in example\/tests\/plugins\/header.yml ","tags":"","url":"headers.html"},{"title":"Testing with Javascript","text":"  Javascript Testing Requirements   Your testing machine must have Chrome installed.   Javascript Testing Setup  To support JS testing, you must indicate where your Chrome binary is located in your runner configuration file, like so:  chrome: \/Applications\/Google Chrome.app\/Contents\/MacOS\/Google Chrome   Enable Javascript per Test  Unless you enable it, or in the case the selector type (i.e., style , javascript) requires it, javascript is not run during testing. If you need to assert that an element exists, which was created from Javascript (or otherwise need javascript to run on the page), you will need to indicate the following in your test, namely js: true.  -   visit: \/foo   js: true   find:     -       dom: .js-created-page-title       text: Javascript added me to the DOM!   Asserting Javascript Evaluations  Let's say you want to assert the value of the URL fragment. You can do that with the javascript selector. The value of javascript should be the expression to evaluate, once the page loads. Notice that you may omit the js: true as it will be set automatically.  -   visit: \/foo   find:     -       javascript: location.hash       is: \"#top\"   Javascript Testing Related Links   Chrome DevTools Protocol 1.3 Learn more CLI parameters More on parameters https:\/\/github.com\/GoogleChrome\/chrome-launcher https:\/\/peter.sh\/experiments\/chromium-command-line-switches\/ https:\/\/raw.githubusercontent.com\/GoogleChrome\/chrome-launcher\/v0.8.0\/scripts\/download-chrome.sh  ","tags":"","url":"javascript.html"},{"title":"Plugins","text":"  To add new functionality to find...   Create a unique folder in plugins with the following structure. In this example the new plugin will be called foo.   \u251c\u2500\u2500 plugins \u2502   \u2514\u2500\u2500 find \u2502       \u2514\u2500\u2500 foo \u2502           \u2514\u2500\u2500 schema.json    Write the find portion of the schema file as schema.find.json.   {     \"type\": \"object\",     \"required\": [         \"foo\"     ],     \"properties\": {         \"foo\": {             \"$ref\": \"#\/definitions\/dom_selector\"         }     },     \"additionalProperties\": false }    Optionally, you may provide definitions in the schema as _ schema.definitions.json_, e.g.,   {     \"js_eval\": {         \"type\": \"string\",         \"pattern\": \".+\",         \"examples\": [             \"location.hash\"         ]     } }    Write the suite.yml file which will be run against test_subject.html or _ test_subject.php_ Create test_subject.html or test_subject.php as needed to test _ suite.yml_.  ","tags":"","url":"plugins.html"},{"title":"Search Results","text":" ","tags":"","url":"search--results.html"},{"title":"File Storage","text":"  Authentication can slow down your tests. To mitigate this you can create a writeable folder at files\/storage and the session cookies will be written to disk the first time they are obtained from the test subject. After that, authentication will skip the login step and pull the session data from files\/storage. Protect the contents of that directory because it contains credentials.  If you do not want this feature, then make sure that files\/storage does not exist. ","tags":"","url":"storage.html"},{"title":"Using <code>why<\/code> For Better Clarity","text":"  The test suite output will automatically generate a line for each assertion, which in many cases is sufficient for test readability. However you may use the why key in your tests if you wish. This serves two purposes, it makes the tests more clear and readable, and it makes the test output more understandable, and especially if you're trying to troubleshoot a failed assertion.  This can be used at two levels in your test suite.  At the Test Level  -   why: Assert homepage has lorem text.   url: \/index.php   find:     - Lorem ipsum.   \u23f1  RUNNING \"SUITE\" SUITE... \ud83d\udd0e Assert homepage has lorem text. \ud83d\udc4d http:\/\/localhost:8000\/index.php \u251c\u2500\u2500 HTTP 200 \u251c\u2500\u2500 Find \"Lorem ipsum\" on the page. \u2514\u2500\u2500 Test passed.   At the Assert Level  -   url: \/index.php   find:     -       dom: h1       why: Assert page title is lorem text.       text: Lorem ipsum   \u23f1  RUNNING \"SUITE\" SUITE... \ud83d\udc4d http:\/\/localhost:8000\/index.php \u251c\u2500\u2500 HTTP 200 \u251c\u2500\u2500 Assert page title is lorem text. \u2514\u2500\u2500 Test passed.  ","tags":"","url":"why.html"}]};
