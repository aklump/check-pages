<?php

namespace AKlump\CheckPages;

use MidwestE\ObjectPath;
use Psr\Http\Message\ResponseInterface;

/**
 * Implements the Data plugin.
 */
final class Data extends Plugin {

  use SerializationTrait;

  const SEARCH_TYPE = 'path';

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    if (!empty($stringified)) {
      $stringified = rtrim($stringified, '.');
      if (empty($assert->path)) {
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
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
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
    $assert->setHaystack($this->normalize($value));
    if ($assert->set) {
      [$type] = $assert->getAssertion();
      if (empty($type)) {
        $assert->setNeedle($value);
        $assert->setResult(TRUE);
      }
    }
  }

}
