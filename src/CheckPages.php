<?php

namespace AKlump\CheckPages;

use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use AKlump\LoftLib\Bash\Output;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

class CheckPages {

  public $failedSuiteCount = 0;

  /**
   * @var bool
   */
  protected $outcome = TRUE;

  /**
   * @var array
   */
  protected $printed = [];

  /**
   * @var array
   */
  protected $debug = [];

  /**
   * @var \AKlump\LoftLib\Bash\Bash
   */
  protected $bash;

  /**
   * @var string
   */
  protected $rootDir;

  /**
   * @var string
   */
  protected $configPath;

  /**
   * @var array
   */
  protected $config = [];

  /**
   * App constructor.
   *
   * @param string $root_dir
   *   The system path to this test app root directory.  The schema files, for
   *   example are found in this directory.
   * @param \AKlump\LoftLib\Bash\Bash $bash
   *   An instance of \AKlump\LoftLib\Bash\Bash.
   */
  public function __construct(string $root_dir, Bash $bash) {
    $this->rootDir = $root_dir;
    $this->bash = $bash;
  }

  public function setConfig(string $path) {
    $this->configPath = $path;

    return $this;
  }

  public function getTest(): string {
    try {
      return $this->resolve((string) $this->bash->getArg(1));
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  protected function resolve(string $path) {
    $candidates = [
      $path,
      $this->rootDir . '/tests/' . $path,
      $this->rootDir . '/tests/' . $path . '.yml',
      $this->rootDir . '/' . $path,
      $this->rootDir . '/' . $path . '.yml',
    ];

    while (($try = array_shift($candidates))) {
      if (is_file($try)) {
        return $try;
      }
    }
    throw new \InvalidArgumentException("Cannot resolve \"$path\".");
  }

  /**
   * Resolve a relative URL to the configured base_url.
   *
   * @param string $relative_url
   *   THe relative URL, beginning with an '/'.
   *
   *
   * @return string
   *   The absolute URL.
   */
  protected function url(string $relative_url): string {
    if (substr($relative_url, 0, 1) !== '/') {
      throw new \InvalidArgumentException("Relative URLS must begin with a forward slash.");
    }

    return rtrim($this->config['base_url'], '/') . '/' . $relative_url;
  }

  /**
   * Load YAML from a file first validating against the schema.
   *
   * @param string $path
   * @param string $schema_basename
   *
   * @return array
   */
  protected function validateAndLoadYaml(string $path, string $schema_basename): array {
    $data = Yaml::parseFile($this->resolve($path), Yaml::PARSE_OBJECT_FOR_MAP);
    $validator = new Validator();
    $validator->validate($data, (object) ['$ref' => 'file://' . $this->rootDir . '/' . $schema_basename], Constraint::CHECK_MODE_EXCEPTIONS);

    // Convert to arrays, we only needed objects for the validation.
    return json_decode(json_encode($data), TRUE);
  }

  /**
   * Visit an URLs definition found in $path.
   *
   * @param string $path
   *   A resolvable path to a yaml file.
   *
   * @return array
   */
  public function runSuiteFromFile(string $path): array {
    $this->config = $this->validateAndLoadYaml($this->configPath, 'schema.config.json');
    if (empty($this->printed['base_url'])) {
      echo Color::wrap('blue', sprintf('Base URL is %s', $this->config['base_url'])) . PHP_EOL;
      $this->printed['base_url'] = TRUE;
    }
    echo Color::wrap('blue', sprintf('Running "%s" suite...', $path)) . PHP_EOL;

    $results = [];
    $data = $this->validateAndLoadYaml($path, 'schema.visit.json');

    $longest_url = array_reduce($data, function ($carry, $item) {
      return max($carry, strlen($item['url'] ?? $item));
    });

    $this->debug = [];
    foreach ($data as $config) {
      $config += [
        'expect' => 200,
        'find' => '',
      ];
      $result = $this->visitUrl($config);

      $status = $result['status'];
      if ($this->bash->hasParam('debug')) {
        $status = sprintf("Expected %d, got %d", $config['expect'], $result['status']);
      }

      $row = [
        'url' => str_pad($config['url'], $longest_url),
        'status' => $status,
        'result' => $result['pass'] ? 'pass' : 'FAIL',
      ];
      $results[] = $config + ['result' => $result];
      $row = ['color' => $result['pass'] ? 'green' : 'red', 'data' => $row];
      echo Output::columns([$row], array_fill_keys(array_keys($row), 'left'));

      if ($this->bash->hasParam('debug') && $this->debug) {
        $debug = array_map(function ($item) {
          return $item['data'];
        }, $this->debug);
        echo Color::wrap('red', PHP_EOL . implode(PHP_EOL . PHP_EOL, $debug) . PHP_EOL);
      }

      if (!$result['pass']) {
        $this->failedSuiteCount++;
        throw new SuiteFailedException($path, $results);
      }
    }

    return $results;
  }

  protected function visitUrl(array $config): array {
    $client = new Client();
    try {
      $res = $client->request('GET', $this->url($config['url']));

    }
    catch (ClientException $exception) {
      $res = $exception->getResponse();
    }

    $pass = $res->getStatusCode() == $config['expect'];

    // Look for a piece of text on the page.
    if ($pass && $config['find']) {
      $body = strval($res->getBody());
      foreach ($config['find'] as $needle) {
        $pass = $this->handleSingleFind($needle, $body);
        if (!$pass) {
          break;
        }
      }
    }

    if ($this->bash->hasParam('show-source')) {
      $this->debug((string) $res->getBody());
    }

    return [
      'pass' => $pass,
      'status' => $res->getStatusCode(),
    ];
  }

  /**
   * Apply a single find action in the text.
   *
   * @param array|string $needle
   *   When a string a case-sensitive search will be made in $haystack.  As an
   *   array it should contain an "expect" key and a key "dom", which is a CSS
   *   selector.
   * @param string $haystack
   *   The large string to search within.
   *
   * @return bool
   *   True if the find was successful.
   */
  protected function handleSingleFind($needle, string $haystack): bool {
    $pass = FALSE;
    if (!is_array($needle)) {
      $pass = strpos($haystack, $needle) !== FALSE;
      if (!$pass) {
        $this->debug(sprintf('Could not find the "%s" in the response body.', $needle));
      }
    }
    elseif (isset($needle['match'])) {
      $pass = preg_match($needle['match'], $haystack);
      if (!$pass) {
        $this->debug(sprintf('Could not match against RegEx "%s".', $needle['match']));
      }
    }
    elseif (isset($needle['dom'])) {
      $crawler = new Crawler($haystack);
      $crawler = $crawler->filter($needle['dom']);

      if (isset($needle['count'])) {
        $actual = $crawler->count();

        if (is_numeric($needle['count'])) {
          $pass = $actual === $needle['count'];
        }
        else {
          preg_match('/([><=]+)\s*(\d+)/', $needle['count'], $matches);
          switch ($matches[1]) {
            case '>':
              $pass = $actual > $needle['count'];
              break;

            case '>=':
              $pass = $actual >= $needle['count'];
              break;

            case '<':
              $pass = $actual < $needle['count'];
              break;

            case '<=':
              $pass = $actual <= $needle['count'];
              break;
          }

        }

        if (!$pass) {
          $this->debug(sprintf('Expecting %s to have a count of %d.  The actual count is %d.', $needle['dom'], $needle['count'], $actual));
        }
      }
      elseif (is_string($needle['expect'])) {
        $actual = $crawler->first()->text();
        $pass = $actual === $needle['expect'];
        if (!$pass) {
          $this->debug(sprintf('Expecting %s to have a text value of "%s".  The actual value is "%s".', $needle['dom'], $needle['expect'], $actual));
        }
      }
    }

    return $pass;
  }

  protected function debug($message) {
    $this->debug[] = ['data' => $message];
  }

}
