## Javascript Testing Requirements

* Your testing machine must have Chrome installed.

## Javascript Testing Setup

To support JS testing, you must indicate where your Chrome binary is located in
your runner configuration file, like so:

```yaml
chrome: /Applications/Google Chrome.app/Contents/MacOS/Google Chrome
```

## Enable Javascript per Test

Unless you enable it, or in the case the selector type (i.e., `style`
, `javascript`) requires it, javascript is not run during testing. If you need
to assert that an element exists, which was created from Javascript (or
otherwise need javascript to run on the page), you will need to indicate the
following in your test, namely `js: true`.

```yaml
-
  visit: /foo
  js: true
  find:
    -
      dom: .js-created-page-title
      text: Javascript added me to the DOM!
```

## Asserting Javascript Evaluations

Let's say you want to assert the value of the URL fragment. You can do that with
the `javascript` selector. The value of `javascript` should be the expression to
evaluate, once the page loads. Notice that you may omit the `js: true` as it
will be set automatically.

```yaml
-
  visit: /foo
  find:
    -
      javascript: location.hash
      is: "#top"
```

## Javascript Testing Related Links

* [Chrome DevTools Protocol 1.3](https://chromedevtools.github.io/devtools-protocol/1-3/)
* [Learn more](https://developers.google.com/web/updates/2017/04/headless-chrome)
* [CLI parameters](https://source.chromium.org/chromium/chromium/src/+/master:headless/app/headless_shell_switches.cc)
* [More on parameters](https://developers.google.com/web/updates/2017/04/headless-chrome#command_line_features)
* https://github.com/GoogleChrome/chrome-launcher
* <https://peter.sh/experiments/chromium-command-line-switches/>
* https://raw.githubusercontent.com/GoogleChrome/chrome-launcher/v0.8.0/scripts/download-chrome.sh
