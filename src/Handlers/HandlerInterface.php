<?php

namespace AKlump\CheckPages\Handlers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface HandlerInterface extends EventSubscriberInterface {

  public static function getId(): string;

}
