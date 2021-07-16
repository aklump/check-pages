# File Storage

Authentication can slow down your tests. To mitigate this you can create a writeable folder at _{config.files}/storage_ and the session cookies will be written to disk the first time they are obtained from the test subject. After that, authentication will skip the login step and pull the session data from _files/storage_. Protect the contents of that directory because it contains credentials.

If you do not want this feature, then make sure that there is no directory called _storage_ in the the directory you've defined as your writeable files in the runner config.

