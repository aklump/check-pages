<?php
if (isset($_GET['apiKey']) && $_GET['apiKey'] !== 'OWqQtt741kDd6x8c') {
  header("HTTP/1.1 403 Forbidden");
  exit(1);
}
?>
<html>
<head>
  <style type="text/css">
      #logo svg {
          width: 200px;
      }
  </style>
</head>
<body>

<a id="logo" title="An SVG image">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
    <path d="M13 7H7v6h6V7z"/>
    <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd"/>
  </svg>
</a>

<h1 class="page-title" data-timestamp="<?= time() ?>">
  <span>About In the Loft Studios</span>
</h1>
<h2>Creating the web since <span class="since">1999</span></h2>


<h2 class="block-title">Quick Start</h2>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>

<h2 class="block-title">Upcoming Events</h2>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquam aspernatur deserunt expedita explicabo labore quam quod ullam, vero. Molestiae, nulla pariatur? Ab accusamus blanditiis enim nostrum perferendis qui quisquam vitae?</p>

<h2 class="block-title">Latest Blog Post</h2>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<div id="footer">
  <p id="copyright">Copyright &copy; 2000-<?php echo date('Y') ?> In the Loft Studios, PO Box 29294, Bellingham, WA</p>
</div>
</body>
</html>
