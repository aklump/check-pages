<!--
id: images
tags: ''
-->

# Testing Images

## Checking Native Dimensions

```yaml
-
  why: Capture the url of the image
  url: /admin/config/media/image-styles/manage/thumbnail
  find:
    -
      dom: .modified-image img
      attribute: src
      set: image_url
-
  why: Load the image so it renders without CSS constraint
  url: ${image_url}
  find:
    -
      javascript: document.querySelector('img').naturalHeight
      set: image_height
    -
      eval: ${image_height} == 100
    -
      javascript: document.querySelector('img').naturalWidth
      set: image_width
    -
      eval: ${image_width} == 100

```
