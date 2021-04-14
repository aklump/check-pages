<?php

use AKlump\CheckPages\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';


function selectors_to_markdown() {
  $assert = new Assert('');
  $info = $assert->getSelectorsInfo();
  $markdown = [];
  foreach ($info as $item) {
    if ($item->code() === 'javascript') {
      continue;
    }

    $markdown[] = '* `' . $item->code() . '`: ' . $item->description();

    $dom = '';
    if ($item->code() === 'attribute') {
      $dom = "dom: .foo-bar\n      ";
    }

    foreach ($item->examples() as $example) {
      $markdown[] = <<<EOD

  ```yaml
  find:
    - 
      {$dom}{$item->code()}: {$example}
  ```
    
EOD;
    }
  }

  return implode(PHP_EOL, $markdown) . PHP_EOL;
}

function supports_attribute(\AKlump\CheckPages\HelpInfoInterface $item) {
  $assert = new Assert('');
  $info = $assert->getIntersectionsByModifier('attribute');

  return in_array($item->code(), $info['assert']);
}

function asserts_to_markdown() {
  $assert = new Assert('');
  $selector = array_first($assert->getSelectorsInfo());
  $selector_example = array_first($selector->examples());
  $info = $assert->getAssertionsInfo();
  $markdown = [];
  foreach ($info as $item) {

    // This one only works per page.
    if ($item->code() === 'none') {
      continue;
    }

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

      if (supports_attribute($item)) {
        $markdown[] = <<<EOD

  ```yaml
  find:
    - 
      {$selector->code()}: {$selector_example}
      attribute: id
      {$item->code()}: {$example}
  ```
    
EOD;
      }
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
  return $item->code() . (supports_attribute($item) ? '*' : '');
}, $assert->getAssertionsInfo()));

$contents = [];
$contents[] = <<<EOD
## Test Cheatsheet

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
| _Load page_       | `visit|url`      |                              | - |
| _Javascript_  | `js`      |                              | auto |
| _Status Code_     | `expect`   | | 200 |
| _Redirect_  | `location` |                               | - |
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `dom|xpath|javascript`                       | - |
| _Assertions_  |            | `none|contains|exact|match|count|text` | - |
EOD;

// Demonstrate the usage of each of the modifiers.
foreach ($assert->getModifiersInfo() as $modifier) {
  $info = $assert->getIntersectionsByModifier($modifier->code());
  $info['search'] = implode('|', $info['search']);
  $info['assert'] = implode('|', $info['assert']);
  $contents[] = <<<EOD
### Using the `{$modifier->code()}` Modifier

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
|...|...|...|...|
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `{$info['search']}`                       | - |
| _Modifiers_   |            | `{$modifier->code()}`                       | - |
| _Assertions_  |            | `{$info['assert']}` | - |
EOD;
}

echo $compiler->addInclude('_cheatsheet.md', implode(PHP_EOL, $contents))
    ->getBasename() . ' has been created.' || exit(1);

exit(0);
