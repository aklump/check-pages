# CURL Errors

> cURL error 6: Could not resolve host:

This may happen if you your system doesn't allow many open files. This is a known bug. The current work around is to include this in your script.

```shell
ulimit -n 10240
```
