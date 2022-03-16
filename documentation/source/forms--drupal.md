# Testing Forms

## Drupal 8

The following is how you can test the contact form:

```yaml
-
  why: Gather form info for next test.
  visit: /contact
  find:
    -
      why: Capture form build ID to use in next POST test.
      dom: '.contact-form [name="form_build_id"]'
      attribute: value
      set: formBuildId
    -
      why: Capture form ID to use in next POST test.
      dom: '.contact-form [name="form_id"]'
      attribute: value
      set: formId
    -
      why: Capture submit button value to use in next POST test.
      dom: '.contact-form [name="op"]'
      attribute: value
      set: op
    -
      why: Capture honeypot_time to use in next POST test.
      dom: '.contact-form [name="honeypot_time"]'
      attribute: value
      set: honeypotTime

-
  why: Prevent honeypot module from invalidating submission.
  sleep: 3

-
  why: Assert contact form stays on /contact after successful submission.
  visit: /contact
  request:
    method: POST
    headers:
      Content-Type: application/x-www-form-urlencoded
    body: form_id=${formId}&form_build_id=${formBuildId}&op=${op}&honeypot_time=${honeypotTime}&name=Alpha&mail=alpha@foo.com&message[0][value]=lorem%20ipsum
  find:
    -
      dom: 'meta[property="og:url"]'
      attribute: content
      matches: /\/contact$/
    -
      why: Assert no form messages were triggered.
      dom: .message[data-level="error"]
      count: 0

```

## Proposed Shorthand

Not yet developed.

```yaml
-
  url: /contact
  form:
    -
      name: name
      value: Alpha
    -
      name: mail
      value: alpha@foo.com
    -
      name: message[0][value]
      value: lorem ipsum
```
