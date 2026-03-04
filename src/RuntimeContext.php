<?php

namespace AKlump\CheckPages;

/**
 * A singleton class to store and retrieve runtime context.
 */
class RuntimeContext implements \Stringable {

  private static ?RuntimeContext $instance = NULL;

  private array $contexts = [];

  /**
   * Private constructor for singleton.
   */
  private function __construct() {
  }

  /**
   * Get the singleton instance.
   */
  public static function get(): RuntimeContext {
    if (self::$instance === NULL) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Add a context item.
   *
   * @param mixed $context
   *   Anything that can be stringified.
   *
   * @return $this
   */
  public function add($context, string $key = NULL): self {
    if (empty($key)) {
      if (is_object($context)) {
        $key = get_class($context);
      }
      else {
        $key = gettype($context);
      }
    }
    try {
      if (is_array($context)) {
        $this->contexts[$key][] = $context;
      }
      elseif (method_exists($context, 'id')) {
        $this->contexts[$key][] = $context->id();
      }
      elseif (method_exists($context, '__toString')) {
        $this->contexts[$key][] = (string) $context;
      }
      elseif (is_scalar($context)) {
        $this->contexts[$key][] = (string) $context;
      }
      else {
        throw new \Exception('Unserializable context item.');
      }
    }
    catch (\Exception $exception) {
      $this->contexts[$key][] = '';
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return json_encode($this->contexts, JSON_UNESCAPED_SLASHES);
  }

  /**
   * Clear all stored contexts (useful for testing).
   */
  public function clear(): void {
    $this->contexts = [];
  }

}
