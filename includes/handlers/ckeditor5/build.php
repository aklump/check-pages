<?php

use AKlump\TokenEngine\ReplaceTokens;
use AKlump\TokenEngine\Styles\CustomStyle;
use AKlump\TokenEngine\TokenCollection;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * Config
 */
$output = '/Users/aaronklump/Code/Projects/NationalUniversity/TheCommons/site/app/tests_check_pages/suites/contrib/maxlength.yml';

$suite = Yaml::parseFile(__DIR__ . '/suite.yml');
$test = $suite[0];
$assertions = $test['find'] ?? [];
$textarea = $test['ckeditor5'];
if (empty($textarea)) {
  throw new \RuntimeException('ckeditor5 not found in test');
}
if (empty($assertions)) {
  throw new \RuntimeException('No assertions found in test');
}

// TODO foreach $assertions

$assertion = $assertions[0];
$key = 0;

$generatedTest = $test;
unset($generatedTest['ckeditor5']);

$generatedTest['find'] = [];
foreach ($assertions as $key => $assertion) {
  $tokens = TokenCollection::createFromKeyValueArray([
    'TIMEOUT_SECONDS' => 10,
    'TEXTAREA' => $textarea,
    'DOM' => $assertion['dom'],
    'VALUE' => $assertion['ckeditor5'],
  ]);
  $replace = (new ReplaceTokens($tokens, new CustomStyle('JIG__')));
  $script = file_get_contents(__DIR__ . '/script.js');
  $script = $replace($script);
  file_put_contents(__DIR__ . '/script.built.js', $script);
  $script = (new MatthiasMullie\Minify\JS())->add($script)->minify();
  $script = implode(';', explode(PHP_EOL, $script));

  // This must contain at least ONE EOL so that DUMP_MULTI_LINE_LITERAL_BLOCK
  // behaves as expected.  If not javascript gets escaped and breaks
  $assertion = ['javascript' => $script . PHP_EOL] + $assertion;

  unset($assertion['dom']);
  unset($assertion['ckeditor5']);
  unset($assertion['__this']);
  unset($assertion['value']);
  $generatedTest['find'][] = $assertion;
}

$generatedSuite = [$generatedTest];
file_put_contents($output, Yaml::dump($generatedSuite, 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
