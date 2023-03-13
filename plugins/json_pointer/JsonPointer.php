<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\SerializationTrait;
use Rs\Json\Pointer;
use AKlump\CheckPages\Exceptions\TestFailedException;

/**
 * Implements the Json Pointer plugin.
 */
final class JsonPointer implements PluginInterface {

  use SerializationTrait;

  public static function doesApply($context): bool {
    if ($context instanceof Assert) {
      return array_key_exists('pointer', $context->getConfig());
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
            $json_pointer = new self();
            $json_pointer->setHaystack($event);
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
    $assert->setSearch($this->getPluginId(), $assert->pointer);
    $json = $assert->getHaystack()[0] ?? NULL;
    try {
      $assert->setHaystack([]);
      $pointer = new Pointer($json);
      $haystack = $pointer->get($assert->pointer);
      $haystack = $this->valueToArray($haystack);
      $assert->setHaystack($haystack);
    }
    catch (Pointer\InvalidJsonException $e) {
      throw new TestFailedException($event->getTest()
        ->getConfig(), 'The response was not valid JSON.');
    }
    catch (Pointer\NonexistentValueReferencedException $e) {
      $assert->setHaystack([]);
    }
  }

  public static function getPluginId(): string {
    return 'json_pointer';
  }

}
