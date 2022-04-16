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
   * Allows instance caching of decoded schemas.
   *
   * @var array
   */
  private $jsonSchemas;


  /**
   * (Load) and decode a schema.
   *
   * @param $schema_config_value
   *   A JSON schema string or a resolvable path to a JSON schema file.
   *
   * @return object
   */
  private function decodeSchemaValue(string $schema_config_value) {
    $cid = crc32($schema_config_value);
    if (!isset($this->jsonSchemas[$cid])) {
      $is_json = substr(ltrim($schema_config_value), 0, 1) === '{';
      if ($is_json) {
        $json = $schema_config_value;
      }
      else {
        try {
          $path_to_schema = $this->runner->resolveFile($schema_config_value);
        }
        catch (UnresolvablePathException $exception) {
          $message = sprintf('Cannot resolve to schema file: "%s".', $schema_config_value);
          throw new UnresolvablePathException($schema_config_value, $message);
        }
        $json = file_get_contents($path_to_schema);
      }

      try {
        $schema = json_decode($json, NULL, 512, JSON_THROW_ON_ERROR);
        if (!is_object($schema)) {
          throw new \RuntimeException();
        }
      }
      catch (\Exception $exception) {
        throw new \RuntimeException(sprintf('Cannot decode JSON schema from "%s".', $schema_config_value));
      }
      $this->jsonSchemas[$cid] = $schema;
    }

    return $this->jsonSchemas[$cid];
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
      $schema = $this->decodeSchemaValue($assert->schema);
      $expected = $assert->matches ?? TRUE;
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

}
