<?php

namespace AKlump\CheckPages\Handlers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface HandlerInterface extends EventSubscriberInterface {

  /**
   * @return string
   *   The ID of this handler.
   */
  public static function getId(): string;

}
