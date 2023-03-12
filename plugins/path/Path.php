<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\CheckPages\SerializationTrait;
use MidwestE\ObjectPath;
use AKlump\CheckPages\Event\AssertEventInterface;

/**
 * Implements the Json Pointer plugin.
 */
final class Path implements PluginInterface {

  use SerializationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $should_apply = array_key_exists('path', $event->getAssert()
            ->getConfig());
          if ($should_apply) {
            $path = new self();
            $path->setHaystack($event);
          }
        },
      ],
    ];
  }

  public function setHaystack(AssertEventInterface $event = NULL) {
    $assert = $event->getAssert();
    $assert->setSearch($this->getPluginId());
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
          $haystack = $this->normalize($haystack);
          $assert->setHaystack($haystack);
        }
      }
    }
  }

  public function getPluginId(): string {
    return 'path';
  }

}
