<?php
$json = [
  'title' => "Introducing The Great Grebe Bird",
  'body' => '<div><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p><img class="hero-image" src="/ajax/images/great_grebe_bird.jpg" alt="Great Grebe Bird from Araucania, Chile"></p></div>',
];
$response = json_encode($json);

header('Content-Type: application/json');
header('Content-Length: ' . strlen($response));
echo $response;
