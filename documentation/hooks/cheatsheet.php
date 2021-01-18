<?php

use AKlump\CheckPages\Assert;

require_once __DIR__ . '/../../vendor/autoload.php';


function selectors_to_markdown() {
  $assert = new Assert('');
  $info = $assert->getSelectorsInfo();
  $markdown = [];
  foreach ($info as $item) {
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
  return strpos($item->description(), '`attribute`') !== FALSE;
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
$modifiers = implode('|', array_map(function ($item) {
  return $item->code();
}, $assert->getModifiersInfo()));
$assertions = implode('|', array_map(function ($item) {
  return $item->code() . (supports_attribute($item) ? '*' : '');
}, $assert->getAssertionsInfo()));

$contents = [];
$contents[] = <<<EOD
## Test Cheatsheet

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
| _Load page_       | `visit|url`      |                              | - |
| _Javascript_  | `js`      |                              | `false` |
| _Status Code_     | `expect`   | | 200 |
| _Redirect_  | `location` |                               | - |
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `dom|xpath`                       | - |
| _Assertions_  |            | `contains|exact|match|count|text` | `contains` |
EOD;

$info = $assert->getIntersectionsByModifier(Assert::MODIFIER_ATTRIBUTE);
$info_search = implode('|', $info['search']);
$info_assert = implode('|', $info['assert']);
$contents[] = <<<EOD
### Using the `attribute` modifier

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `{$info_search}`                       | - |
| _Modifiers_   |            | `attribute`                       | - |
| _Assertions_  |            | `{$info_assert}` | `contains` |
EOD;

$contents[] = <<<EOD
### Using the `style` selector

| operation | property  | `find` property  | default |
|----------|----------|----------|--|
| _Content_    | `find`      |                                   | - |
| _Selectors_   |            | `style`                       | - |
| _Modifiers_   |            | `property`                       | - |
| _Assertions_  |            | `contains|exact|match` | `contains` |
EOD;



echo $compiler->addInclude('_cheatsheet.md', implode(PHP_EOL, $contents))
    ->getBasename() . ' has been created.' || exit(1);

exit(0);
