<!--
id: sizing_elements
tags: ''
-->

# Sizing DOM Elements

## Assert a DOM element has a height greater than N

```yaml
-
  url: /
  find:
    -
      why: Assert the element to be measured.
      dom: '[data-test="foobar"]'
      count: 1
    -
      why: Measure the line height so we know what two lines amount to.
      dom: '[data-test="foobar"]'
      style: line-height
      set: lineHeight
    -
      why: Get the calculated height of the element.
      javascript: document.querySelector('[data-test="foobar"]').getBoundingClientRect().height
      set: height
    -
      why: Assert the height is greater than the threshold.
      eval: ${nameHeight} >= (${lineHeight} * 2)
```
