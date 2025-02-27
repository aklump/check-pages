<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\SerializationTrait;
use BinaryCube\DotArray\DotArray;

/**
 * Implements the Json Pointer handler.
 */
final class Path implements HandlerInterface {

  use SerializationTrait;

  public static function getId(): string {
    return 'path';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          // Take a look at json_schema, which uses "path" as well.  It will
          // have handled this event already so we have to make sure we're not
          // trying to process that here.
          if (!$assert->has('path') || $assert->has('schema')) {
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
    $assert->setSearch($this->getId(), $assert->get('path'));
    $response_body = $assert->getHaystack()[0] ?? '';
    if (!empty($response_body)) {
      $content_type = $this->getContentType($event->getDriver()
        ->getResponse());
      $data = $this->deserialize($response_body, $content_type);
      $path = $assert->get('path') ?? '';
      $data = $this->valueToArray($data);
      $dot = DotArray::create($data);
      $value = $dot->get($path);
      $exists = $dot->has($path);
      if (!$exists) {
        $value = [];
      }
      $haystack = $this->valueToArray($value);
      $assert->setHaystack($haystack);
    }
  }

}
