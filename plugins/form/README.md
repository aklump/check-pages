# Testing Forms

The form plugin allows you to test the submission of forms.

## Submit

The submit is a special `dom` selector in that it uses the form as the root, so you can only select elements inside the form AND you should omit any reference to the form element, as it will be prepended automatically.

You only need to use this if the form contains more than one `input[type="submit"]` element.

## Setting Form Values

To provide a form value, add an object to the `input` array as shown below. It must have the keys `name` and `value`, where `name is the `name` attribute of the form element.

