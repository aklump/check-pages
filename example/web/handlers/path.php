<?php

/**
 * @file
 * Controller for the path plugin.
 */

switch ($_GET['op'] ?? '') {
  case 'rest':
    $resource_id = 144;
    switch (strtolower($_SERVER['REQUEST_METHOD'])) {
      case 'get':
        header('content-type: text/yml');
        print "- id: $resource_id\n  title: Lorem Ipsum";
        exit(0);

      case 'delete':
        if ($resource_id == $_GET['id']) {
          header('HTTP/1.1 204 No content', TRUE, 204);
          exit(0);
        }
        break;
    }
    header('HTTP/1.1 404 Not found', TRUE, 404);
    exit(1);

  case 'items':
    header('content-type: application/json');
    print '{"items":["apple","banana","carrot","daikon","eggplant"], "id":null,"group":""}';
    exit(0);

  case 'root':
    header('content-type: application/json');
    print '["apple","banana","carrot","daikon","eggplant"]';
    exit(0);

  default:
    $headers = array_change_key_case(getallheaders());
    header('content-type: ' . ($headers['accept'] ?? ''));

    switch ($headers['accept'] ?? '') {
      case 'application/json':
        print '{"foo":{"bar":"baz"}}';
        break;

      case 'application/xml':
        print '<?xml version="1.0" encoding="UTF-8" ?><foo><bar>baz</bar></foo>';
        break;

      case 'application/x+yaml':
      case 'text/yaml':
        print "foo:\n  bar: baz\n";
        break;

      default:
        header('Status: 404 Not Found');
        break;
    }

    exit(0);
}

