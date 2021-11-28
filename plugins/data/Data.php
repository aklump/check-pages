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

  private $message;

  private $messageIsComplete;

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    if ($this->messageIsComplete) {
      return $this->message;
    }

    return sprintf('%s in the node at "%s". %s', rtrim($stringified, ' .'), $assert->path, $this->message);
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $assert->setSearch(self::SEARCH_TYPE);
    $item = $this->deserialize(
      $response->getBody(),
      $this->getContentType($response)
    );
    $path = $assert->path ?? NULL;
    if (is_null($path)) {
      throw new \UnexpectedValueException('$assert does not contain a "path" value.');
    }
    $o = new ObjectPath($item);
    $value = $o->get($path);
    $assert->setHaystack($this->normalize($value));

    if ($assert->set) {
      $this->message = sprintf('Value set as "%s".', $assert->set);
      $this->messageIsComplete = FALSE;

      $this->runner->getSuite()->variables()->setItem($assert->set, $value);
      [$type] = $assert->getAssertion();

      $is_only_setter = empty($type);
      if ($is_only_setter) {
        $assert->setResult(TRUE);
        $suffix = '';
        if (is_scalar($value)) {
          $suffix = sprintf(' with value %s', $value);
        }
        $this->message = sprintf('"%s" set as "%s"%s.', $path, $assert->set, $suffix);
        $this->messageIsComplete = TRUE;
      }
    }
  }

}
