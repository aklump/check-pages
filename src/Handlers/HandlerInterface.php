<?php

namespace AKlump\CheckPages\Handlers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface HandlerInterface extends EventSubscriberInterface {

  /**
   * @return string
   *   The lower, snake case ID for this handler.
   */
  public static function getId(): string;

}
