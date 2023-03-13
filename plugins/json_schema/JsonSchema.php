<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\SerializationTrait;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use MidwestE\ObjectPath;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Json Schema plugin.
 */
final class JsonSchema implements PluginInterface {

  use SerializationTrait;

  /**
   * Allows instance caching of decoded schemas.
   *
   * @var array
   */
  private $jsonSchemas;

  public static function doesApply($context): bool {
    if ($context instanceof Assert) {
      return array_key_exists('schema', $context->getConfig());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          if (!self::doesApply($event->getAssert())) {
            return;
          }
          try {
            $json_schema = new self();
            $json_schema->prepareAssertion($event);
          }
          catch (\Exception $e) {
            throw new TestFailedException($event->getTest()->getConfig(), $e);
          }
        },
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareAssertion(AssertEventInterface $event) {
    $this->runner = $event->getTest()->getRunner();
    $assert = $event->getAssert();
    $assert->setToStringOverride([$this, 'onAssertToString']);
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
    $assert->setSearch(self::getPluginId(), $assert->schema);

    $assert->setAssertion(Assert::ASSERT_CALLABLE, function ($assert) use ($response) {

      $expected = $assert->matches ?? TRUE;
      list($uri, $schema) = $this->decodeSchemaValue($assert->schema);

      // This is required to allow for relative path $ref.
      // @link https://github.com/justinrainbow/json-schema#with-inline-references
      $storage = new SchemaStorage();
      $storage->addSchema($uri, $schema);
      $validator = new Validator(new Factory($storage));

      foreach ($assert->getHaystack() as $data) {
        $validator->validate($data, $schema);
        if ($validator->isValid() !== $expected) {
          $message = ['Invalid configuration:'];
          foreach ($validator->getErrors() as $error) {
            $message[] = sprintf("[%s] %s", $error['property'], $error['message']);
          }
          throw new TestFailedException($assert->getConfig(), new \Exception(implode(PHP_EOL, $message)));
        }
      }

      return TRUE;
    });
  }


  private function validatePerSchema2($expected, $schema, $data) {
    $validator = new \Opis\JsonSchema\Validator();
    $result = $validator->validate($data, $schema);
    if ($result->isValid() !== $expected) {
      $message = ['Invalid configuration:'];
      $message .= $result->error();
      throw new \RuntimeException(implode(PHP_EOL, $message));
    }
  }

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
        $uri = SchemaStorage::INTERNAL_PROVIDED_SCHEMA_URI;
        $json = $schema_config_value;
      }
      else {
        try {
          $path_to_schema = $this->runner->resolveFile($schema_config_value);
          $uri = 'file://' . $path_to_schema;
        }
        catch (UnresolvablePathException $exception) {
          $message = sprintf('Schema "%s" cannot be resolved to an absolute path.', $schema_config_value);
          throw new UnresolvablePathException($schema_config_value, $message);
        }
        $json = file_get_contents($path_to_schema);
      }

      try {
        $schema = json_decode($json);
        if (!is_object($schema)) {
          throw new \RuntimeException();
        }
      }
      catch (\Exception $exception) {
        throw new \RuntimeException(sprintf('Cannot decode JSON schema from "%s".', $schema_config_value));
      }
      $this->jsonSchemas[$cid] = [$uri, $schema];
    }

    return $this->jsonSchemas[$cid];
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

  public static function getPluginId(): string {
    return 'json_schema';
  }

}
