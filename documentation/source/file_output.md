# File Output

If you create the files directory, certain files will be written there when you run your suites.

* Each suite will create a directory by it's filename.
* In that directory the following files will be created:
    * _urls.txt_ a list of urls that failed testing.
    * _failures.txt_ verbose output of the failures only.
  
Try using `tail -f files/SUITE/urls.txt` during development.
