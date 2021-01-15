## Javascript Testing Requirements

* Your testing machine must have Chrome installed.

## Javascript Testing Setup

To support JS testing, you must indicate where your Chrome binary is located in your runner configuration file, like so:

```yaml
chrome: /Applications/Google Chrome.app/Contents/MacOS/Google Chrome
```

## Enable Javascript per Test

Unless you enable it, javascript is not run during testing.  If you need to assert that an element exists, which was created from Javascript (or otherwise need javascript to run on the page), you will need to indicate the following in your test, namely `js: true`.

```yaml
- visit: /foo
  js: true
  find:
    - dom: .js-created-page-title
      text: Javascript added me to the DOM!
```

## Javascript Testing Related Links

* [Learn more](https://developers.google.com/web/updates/2017/04/headless-chrome)
* https://github.com/GoogleChrome/chrome-launcher
* <https://peter.sh/experiments/chromium-command-line-switches/>
* https://raw.githubusercontent.com/GoogleChrome/chrome-launcher/v0.8.0/scripts/download-chrome.sh
