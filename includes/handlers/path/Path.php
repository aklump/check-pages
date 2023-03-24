<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\CheckPages\Plugin\TestFailedException;
use AKlump\CheckPages\SerializationTrait;
use MidwestE\ObjectPath;

/**
 * Implements the Json Pointer handler.
 */
final class Path implements HandlerInterface {

  use SerializationTrait;

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
            $path = new self();
            $path->setHaystack($event);
          }
          catch (\Exception $e) {
            throw new TestFailedException($event->getTest()->getConfig(), $e);
          }
        },
      ],
    ];
  }

  /**
   * @throws \Rs\Json\Pointer\NonWalkableJsonException
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   * @throws \Rs\Json\Pointer\InvalidPointerException
   */
  protected function setHaystack(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $assert->setSearch($this->getId(), $assert->path);
    $response_body = $assert->getHaystack()[0] ?? '';

    if (!empty($response_body)) {
      $content_type = $this->getContentType($event->getDriver()
        ->getResponse());
      $data = $this->deserialize($response_body, $content_type);
      if (is_array($data)) {
        $assert->setHaystack($data);
      }
      if ($assert->path) {
        $object_path = new ObjectPath($data);
        if (!$object_path->exists($assert->path)) {
          $assert->setHaystack([]);
        }
        else {
          $haystack = $object_path->get($assert->path);
          $haystack = $this->valueToArray($haystack);
          $assert->setHaystack($haystack);
        }
      }
    }
  }

  public static function getId(): string {
    return 'path';
  }

  public static function doesApply($context): bool {
    if ($context instanceof \AKlump\CheckPages\Assert) {
      $config = $context->getConfig();
    }
    if (empty($config)) {
      return FALSE;
    }
    // This is a hack for now because json_schema also uses the 'path'; need
    // to find a better detection method.
    return array_key_exists('path', $config) && !array_key_exists('schema', $config);
  }

}
