<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Parts\SetTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event\AssertEventInterface;
use Rs\Json\Pointer;
use AKlump\CheckPages\Exceptions\TestFailedException;

/**
 * Implements the Json Pointer plugin.
 */
final class JsonPointer implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          $should_apply = $assert->pointer;
          if (!$should_apply) {
            return;
          }
          $json = $assert->getHaystack()[0] ?? NULL;
          try {
            $assert->setSearch($this->getPluginId())->setHaystack([]);
            $pointer = new Pointer($json);
            $haystack = $pointer->get($assert->pointer);
            if (is_scalar($haystack)) {
              $haystack = [$haystack];
            }
            $assert->setHaystack($haystack);
          }
          catch (Pointer\InvalidJsonException $e) {
            throw new TestFailedException($event->getTest()
              ->getConfig(), 'The response was not valid JSON.');
          }
          catch (Pointer\NonexistentValueReferencedException $e) {
            $assert->setHaystack([]);
          }
        },
      ],
    ];
  }

  public function getPluginId(): string {
    return 'json_pointer';
  }

}
