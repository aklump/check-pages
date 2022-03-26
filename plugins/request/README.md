# Making Requests Other Than GET

## Multiple Verbs

Use `methods` to request against the same endpoint using the same configuration, varying on method. This can be handly for testing REST APIs against 403 responses.

Notice how you can use `${request.method}` to interpolate.
