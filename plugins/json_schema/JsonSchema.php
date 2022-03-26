<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\SerializationTrait;
use JsonSchema\Validator;
use MidwestE\ObjectPath;

/**
 * Implements the Json Schema plugin.
 */
final class JsonSchema extends LegacyPlugin {

  use SerializationTrait;

  const SEARCH_TYPE = 'schema';

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $jsonSchemas;

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
  public function onBeforeDriver(TestEventInterface $event) {
    $config = $event->getTest()->getConfig();
    if (!is_array($config) || empty($config['find'])) {
      return;
    }
    foreach ($config['find'] as $assert_id => $assertion) {

      // Resolve paths and load all our JSON schemas.
      if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
        try {
          $path_to_schema = $this->runner->resolveFile($assertion['schema']);
        }
        catch (UnresolvablePathException $exception) {
          $message = sprintf('Cannot resolve to schema file: "%s".', $assertion['schema']);
          throw new UnresolvablePathException($assertion['schema'], $message);
        }
        $this->jsonSchemas[$assert_id] = file_get_contents($path_to_schema);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $response = $event->getDriver()->getResponse();

    $content_type = $this->getContentType($response);
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
    $assert->setSearch(self::SEARCH_TYPE);

    $assert->setAssertion(Assert::ASSERT_CALLABLE, function ($assert) use ($response) {
      $assert_id = $assert->getId();
      $expected = $assert->matches ?? TRUE;
      if (empty($this->jsonSchemas[$assert_id])) {
        throw new \RuntimeException(sprintf('Missing schema for assert %d', $assert_id));
      }
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
