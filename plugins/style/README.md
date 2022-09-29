# Style Plugin: Assertions Against CSS

## Assert a DOM element is visible

This example checks the CSS for both display and opacity properties to determine if the modal is visible based on CSS.

```yaml
find:
  -
    dom: .modal
    count: 1
  -
    dom: .modal
    style: display
    matches: /^(?!none).+$/
  -
    dom: .modal
    style: opacity
    is: 1
```

