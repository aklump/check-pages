# Testing Forms

The form plugin allows you to test the submission of forms.

## Submit

The submit is a special `dom` selector in that it uses the form as the root, so you can only select elements inside the form AND you should omit any reference to the form element, as it will be prepended automatically.

## Setting Form Values

To pass a custom form value, add an object to the `input` array as shown below. It must have the keys `name` and `value`, where `name is the `name` attribute of the form element.

## Pass Through Form Values

At least for Drupal forms, there are several hidden fields that must be submitted with the form for it to be valid. You indicate these pass through variables by listing the HTML input names as string values in the `input` array, as shown in the Drupal example.
