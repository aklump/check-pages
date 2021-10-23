<?php

namespace AKlump\CheckPages;

use JsonSchema\Validator;
use MidwestE\ObjectPath;
use Psr\Http\Message\ResponseInterface;
use AKlump\CheckPages\Parts\Runner;

/**
 * Implements the Json Schema plugin.
 */
final class JsonSchema implements TestPluginInterface {

  const SEARCH_TYPE = 'schema';

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $jsonSchemas;

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * Plugin constructor.
   *
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *   The test runner instance.
   */
  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {

  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    $matches = $assert->matches ?? TRUE;
    $path = 'Response body';
    if ($assert->path ?? NULL) {
      $path = "\"$assert->path\"";
    }

    return sprintf('%s %s JSON schema: %s', $path, $matches ? 'matches' : 'does not match', $assert->schema);
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    if (!is_array($config) || empty($config['find'])) {
      return;
    }
    foreach ($config['find'] as $assertion) {

      // Resolve paths and load all our JSON schemas.
      if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
        $path_to_schema = $this->runner->resolve($assertion['schema']);
        $this->jsonSchemas[] = file_get_contents($path_to_schema);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
  }

  /**
   * @param string $serial
   * @param string $content_type
   *
   * @return mixed|void
   *
   * @throws \InvalidArgumentException
   *   If the content type is unsupported.
   *
   * @todo Support other content types.
   */
  private function deserialize(string $serial, string $content_type) {
    switch ($content_type) {
      case 'application/json':
        return json_decode($serial);
    }
    throw new \InvalidArgumentException('Cannot deserialize content of type "%s".', $content_type);
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $content_type = $response->getHeader('content-type')[0] ?? 'application/json';
    $haystack = array_map(function ($item) use ($assert, $content_type) {
      $item = $this->deserialize($item, $content_type);
      $path = $assert->path ?? NULL;
      if ($path) {
        $o = new ObjectPath($item);
        $item = $o->get($path);
      }

      return $item;
    }, $assert->getHaystack());
    $assert->setHaystack($haystack);

    $assert->setAssertion(Assert::ASSERT_CALLABLE, function ($assert) use ($response) {
      $assert_id = $assert->getId();
      $expected = $assert->matches ?? TRUE;
      $schema = json_decode($this->jsonSchemas[$assert_id]);
      foreach ($assert->getHaystack() as $data) {
        $validator = new Validator();
        $validator->validate($data, $schema);
        if ($validator->isValid() !== $expected) {
          $message = ['Invalid configuration:'];
          foreach ($validator->getErrors() as $error) {
            $message[] = sprintf("[%s] %s", $error['property'], $error['message']);
          }
          throw new \RuntimeException(implode(PHP_EOL, $message));
        }
      }

      return TRUE;
    });
  }

}
