# Testing Forms

The form plugin allows you to test the submission of forms.

## Submit

The submit is a special `dom` selector in that it uses the form as the root, so you can only select elements inside the form AND you should omit any reference to the form element, as it will be prepended automatically.

You only need to use this if the form contains more than one `input[type="submit"]` element.

## Setting Form Values

To provide a form value, add an object to the `input` array as shown below. It must have the keys `name` and `value`, where `name is the `name` attribute of the form element.

### Dates

* You must wrap date strings in quotations like this `value: "2010-01-01"`.

### Select

* Instead of using `value`, you may use `option` to set the value of a `<select>` element. It is case-sensitive. Subject to the _Element Present_ limitation.

## _Element Present_ limitation

The DOM element must be present in the markup and not added via AJAX, if it's not present the value cannot be determined.

## _Element Name Attribute_ issue

If the input element is missing the `name` attribute, strange things may happen.

## Use of `status`

**When you use `status` in a form test, it will test the final status AFTER the form is submitted, not the status of the form page itself.**  For example if your form redirects to a 404 page after it is submitted, and you want to verify this, you should use `status: 404` like so:

```yaml
-
  url: /my-redirecting-form
  status: 403
  form:
    dom: form
```

## Reference

* https://www.w3.org/TR/html401/interact/forms.html
* https://html.spec.whatwg.org/multipage/form-elements.html
