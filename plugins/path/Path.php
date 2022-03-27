<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\SerializationTrait;
use MidwestE\ObjectPath;

/**
 * Implements the Path plugin.
 */
final class Path extends LegacyPlugin {

  use SerializationTrait;

  const SEARCH_TYPE = 'path';

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    if (!empty($stringified)) {
      $stringified = rtrim($stringified, '.');
      if (!$assert->path) {
        $stringified = sprintf('%s at the root node.', $stringified, $assert->path);
      }
      else {
        $stringified = sprintf('%s in the node at "%s".', $stringified, $assert->path);
      }
    }

    return $stringified;
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(AssertEventInterface $event) {
    try {
      $assert = $event->getAssert();
      $response = $event->getDriver()->getResponse();
      $assert->setSearch(self::SEARCH_TYPE);
      $value = $this->deserialize(
        $response->getBody(),
        $this->getContentType($response)
      );

      // "" means the root node.
      $path = $assert->path ?? "";
      if ($path) {
        $o = new ObjectPath($value);
        $value = $o->get($path);
      }
      if ($path && isset($o) && !$o->exists($path)) {
        $assert->setHaystack([]);
      }
      else {
        $assert->setHaystack($this->normalize($value));
      }
      if ($assert->set) {
        [$type] = $assert->getAssertion();
        if (empty($type)) {
          $assert->setNeedle($value);
          $assert->setResult(TRUE);
        }
      }
    }
    catch (\Exception $exception) {
      // TODO How to print a message? Throwing doesn't print anything to the user so that is not good.
      $assert->setHaystack([]);
      $assert->setResult(FALSE);
    }
  }

}
