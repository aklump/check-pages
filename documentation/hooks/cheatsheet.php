<?php

use AKlump\CheckPages\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';


function selectors_to_markdown() {
  $assert = new Assert('');
  $info = $assert->getSelectorsInfo();
  $markdown = [];
  foreach ($info as $item) {
    $markdown[] = '* `' . $item->code() . '`: ' . $item->description();
    foreach ($item->examples() as $example) {
      $markdown[] = <<<EOD

  ```yaml
  find:
    - 
      {$item->code()}: {$example}
  ```
    
EOD;
    }
  }

  return implode(PHP_EOL, $markdown) . PHP_EOL;
}

function asserts_to_markdown() {
  $assert = new Assert('');
  $selector = array_first($assert->getSelectorsInfo());
  $selector_example = array_first($selector->examples());
  $info = $assert->getAssertionsInfo();
  $markdown = [];
  foreach ($info as $item) {
    $markdown[] = '* `' . $item->code() . '`: ' . $item->description();
    foreach ($item->examples() as $example) {
      $markdown[] = <<<EOD

  ```yaml
  find:
    - 
      {$selector->code()}: {$selector_example}
      {$item->code()}: {$example}
  ```
    
EOD;
    }
  }

  return implode(PHP_EOL, $markdown) . PHP_EOL;
}

$contents = asserts_to_markdown();
echo $compiler->addInclude('_assertions.md', $contents)
    ->getBasename() . ' has been created.' || exit(1);

$contents = selectors_to_markdown();
echo $compiler->addInclude('_selectors.md', $contents)
    ->getBasename() . ' has been created.' || exit(1);


$assert = new Assert('');
$selectors = implode('|', array_map(function ($item) {
  return $item->code();
}, $assert->getSelectorsInfo()));
$assertions = implode('|', array_map(function ($item) {
  return $item->code();
}, $assert->getAssertionsInfo()));
$contents = <<<EOD
## Test Cheatsheet

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
| _Load page_       | `visit|url`      |                              | - |
| _Javascript_  | `js`      |                              | `false` |
| _Status Code_     | `expect`   | | 200 |
| _Redirect_  | `location` |                               | - |
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `{$selectors}`                       | - |
| _Assertions_  |            | `{$assertions}` | `contains` |

EOD;
echo $compiler->addInclude('_cheatsheet.md', $contents)
    ->getBasename() . ' has been created.' || exit(1);

exit(0);
