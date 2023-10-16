# Understanding How Multiple DOM Elements Are Handled

In this example you see three `<h2>` elements on _index.html_.

```html
<body>
<h2>alpha</h2>
<h2>bravo</h2>
<h2>charlie</h2>
</body>
```

When using the `dom` selector, we get back three headings; using these more specific `xpath` selectors (see below), we get back only one.  Therefore the important thing to fully comprehend when writing tests is:

**Given more than one DOM element, `matches, contains, etc` will pass when at least ONE element passes.**

**In the same case, `not matches, not contains, etc` will ONLY pass if ALL of them pass.**

With that in mind, a passing test for the above HTML would look like this:

```yaml
-
  visit: /index.html
  find:
    -
      dom: 'h2'
      matches: '/charlie/i'
    -
      dom: 'h2'
      not matches: '/delta/i'
    -
      dom: '//*h2[1]'
      not matches: '/charlie/i'
    -
      dom: '//*h2[2]'
      not matches: '/charlie/i'
    -
      dom: '//*h2[3]'
      matches: '/charlie/i'
```
