<?php
if (array_key_exists('redirect', $_GET)) {
  header("HTTP/1.1 302 Found");
  header('Location: ' . $_SERVER['PHP_SELF'] . '?redirected');
} ?>
<html>
<head>
  <script type="text/javascript">
    function ready(fn) {
      if (document.readyState != 'loading') {
        fn()
      } else {
        document.addEventListener('DOMContentLoaded', fn)
      }
    }

    ready(function() {
      document.getElementById('title').textContent = 'COVID 19 Pandemic'
      location.hash = 'foo=bar&alpha=bravo'
      setTimeout(function() {
        makeHttpRequest()
      }, 3000)
    })

    function makeHttpRequest() {
      var req = new XMLHttpRequest()
      req.onreadystatechange = processResponse
      req.open('GET', 'javascript__ajax_server.html')
      req.send()

      function processResponse() {
        if (req.readyState != 4) return // State 4 is DONE
        document.querySelector('.ajax-content').innerHTML = req.responseText
      }
    }
  </script>
</head>
<body>
<h1 id="title">Loading...</h1>
<div class="ajax-content"></div>
</body>
</html>
