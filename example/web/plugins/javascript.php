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
    })
  </script>
</head>
<body>
<h1 id="title">Loading...</h1>
</body>
</html>
