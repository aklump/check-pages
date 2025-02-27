<?php

namespace AKlump\Knowledge\User;

class Handlers {

  public function __invoke() {

  }

  function get_path_to_examples($handler): string {
    global $path_to_handlers;
    $path = "$path_to_handlers/$handler/examples.yml";
    if (file_exists($path)) {
      return $path;
    }
    $path = "$path_to_handlers/$handler/suite.yml";
    if (file_exists($path)) {
      return $path;
    }

    return '';
  }

  function get_frontmatter($handler): string {
    $real_title = get_real_title($handler);
    $title = Strings::title($handler);
    if (strcasecmp($real_title, $title) !== 0) {
      $title .= " -- $real_title";
    }

    return "<!--\ntitle: '$title'\nid: handler_{$handler}\n-->";
  }

  function get_real_title($handler): string {
    global $path_to_handlers;
    $path_to_readme = "$path_to_handlers/$handler/README.md";
    $title = Strings::title($handler);;
    if (!file_exists($path_to_readme)) {
      return $title;
    }
    $contents = file_get_contents($path_to_readme);
    preg_match('/#\s*(.+?)\n/s', $contents, $matches);

    return $matches[1] ?? $title;
  }


}
